<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use App\Chat\Api;
use App\Chat\Response\Error;
use JsonSchema\Uri\UriRetriever;
use function Aerys\router;

return (function () use ($mysql, $redis) {
    $api = new Api(new UriRetriever, $mysql, $redis);
    $authentication = new \App\Authentication($mysql);
    $authorization = new \App\ChatAuthorization($mysql);

    $apiCallable = function ($endpoint) use ($api, $mysql, $authentication, $authorization) {
        return function (Request $request, Response $response, array $args) use ($endpoint, $api, $mysql, $authentication, $authorization) {
            $response->setHeader("content-type", "application/json");

            foreach ($args as $key => $arg) {
                if (is_numeric($arg)) {
                    $args[$key] = (int) $arg;
                }
            }

            foreach ($request->getQueryVars() as $key => $value) {
                // Don't allow overriding URL parameters
                if (isset($args[$key])) {
                    continue;
                }

                if (is_numeric($value)) {
                    $args[$key] = (int) $value;
                } else if (is_string($value)) {
                    $args[$key] = $value;
                } else {
                    $response->setStatus(400);
                    $response->send(json_encode([
                        "error" => [
                            "code" => "bad_query_parameters"
                        ]
                    ]));

                    return;
                }
            }

            $auth = $request->getHeader("authentication");
            $auth = explode(" ", $auth);

            if (count($auth) !== 2 || $auth[0] !== "token") {
                $response->setStatus(400);
                $response->send(json_encode([
                    "error" => [
                        "code" => "bad_authentication_header"
                    ]
                ]));

                return;
            }

            $user = yield from $authentication->authenticateWithToken($auth[1]);

            $payload = json_decode(yield $request->getBody());
            $args = $args ? (object) $args : null;
            $command = $api->getCommand($endpoint);

            if (!$command) {
                $response->setStatus(404);
                $response->send(json_encode([], JSON_PRETTY_PRINT));

                return;
            }

            if (!$command->isValid($args, $payload)) {
                $response->setStatus(422);
                $response->send(json_encode([
                    "code" => "invalid_request",
                    "message" => "The provided request data wasn't in the right format",
                    "errors" => $command->getValidationErrors()
                ], JSON_PRETTY_PRINT));

                $command->resetValidation();

                return;
            }

            $command->resetValidation();

            $requiredPermissions = $command->getPermissions();

            if ($user->id < 0) {
                $permissions = $authorization->getBotPermissions($user->id);
            } elseif (isset($args->room_id) || isset($payload->room_id)) {
                $roomId = $args->room_id ?? $payload->room_id;
                $permissions = yield from $authorization->getRoomPermissions($user->id, $roomId);
            } else {
                $permissions = [];
            }

            foreach ($requiredPermissions as $permission) {
                if (!isset($permissions[$permission])) {
                    $response->setStatus(403);
                    $response->send(json_encode([
                        "code" => "forbidden",
                        "message" => "access to resource not granted"
                    ], JSON_PRETTY_PRINT));

                    return;
                }
            }

            /** @var stdClass $args */
            $args = $args ?? new stdClass;
            $args->user_id = $user->id;
            $args->user_name = $user->name;
            $args->user_avatar = $user->avatar;

            try {
                $result = $command->execute($args, $payload);

                if ($result instanceof Generator) {
                    $result = yield from $result;
                }
            } catch (App\Chat\Command\Exception $e) {
                $result = Error::make("bad_request");
            }

            if ($result === null) {
                $result = Error::make("not_found");
            }

            $links = $result->getLinks();

            $response->setStatus($result->getStatus());

            if ($links) {
                $elements = [];

                foreach ($links as $rel => $params) {
                    $uri = strtok($request->getUri(), "?");
                    $uri .= "?" . http_build_query($params);
                    $elements[] = "<{$uri}>; rel=\"{$rel}\"";
                }

                $response->addHeader("link", implode(", ", $elements));
            }

            $response->send(json_encode($result->getData(), JSON_PRETTY_PRINT));
        };
    };

    return router()
        ->get("me", $apiCallable("me"))
        ->get("me/rooms", $apiCallable("me:rooms"))
        ->put("messages", $apiCallable("messages:create"))
        ->get("messages/{message_id:\\d+}", $apiCallable("messages:get"))
        ->patch("messages/{message_id:\\d+}", $apiCallable("messages:edit"))
        ->patch("pings/{message_id:\\d+}", $apiCallable("pings:edit"))
        ->get("pings/{message_id:\\d+}", $apiCallable("pings:get"))
        ->get("rooms/{room_id:\\d+}", $apiCallable("rooms:get"))
        ->patch("rooms/{room_id:\\d+}", $apiCallable("rooms:edit"))
        ->get("rooms/{room_id:\\d+}/users", $apiCallable("rooms:users:get"));
})();