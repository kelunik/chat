<?php

namespace App\Chat\Command;

use Amp\Mysql\Pool;
use App\Chat\Command;
use JsonSchema\Validator;

class Rooms extends Command {
    const COUNT = 10;

    private $mysql;

    public function __construct (Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute ($args, $payload) {
        $start = ($args->page - 1 ?? 0) * self::COUNT;

        if ($start < 0) {
            return null;
        }

        $result = yield $this->mysql->prepare(
            "SELECT r.id, r.name, r.description FROM `rooms` r ORDER BY r.id ASC LIMIT ?, ?",
            [$start, self::COUNT]
        );

        return yield $result->fetchObjects();
    }
}