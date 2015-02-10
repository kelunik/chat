<?php

namespace App;

use Amp\Reactor;
use Amp\Redis\Redis;
use Mysql\Pool;

class RoomHandler {
    private $db;
    private $reactor;
    private $redis;
    private $listener;
    private $rooms;
    private $handlers;
    private $clients;
    private $sessions;
    private $users;
    private $sessionHandler;
    private $chatApi;
    private $active;

    public function __construct (Pool $db, Reactor $reactor) {
        $this->db = $db;
        $this->reactor = $reactor;

        $this->redis = new Redis([
            "host" => "127.0.0.1:6380",
            "password" => REDIS_PASSWORD
        ], $reactor);

        $this->listener = new Redis([
            "host" => "127.0.0.1:6380",
            "password" => REDIS_PASSWORD
        ], $reactor);

        $this->handlers = [
            "lost-push" => "onMultiMessage",
            "message" => "handleMessage",
            "message-edit" => "handleMessageEdit",
            "missed-query" => "handleMissedQuery",
            "whereami" => "handleWhereAmI",
            "transcript" => "handleTranscript",
            "star" => "handleStar",
            "unstar" => "handleUnstar",
            "stars" => "handleStars",
            "ping" => "handlePing",
            "activity" => "handleActivity"
        ];

        $this->clients = $this->users = $this->sessions = [];
        $this->sessionHandler = new SessionManager();
        $this->chatApi = new ChatApi($this->db, $this->redis);
        $this->active = [];
    }

    public function init () {
        $keys = yield $this->redis->keys("user.*");

        foreach ($keys as $key) {
            yield $this->redis->del($key);
        }

        $keys = yield $this->redis->keys("room.*");

        foreach ($keys as $key) {
            yield $this->redis->del($key);
        }

        $this->listener->subscribe("chat.room", yield "bind" => function ($payload) {
            $payload = json_decode($payload);

            if (empty($this->rooms[$payload->roomId])) {
                return;
            }

            yield "broadcast" => [
                json_encode([
                    "type" => $payload->type,
                    "data" => $payload->payload
                ]),
                $this->rooms[$payload->roomId],
                isset($payload->clientId) ? [$payload->clientId] : []
            ];
        });

        $this->listener->subscribe("chat.user", yield "bind" => function ($payload) {
            $payload = json_decode($payload);

            if (empty($this->users[$payload->userId])) {
                return;
            }

            yield "broadcast" => [
                json_encode([
                    "type" => $payload->type,
                    "data" => $payload->payload
                ]),
                array_keys($this->users[$payload->userId]),
                []
            ];
        });

        $this->listener->subscribe("chat.session", yield "bind" => function ($payload) {
            $payload = json_decode($payload);
            $sessionId = $payload->sessionId;

            if (empty($this->sessions[$sessionId])) {
                return;
            }

            $clients = $this->users[$this->sessions[$sessionId]->id];
            $closingClients = [];

            foreach ($clients as $clientId => $client) {
                if ($this->clients[$clientId] === $sessionId) {
                    $closingClients[] = $clientId;
                }
            }

            if (!empty($closingClients)) {
                yield "broadcast" => [
                    json_encode([
                        "type" => $payload->type,
                        "data" => $payload->payload
                    ]), $closingClients, []
                ];
            }
        });
    }

    public function stop () {
        yield $this->listener->unsubscribe("room.broadcast");
        yield $this->listener->unsubscribe("chat.user");
        yield $this->listener->unsubscribe("chat.session");
        yield $this->listener->close(true);
        yield $this->redis->close();
    }

    public function onMessage ($clientId, $data) {
        $handler = isset($this->handlers[$data->type]) ? $this->handlers[$data->type] : null;

        if ($handler === null) {
            return;
        }

        yield $this->$handler($clientId, $data->data);
    }

    private function onMultiMessage ($clientId, array $data) {
        foreach ($data as $message) {
            yield $this->onMessage($clientId, $message);
        }
    }

