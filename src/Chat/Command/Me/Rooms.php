<?php

namespace App\Chat\Command\Me;

use Amp\Mysql\Pool;
use App\Chat\Command;
use JsonSchema\Validator;

class Rooms extends Command {
    private $mysql;

    public function __construct (Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute ($args, $payload) {
        $result = yield $this->mysql->prepare(
            "SELECT r.id, r.name, r.description, ru.permissions FROM `rooms` AS r, `room_users` AS ru WHERE r.id = ru.roomId && ru.userId = ? ORDER BY r.name ASC",
            [$args->user_id]
        );

        return yield $result->fetchObjects();
    }
}