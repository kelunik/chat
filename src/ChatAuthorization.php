<?php

namespace App;

use Amp\Mysql\Pool;

class ChatAuthorization {
    private $mysql;

    public function __construct (Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function getRoomPermissions (int $userId, int $roomId) {
        $query = yield $this->mysql->prepare("SELECT `permissions` FROM `room_user` WHERE `user_id` = ? && `room_id` = ?", [
            $userId, $roomId
        ]);

        $result = yield $query->fetch();

        if ($result) {
            $permissions = json_decode($result["permissions"]);
            return array_flip($permissions);
        }

        return [];
    }

    public function getBotPermissions (int $userId) {
        if ($userId === -1) {
            return ["write" => true];
        }

        return [];
    }
}