    public function addClient ($clientId, $sessionId, $session) {
        $this->clients[$clientId] = $sessionId;
        $this->sessions[$sessionId] = $session;
        $this->users[$session->id][$clientId] = $sessionId;
        $this->active[$clientId] = true;

        $result = yield $this->db->prepare("SELECT r.id, r.name, r.description FROM `rooms` AS r, `room_users` AS ru WHERE r.id = ru.roomId && ru.userId = ? ORDER BY r.name ASC", [
            $session->id
        ]);

        foreach (yield $result->fetchAll() as $room) {
            list($roomId, $roomName, $roomDescription) = $room;
            $this->rooms[(int) $roomId][$clientId] = $clientId;
        }

        yield $this->redis->incr("user.{$session->id}.clients-active");
        yield $this->redis->incr("user.{$session->id}.clients");

        foreach ($this->rooms as $id => $room) {
            if (in_array($clientId, $room)) {
                $success = yield $this->redis->smove("room.{$id}.users.inactive", "room.{$id}.users.active", $session->id);

                if (!$success) {
                    yield $this->redis->sadd("room.{$id}.users.active", $session->id);
                }

                yield $this->setActivity($session->id, $id, "active");
            }
        }
    }

    public function removeClient ($clientId) {
        $sessionId = $this->clients[$clientId];
        $userId = $this->sessions[$sessionId]->id;

        if ($this->active[$clientId]) {
            $active = yield $this->redis->decr("user.{$userId}.clients-active");

            if ($active === 0) {
                yield $this->redis->del("user.{$userId}.clients-active");
            }
        }

        $clients = yield $this->redis->decr("user.{$userId}.clients");

        if ($clients === 0) {
            yield $this->redis->del("user.{$userId}.clients", "user.{$userId}.clients-active");

            foreach ($this->rooms as $id => $room) {
                if (in_array($clientId, $room)) {
                    yield $this->redis->srem("room.{$id}.users.active", $userId);
                    yield $this->redis->srem("room.{$id}.users.inactive", $userId);
                    yield $this->setActivity($userId, $id, "offline");
                }
            }
        }

        unset(
            $this->clients[$clientId],
            $this->users[$userId][$clientId]
        );

        if (empty($this->users[$userId])) {
            unset($this->users[$userId], $this->sessions[$sessionId]);
        }
    }

    private function getSession ($clientId) {
        $sessionId = isset($this->clients[$clientId])
            ? $this->clients[$clientId]
            : null;

        if ($sessionId === null) {
            throw new \Exception("no session id");
        }

        $session = isset($this->sessions[$sessionId])
            ? $this->sessions[$sessionId]
            : null;

        if ($session === null) {
            throw new \Exception("no session");
        }

        return $session;
    }

    private function handleMessage ($clientId, $data) {
        if (!isset($data->roomId, $data->text, $data->tempId) || !is_int($data->roomId) || !is_string($data->text) || !is_string($data->tempId)) {
            return;
        }

        $session = $this->getSession($clientId);

        $user = new User($this->db, $session->id, $session->name, $session->avatar);
        yield $this->chatApi->addMessage($data->roomId, $user, $data->text, $data->tempId, time());
    }

    private function handleMessageEdit ($clientId, $data) {
        if (!isset($data->messageId, $data->text, $data->tempId) || !is_int($data->messageId) || !is_string($data->text) || !is_string($data->tempId)) {
            return;
        }

        $session = $this->getSession($clientId);
        $user = new User($this->db, $session->id, $session->name, $session->avatar);
        yield $this->chatApi->editMessage($data->messageId, $user, $data->text, $data->tempId, time());
    }

    private function handleMissedQuery ($clientId, $data) {
        if (!isset($data->roomId, $data->last) || !is_int($data->roomId) || !is_int($data->last)) {
            return;
        }

        $session = $this->getSession($clientId);
        $result = yield $this->db->prepare(MessageHandler::buildQuery("roomId = ? && id > ?"), [$data->roomId, $data->last, $session->id]);
        $messages = [];

        foreach (yield $result->fetchAll() as $message) {
            $messages[] = $this->createMessage($message, $data->last == -1);
        }

        yield "send" => json_encode([
            "type" => "missed-query",
            "data" => [
                "roomId" => $data->roomId,
                "init" => $data->last === -1,
                "messages" => array_reverse($messages)
            ]
        ]);
    }

