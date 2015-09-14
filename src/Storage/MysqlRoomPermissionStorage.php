<?php

namespace App\Storage;

use Amp\Mysql\Pool;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\RoomStorage;

class MysqlRoomPermissionStorage implements RoomPermissionStorage {
    private $mysql;
    private $roomStorage;

    public function __construct(Pool $mysql, RoomStorage $roomStorage) {
        $this->mysql = $mysql;
    }

    public function getPermissions(int $user, int $room) {
        $query = yield $this->mysql->prepare("SELECT `permissions` FROM `room_user` WHERE `user_id` = ? && `room_id` = ?", [
            $user, $room,
        ]);

        $result = yield $query->fetch();

        if ($result) {
            $permissions = json_decode($result["permissions"]);

            return array_flip($permissions);
        }

        $room = yield $this->roomStorage->get($room);

        if (!$room) {
            return [];
        }

        if ($room->public) {
            return array_flip(["read"]);
        }

        return [];
    }
}