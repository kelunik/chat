<?php

namespace App\Chat\Command;

use Amp\Mysql\Pool;
use App\Chat\Command;
use JsonSchema\Validator;
use stdClass;

class Rooms extends Command {
    const COUNT = 5;

    private $mysql;

    public function __construct (Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute (stdClass $args, $payload) {
        $rel = $args->rel ?? "next";

        $result = yield $this->mysql->prepare(
            "SELECT r.id, r.name, r.description FROM `room` r ORDER BY r.id ASC LIMIT ?, ?",
            [$start, self::COUNT]
        );

        $rooms = yield $result->fetchObjects();

        return new Data($rooms);
    }

    public function getPermissions () : array {
        return [];
    }
}