<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use App\Chat\Api;
use JsonSchema\Uri\UriRetriever;
use function Aerys\router;

return (function () use ($mysql, $redis) {
    $api = new Api(new UriRetriever, $mysql, $redis);

    $apiCallable = function ($endpoint) use ($api) {
        return function (Request $request, Response $response, array $args) use ($endpoint, $api) {
            foreach ($args as $key => $arg) {
                if (is_numeric($arg)) {
                    $args[$key] = (int) $arg;
                }
            }

            foreach ($request->getQueryVars() as $key => $value) {
                if (is_numeric($value)) {
                    $args[$key] = (int) $value;
                } else {
                    $args[$key] = $value;
                }
            }

            $session = yield (new Session($request))->read();
            $payload = json_decode(yield $request->getBody());
            $args = $args ? (object) $args : null;

            $response->setHeader("content-type", "application/json");
            $command = $api->getCommand($endpoint);

            if (!$command || !$command->isValid($args, $payload)) {
                $response->setStatus(422);
                $response->send(json_encode([
                    "error" => [
                        "code" => "invalid_request"
                    ]
                ], JSON_PRETTY_PRINT));

                return;
            }

            /** @var stdClass $args */
            $args = $args ?? new stdClass;
            $args->user_id = $session->get("login") ?? 0;
            $args->user_name = $session->get("login:name") ?? "";
            $args->user_avatar = $session->get("login:avatar") ?? "";

            try {
                $result = $command->execute($args, $payload);

                if ($result instanceof Generator) {
                    $result = yield from $result;
                }
            } catch (Exception $e) {
                $response->setStatus(400);
                $result = [
                    "error" => [
                        "code" => $e->getMessage()
                    ]
                ];
            }

            if ($result === null) {
                $response->setStatus(404);
                $result = [
                    "error" => [
                        "code" => "not_found"
                    ]
                ];
            }

            $response->send(json_encode($result, JSON_PRETTY_PRINT));
        };
    };

    return router()
        ->get("me", $apiCallable("me"))
        ->get("me/rooms", $apiCallable("me/rooms"))
        ->put("messages", $apiCallable("messages/new"))
        ->get("messages/{message_id:\\d+}", $apiCallable("messages/get"))
        ->patch("messages/{message_id:\\d+}", $apiCallable("messages/edit"))
        ->delete("messages/{message_id:\\d+}", $apiCallable("messages/delete"))
        ->get("rooms", $apiCallable("rooms"))
        ->get("rooms/{room_id:\\d+}", $apiCallable("rooms/get"))
        ->patch("rooms/{room_id:\\d+}", $apiCallable("rooms/edit"))
        ->delete("rooms/{room_id:\\d+}", $apiCallable("rooms/delete"))
        ->get("rooms/{room_id:\\d+}/messages", $apiCallable("rooms/messages/get"))
        ->get("rooms/{room_id:\\d+}/users", $apiCallable("rooms/users/get"))
        ->get("users", $apiCallable("users"))
        ->get("users/{user_id:\\d+}", $apiCallable("users/get"));
})();