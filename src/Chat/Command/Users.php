<?php

namespace App\Chat\Command;

use Amp\Mysql\Pool;
use App\Chat\Command;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use JsonSchema\Validator;
use stdClass;

class Users extends Command {
    const LIMIT = 50;

    private $mysql;

    public function __construct(Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute(stdClass $args, $payload) {
        // set default values, because there's no support for them in our JSON schema library currently.
        $args->rel = $args->rel ?? "next";
        $args->start = $args->start ?? 0;

        $rel = $args->rel === "next" ? ">=" : "<=";
        $offset = max($args->start, 0);

        $query = yield $this->mysql->prepare("SELECT u.id, u.name, u.avatar FROM user u WHERE u.id >= ? LIMIT " . (self::LIMIT + 1), [
            $offset
        ]);

        $data = yield $query->fetchObjects();
        $next = isset($data[self::LIMIT]) ? $data[self::LIMIT]->id : false;
        unset($data[self::LIMIT]);

        if (!$data) {
            return Error::make("not_found");
        }

        $response = new Data($data);

        if ($next) {
            $response->addLink("next", [
                "start" => $next,
            ]);
        }

        return $response;
    }

    public function getPermissions() : array {
        return [];
    }
}