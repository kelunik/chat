<?php

namespace App\Chat\Command;

use Amp\Mysql\Pool;
use App\Chat\Command;
use App\Chat\Response\Data;
use JsonSchema\Validator;
use stdClass;

class Rooms extends Command {
    const LIMIT = 50;

    private $mysql;

    public function __construct(Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute(stdClass $args, $payload) {
        // set default values, because there's no support for them in our JSON schema library currently.
        $args->rel = $args->rel ?? "next";
        $args->start = $args->start ?? 0;

        $rel = $args->rel === "next" ? ">=" : "<=";
        $offset = max($args->start, 0);

        if ($args->user_id > 0) {
            $query = yield $this->mysql->prepare("SELECT r.id, r.name, r.description, r.visibility FROM room r LEFT JOIN room_user ru ON (ru.room_id = r.id && ru.user_id = ?) WHERE (ru.user_id is not null || r.visibility != 'secret') && r.id >= ? LIMIT " . (self::LIMIT + 1), [
                $args->user_id, $offset
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