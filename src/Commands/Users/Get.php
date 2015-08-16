<?php

namespace Kelunik\Chat\Commands\Users;

use Amp\Mysql\Pool;
use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use stdClass;

class Get extends Command {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function execute(stdClass $args, $payload) {
        $stmt = yield $this->mysql->prepare("SELECT `id`, `name`, `avatar` FROM `user` WHERE `id` = ?", [
            $args->id
        ]);

        $user = yield $stmt->fetchObject();

        if ($user) {
            return new Data($user);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return [];
    }
}