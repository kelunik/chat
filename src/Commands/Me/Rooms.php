<?php

namespace Kelunik\Chat\Commands\Me;

use Amp\Mysql\Pool;
use Kelunik\Chat\Command;
use stdClass;

class Rooms extends Command {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function execute(stdClass $args, $payload) {
        // unauthenticated and special users can't join rooms
        if ($args->user_id <= 0) {
            return new Data([]);
        }

        $result = yield $this->mysql->prepare(
            "SELECT r.id, r.name, r.description, ru.permissions FROM `room` AS r, `room_user` AS ru WHERE r.id = ru.room_id && ru.user_id = ? ORDER BY r.name ASC",
            [$args->user_id]
        );

        $rooms = yield $result->fetchObjects();

        return new Data($rooms);
    }

    public function getPermissions() : array {
        return [];
    }
}