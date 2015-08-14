<?php

namespace App;

use Amp\Mysql\Pool;

class Authentication {
    private $mysql;

    public function __construct (Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function authenticateWithToken (string $token) {
        $auth = explode(":", $token, 2);

        if (count($auth) !== 2) {
            throw new MalformedTokenException("Provided token didn't match the required format");
        }

        list($id, $hash) = $auth;

        // use @ so we don't have to check for invalid strings manually
        $hash = (string) @hex2bin($hash);

        $stmt = yield $this->mysql->prepare("SELECT `token` FROM `auth_token` WHERE `user_id` = ?", [$id]);
        $user = yield $stmt->fetchObject();

        if (!$user || !hash_equals($user->token, $hash)) {
            throw new TokenException("User not found or wrong token");
        }

        $stmt = yield $this->mysql->prepare("SELECT `id`, `name`, `avatar` FROM `user` WHERE `id` = ?", [$id]);
        $user = yield $stmt->fetchObject();

        if (!$user) {
            throw new RecordNotFoundException("User had a valid token, but does not exist");
        }

        return $user;
    }
}