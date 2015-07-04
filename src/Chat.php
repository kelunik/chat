<?php

namespace App;

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use Aerys\Websocket;
use Aerys\Websocket\Message;
use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Amp\Redis\SubscribeClient;
use App\Chat\Api;
use App\Chat\Command\Exception;
use App\Chat\MessageCrud;
use Generator;
use JsonSchema\Uri\UriRetriever;
use stdClass;
use function Amp\coroutine;

class Chat implements Websocket {
    const USER_CLIENTS_CONNECTED = "users:clients:connected";
    const USER_CLIENTS_ACTIVE = "users:clients:active";
    const STATE_AVAILABLE = "available";
    const STATE_AWAY = "away";

    /** @var Websocket\Endpoint */
    private $endpoint;
    private $mysql;
    private $redis;
    private $redisSubscribe;
    private $clientStates;
    private $sessions;
    private $users;
    private $api;

    public function __construct (Pool $mysql, Client $redis, SubscribeClient $redisSubscribe, MessageCrud $messageCrud) {
        $this->mysql = $mysql;
        $this->redis = $redis;
        $this->redisSubscribe = $redisSubscribe;
        $this->messageCrud = $messageCrud;
        $this->clientStates = [];
        $this->sessions = [];
        $this->users = [];

        $this->api = new Api(new UriRetriever, $mysql, $redis);
    }

    public function onStart (Websocket\Endpoint $endpoint) {
        $this->endpoint = $endpoint;
        $this->initRoomSubscription();
        $this->initUserSubscription();
    }

    public function onStop () {
        // there's nothing to do...
    }

    public function onHandshake (Request $request, Response $response) {
        $origin = $request->getHeader("origin");

        if (!isOriginAllowed($origin)) {
            $response->setStatus(403);
            $response->setReason("INVALID ORIGIN");
            $response->send("<h1>Invalid Origin</h1>");

            return null;
        }

        return new Session($request);
    }

    public function onOpen (int $clientId, $session) {
        $this->sessions[$clientId] = yield $session->open();
        $this->users[$session->get("login") ?? 0][$clientId] = true;
        $this->clientStates[$clientId] = true;

        if ($session->get("login")) {
            yield $this->redis->hIncrBy(self::USER_CLIENTS_ACTIVE, $session->get("login"));
            yield $this->redis->hIncrBy(self::USER_CLIENTS_CONNECTED, $session->get("login"));
        }
    }

    public function onData (int $clientId, Message $payload) {
        $data = json_decode(yield $payload);
        yield from $this->onMessage($clientId, $data);
    }

    public function onClose (int $clientId, int $code, string $reason) {
        $session = $this->sessions[$clientId];
        $userId = $session->get("login");

        if ($userId) {
            $clients = yield $this->redis->hIncrBy(self::USER_CLIENTS_CONNECTED, $userId, -1);

            if ($clients === 0) {
                yield $this->redis->hDel(self::USER_CLIENTS_ACTIVE, $userId);
                yield $this->redis->hDel(self::USER_CLIENTS_CONNECTED, $userId);
            } else if ($this->clientStates[$clientId]) {
                yield $this->redis->hIncrBy(self::USER_CLIENTS_ACTIVE, $userId, -1);
            }
        }

        unset(
            $this->sessions[$clientId],
            $this->users[$userId ?? 0][$clientId]
        );

        if (empty($this->users[$userId ?? 0])) {
            unset($this->users[$userId ?? 0]);
        }
    }

    protected function onMessage ($clientId, $data) {
        if (is_array($data)) {
            foreach ($data as $message) {
                yield from $this->onMessage($clientId, $message);
            }
        } else {
            if (!$this->isValidPayload($data)) {
                return;
            }

            if ($data->endpoint === "state") {
                $this->onStateChange($clientId, $data);
            }

            $session = $this->sessions[$clientId];
            $command = $this->api->getCommand($data->endpoint);

            if (!$command || !$command->isValid($data->args ?? null, $data->payload ?? null)) {
                return;
            }

            /** @var stdClass $args */
            $args = $data->args ?? new stdClass;
            $args->user_id = $session->get("login") ?? 0;
            $args->user_name = $session->get("login:name") ?? "";
            $args->user_avatar = $session->get("login:avatar") ?? "";

            try {
                $result = $command->execute($args, $data->payload ?? null);

                if ($result instanceof Generator) {
                    $result = yield from $result;
                }
            } catch (Exception $e) {
                $result = [
                    "error" => [
                        "code" => $e->getMessage()
                    ]
                ];
            }

            $this->endpoint->send($clientId, json_encode([
                "sequence_id" => $data->sequence_id,
                "response" => $result,
            ]));
        }
    }

    protected function isValidPayload ($data): bool {
        if (!isset($data->sequence_id, $data->endpoint)) {
            return false;
        }

        if (!is_int($data->sequence_id)) {
            return false;
        }

        if (!is_string($data->endpoint)) {
            return false;
        }

        if (isset($data->args) && !$data->args instanceof stdClass) {
            return false;
        }

        if (isset($this->args)) {
            foreach ($this->args as $value) {
                if (!is_scalar($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function initRoomSubscription () {
        $subscription = $this->redisSubscribe->subscribe("chat:room");
        $subscription->watch(function ($payload) {
            $payload = json_decode($payload);

            if (empty($this->rooms[$payload->room_id])) {
                return;
            }

            $this->endpoint->broadcast(json_encode([
                "type" => $payload->type,
                "data" => $payload->payload
            ]), array_keys($this->rooms[$payload->room_id]));
        });

        $subscription->when(function ($error) {
            if ($error) {
                $this->initRoomSubscription();
            }
        });
    }

    protected function initUserSubscription () {
        $subscription = $this->redisSubscribe->subscribe("chat:user");
        $subscription->watch(function ($payload) {
            $payload = json_decode($payload);

            if (empty($this->users[$payload->user_id])) {
                return;
            }

            $this->endpoint->broadcast(json_encode([
                "type" => $payload->type,
                "data" => $payload->payload
            ]), array_keys($this->users[$payload->user_id]));
        });

        $subscription->when(function ($error) {
            if ($error) {
                $this->initUserSubscription();
            }
        });
    }

    private function onStateChange (int $clientId, $data) {
        if (!isset($data->payload->state) || !is_string($data->payload->state)) {
            return;
        }

        $payload = $data->payload;
        $current = $this->clientStates[$clientId];

        if ($current && $payload->state === self::STATE_AWAY) {
            $this->clientStates[$clientId] = false;
        } else if (!$current && $payload->state === self::STATE_AVAILABLE) {
            $this->clientStates[$clientId] = true;
        } else {
            return; // there's no change, don't do anything and just ignore it.
        }

        $session = $this->sessions[$clientId];
        $userId = $session->get("login");

        if (!$userId) {
            return; // ignore all guests
        }

        $activeClients = yield $payload->state === self::STATE_AVAILABLE
            ? $this->redis->hIncrBy(self::USER_CLIENTS_ACTIVE, $userId, +1)
            : $this->redis->hIncrBy(self::USER_CLIENTS_ACTIVE, $userId, -1);

        if ($activeClients === 0) {
            // send payload: away
        } else if ($activeClients === 1 && $data->state === self::STATE_AVAILABLE) {
            // send payload: available
        }
    }

    private function onSessionChange (Session $session) {
        // if user login changed: close websocket
    }
}