    private function handleTranscript ($clientId, $data) {
        if (!isset($data->roomId, $data->messageId) || !is_int($data->roomId) || !is_int($data->messageId)) {
            return;
        }

        $session = $this->getSession($clientId);

        if ($data->messageId === -1) {
            $result = yield $this->db->prepare(MessageHandler::buildQuery("roomId = ?"), [$data->roomId, $session->id]);
        } else {
            $result = yield $this->db->prepare(MessageHandler::buildQuery("roomId = ? && id < ?"), [$data->roomId, $data->messageId, $session->id]);
        }

        $messages = [];

        foreach (yield $result->fetchAll() as $message) {
            $messages[] = $this->createMessage($message, true);
        }

        yield "send" => json_encode([
            "type" => "transcript",
            "data" => [
                "roomId" => $data->roomId,
                "messages" => $messages
            ]
        ]);
    }

    public function handleWhereAmI ($clientId, $data) {
        if (!isset($data->join)) {
            return;
        }

        $session = $this->getSession($clientId);
        $result = yield $this->db->prepare("INSERT IGNORE INTO room_users (roomId, userId, role, joinedTime) VALUES (?, ?, ?, ?)", [
            $data->join, $session->id, "WRITER", time()
        ]);

        if ($result->affectedRows === 1) {
            yield $this->redis->publish("chat.room", json_encode([
                "roomId" => $data->join,
                "type" => "user-join",
                "payload" => [
                    "roomId" => $data->join,
                    "user" => [
                        "id" => $session->id,
                        "name" => $session->name,
                        "avatar" => $session->avatar,
                        "state" => "active"
                    ]
                ]
            ]));
        }

        $result = yield $this->db->prepare("SELECT r.id, r.name, r.description FROM `rooms` AS r, `room_users` AS ru WHERE r.id = ru.roomId && ru.userId = ? ORDER BY r.name ASC", [
            $session->id
        ]);

        $query = yield $this->db->prepare("SELECT u.id, u.name, u.avatar_url AS avatar FROM `users` AS u, `room_users` AS ru WHERE u.id = ru.userId && ru.roomId = ? ORDER BY u.lastActivity DESC");
        $query2 = yield $this->db->prepare("SELECT p.messageId FROM pings AS p, messages AS m WHERE p.userId = ? && p.messageId = m.id && m.roomId = ? && p.seen = 0");

        $rooms = [];

        foreach (yield $result->fetchAll() as $room) {
            list($roomId, $roomName, $roomDescription) = $room;

            $queryResult = yield $query->execute([$roomId]);
            $users = [];

            $usersActive = yield $this->redis->smembers("room.{$roomId}.users.active");
            $usersInactive = yield $this->redis->smembers("room.{$roomId}.users.inactive");

            $users = [];

            foreach (yield $queryResult->fetchObjects() as $user) {
                $user->id = (int) $user->id;
                $user->state = "offline";
                $users[$user->id] = $user;
            }

            foreach ($usersActive as $userId) {
                $userId = (int) $userId;

                if (isset($users[$userId])) {
                    $users[$userId]->state = "active";
                }
            }

            foreach ($usersInactive as $userId) {
                $userId = (int) $userId;

                if (isset($users[$userId])) {
                    $users[$userId]->state = "inactive";
                }
            }

            $users = array_values($users);

            $queryResult2 = yield $query2->execute([$session->id, $roomId]);
            $pings = [];

            foreach (yield $queryResult2->fetchAll() as $user) {
                $pings[] = $user[0];
            }

            $rooms[] = [
                "id" => $roomId,
                "name" => $roomName,
                "description" => $roomDescription,
                "users" => $users,
                "pings" => $pings
            ];

            $this->rooms[(int) $roomId][$clientId] = $clientId;
        }

        yield "send" => json_encode([
            "type" => "whereami",
            "data" => $rooms
        ]);
    }

    public function handleStar ($clientId, $data) {
        if (!isset($data->messageId) || !is_int($data->messageId)) {
            return;
        }

        $session = $this->getSession($clientId);
        yield $this->chatApi->addStar($session->id, $data->messageId, time());
    }

    public function handleUnstar ($clientId, $data) {
        if (!isset($data->messageId) || !is_int($data->messageId)) {
            return;
        }

        $session = $this->getSession($clientId);
        yield $this->chatApi->removeStar($session->id, $data->messageId);
    }

