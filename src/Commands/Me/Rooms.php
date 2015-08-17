<?php

namespace Kelunik\Chat\Commands\Me;

use Amp\Mysql\Pool;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;

class Rooms extends Command {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function execute(Request $request, User $user) {
        // unauthenticated and special users can't join rooms
        if ($user->id <= 0) {
            return new Data([]);
        }

        $result = yield $this->mysql->prepare(
            "SELECT r.id, r.name, r.description, ru.permissions FROM `room` AS r, `room_user` AS ru WHERE r.id = ru.room_id && ru.user_id = ? ORDER BY r.name ASC",
            [$user->id]
        );

        $rooms = yield $result->fetchObjects();

        return new Data($rooms);
    }

    public function getPermissions() : array {
        return [];
    }
}