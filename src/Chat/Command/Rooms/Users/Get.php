<?php

namespace App\Chat\Command\Rooms\Users;

use Amp\Mysql\Pool;
use App\Chat\Command;
use App\Chat\Response;
use JsonSchema\Validator;

class Get extends Command {
    const LIMIT = 10;

    private $mysql;

    public function __construct (Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute ($args, $payload) {
        // set default values, because there's no support for them in our JSON schema library currently.
        $args->rel = $args->rel ?? "next";
        $args->start = $args->start ?? 0;

        $rel = $args->rel === "next" ? ">=" : "<=";
        $offset = max($args->start, 0);

        $query = yield $this->mysql->prepare("SELECT u.id, u.name, u.avatar FROM users u, room_users ru WHERE u.id = ru.userId && ru.userId {$rel} ? && ru.roomId = ? LIMIT " . (self::LIMIT + 1), [
            $offset, $args->room_id
        ]);

        $response = new Response;
        $data = yield $query->fetchObjects();

        if (isset($data[self::LIMIT])) {
            $response->addLink("next", [
                "start" => $data[self::LIMIT]->id,
                "rel" => "next",
            ]);

            unset($data[self::LIMIT]);
        }

        return $response->setData($data);
    }

    public function getPermissions () : array {
        return ["read"];
    }
}