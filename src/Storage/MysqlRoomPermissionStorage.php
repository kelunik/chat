<?php

namespace App\Storage;

use Amp\Mysql\Pool;
use Kelunik\Chat\Storage\int;
use Kelunik\Chat\Storage\RoomPermissionStorage;

class MysqlRoomPermissionStorage implements RoomPermissionStorage {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function getPermissions(int $user, int $room) {
        $query = yield $this->mysql->prepare("SELECT `permissions` FROM `room_user` WHERE `user_id` = ? && `room_id` = ?", [
            $user, $room
        ]);

        $result = yield $query->fetch();

        if ($result) {
            $permissions = json_decode($result["permissions"]);
            return array_flip($permissions);
        }

        return [];
    }
}