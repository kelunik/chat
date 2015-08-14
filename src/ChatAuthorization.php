<?php

namespace App;

use Amp\Mysql\Pool;

class ChatAuthorization {
    private $mysql;

    public function __construct (Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function getRoomPermissions (int $userId, int $roomId) {
        $query = yield $this->mysql->prepare("SELECT `permissions` FROM `room_users` WHERE `userId` = ? && `roomId` = ?", [
            $userId, $roomId
        ]);

        $result = yield $query->fetchRow();

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