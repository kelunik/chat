<?php

namespace Kelunik\Chat\Commands;

use Amp\Mysql\Pool;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\Response;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use stdClass;

class Rooms extends Command {
    const LIMIT = 50;

    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function execute(Request $request, User $user): Response {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->rel = $args->rel ?? "next";
        $args->start = $args->start ?? 0;

        $rel = $args->rel === "next" ? ">=" : "<=";
        $offset = max($args->start, 0);

        if ($user->id > 0) {
            $query = yield $this->mysql->prepare("SELECT r.id, r.name, r.description, r.visibility FROM room r LEFT JOIN room_user ru ON (ru.room_id = r.id && ru.user_id = ?) WHERE (ru.user_id is not null || r.visibility != 'secret') && r.id >= ? LIMIT " . (self::LIMIT + 1), [
                $user->id, $offset
            ]);
        } else {
            $query = yield $this->mysql->prepare("SELECT r.id, r.name, r.description, r.visibility FROM room r WHERE r.visibility != 'secret' && r.id >= ? LIMIT " . (self::LIMIT + 1), [
                $offset
            ]);
        }

        $data = yield $query->fetchObjects();
        $next = isset($data[self::LIMIT]) ? $data[self::LIMIT]->id : false;
        unset($data[self::LIMIT]);

        $response = new Data($data);

        if ($next) {
            $response->addLink("next", [
                "start" => $next,
            ]);
        }

        return $response;
    }

    public function getPermissions () : array {
        return [];
    }
}