<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\RoomStorage;

class Edit extends Command {
    private $roomStorage;

    public function __construct(RoomStorage $roomStorage) {
        $this->roomStorage = $roomStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();
        $payload = $request->getPayload();

        if (!isset($payload->name) && !isset($payload->description)) {
            return new Error("bad_request", "either name or description must be set", 400);
        }

        $room = yield $this->roomStorage->get($args->id);

        if (!$room) {
            return Error::make("not_found");
        }

        $name = $payload->name ?? $room->name;
        $description = $payload->description ?? $room->description;

        yield $this->roomStorage->update($args->id, $name, $description);

        return new Data([
            "id" => $args->id,
            "name" => $name,
            "description" => $description,
        ]);
    }

    public function getPermissions() : array {
        return ["edit"];
    }
}