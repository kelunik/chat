<?php

namespace App\Chat\Command\Rooms;

use Amp\Mysql\Pool;
use App\Chat\Command;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use JsonSchema\Validator;
use stdClass;

class Edit extends Command {
    private $mysql;

    public function __construct(Validator $validator, Pool $mysql) {
        parent::__construct($validator);
        $this->mysql = $mysql;
    }

    public function execute(stdClass $args, $payload) {
        if (!isset($payload->name) && !isset($payload->description)) {
            return new Error("bad_request", "either name or description must be set", 400);
        }

        $stmt = yield $this->mysql->prepare("SELECT `id`, `name`, `description` FROM `room` WHERE `id` = ?", [
            $args->room_id
        ]);

        $room = yield $stmt->fetchObject();

        if ($room) {
            $name = $payload->name ?? $room->name;
            $description = $payload->description ?? $room->description;

            $stmt = yield $this->mysql->prepare("UPDATE `room` SET `name` = ?, `description` = ? WHERE `id` = ?", [
                $name, $description, $args->room_id
            ]);

            return new Data([
                "id" => $args->room_id,
                "name" => $name,
                "description" => $description,
            ]);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return ["edit"];
    }
}