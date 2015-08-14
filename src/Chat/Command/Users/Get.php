<?php

namespace App\Chat\Command\Users;

use Amp\Mysql\Pool;
use App\Chat\Command;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use JsonSchema\Validator;
use stdClass;

class Get extends Command {
    private $mysql;

    public function __construct(Validator $validator, Pool $mysql) {
        parent::__construct($validator);
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