    public function handleStars ($clientId, $data) {
        if (!isset($data->roomId) || !is_int($data->roomId)) {
            return;
        }

        $session = $this->getSession($clientId);
        // TODO: Optimize query...
        $result = yield $this->db->prepare("SELECT m . id, m . userId, u . name AS userName, m . text, (SELECT COUNT(1) FROM `message_stars` AS ms WHERE ms . messageId = m . id) AS stars, (EXISTS(SELECT 1 FROM message_stars AS ms WHERE ms . userId = ? && ms . messageId = m . id)) AS starred, m . time FROM `messages` AS m, `users` AS u, `room_users` AS ru WHERE m . userId = u . id && m . roomId = ru . roomId && ru . userId = ? && m . roomId = ? HAVING stars > 0 ORDER BY stars DESC LIMIT 10", [
            $session->id, $session->id, $data->roomId
        ]);
        $messages = [];

        foreach (yield $result->fetchAll() as $message) {
            list($id, $userId, $userName, $text, $stars, $starred, $time) = $message;

            $messages[] = [
                "messageId" => $id,
                "messageText" => $text,
                "user" => [
                    "id" => $userId,
                    "name" => $userName
                ],
                "stars" => $stars,
                "starred" => $starred,
                "time" => $time
            ];
        }

        yield "send" => json_encode([
            "type" => "stars",
            "data" => [
                "roomId" => $data->roomId,
                "messages" => $messages
            ]
        ]);
    }

    public function handlePing ($clientId, $data) {
        if (!isset($data->messageId) || !is_int($data->messageId)) {
            return;
        }

        $session = $this->getSession($clientId);
        yield $this->chatApi->clearPing($session->id, $data->messageId);
    }

    public function handleActivity ($clientId, $data) {
        $session = $this->getSession($clientId);

        if (isset($data->state) && in_array($data->state, ["active", "inactive"])) {
            $oldState = $this->active[$clientId];

            if ($oldState && $data->state === "inactive") {
                $this->active[$clientId] = false;
            } else if (!$oldState && $data->state === "active") {
                $this->active[$clientId] = true;
            } else {
                return;
            }

            $active = yield $data->state === "active"
                ? $this->redis->incr("user.{$session->id}.clients-active")
                : $this->redis->decr("user.{$session->id}.clients-active");

            if ($active === 0) {
                foreach ($this->rooms as $id => $room) {
                    if (in_array($clientId, $room)) {
                        $success = yield $this->redis->smove("room.{$id}.users.active", "room.{$id}.users.inactive", $session->id);

                        if (!$success) {
                            yield $this->redis->sadd("room.{$id}.users.inactive", $session->id);
                        }

                        yield $this->setActivity($session->id, $id, $data->state);
                    }
                }
            } else if ($active === 1 && $data->state === "active") {
                foreach ($this->rooms as $id => $room) {
                    if (in_array($clientId, $room)) {
                        $success = yield $this->redis->smove("room.{$id}.users.inactive", "room.{$id}.users.active", $session->id);

                        if (!$success) {
                            yield $this->redis->sadd("room.{$id}.users.active", $session->id);
                        }

                        yield $this->setActivity($session->id, $id, $data->state);
                    }
                }
            }
        }
    }

    public function setActivity ($userId, $roomId, $state) {
        yield $this->redis->publish("chat.room", json_encode([
            "roomId" => $roomId,
            "type" => "activity",
            "payload" => [
                "userId" => $userId,
                "state" => $state
            ]
        ]));
    }

    private function createMessage ($message, $seen = false) {
        list($messageId, $roomId, $userId, $userName, $userAvatar, $text, $edit, $time, $stars, $starred, $replyTo, $replyToUserId, $replyToUserName) = $message;

        return [
            "messageId" => $messageId,
            "roomId" => $roomId,
            "messageText" => $text,
            "user" => [
                "id" => $userId,
                "name" => $userName,
                "avatar" => $userAvatar
            ],
            "stars" => (int) $stars,
            "starred" => (boolean) $starred,
            "time" => $time,
            "edit-time" => $edit > 0 ? $edit : null,
            "seen" => $seen,
            "reply" => isset($replyTo) ? [
                "messageId" => (int) $replyTo,
                "user" => [
                    "id" => (int) $replyToUserId,
                    "name" => $replyToUserName
                ]
            ] : null
        ];
    }
}
