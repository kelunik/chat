<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\RoomStorage;
use function Amp\resolve;

class Edit extends Command {
    private $roomStorage;
    private $roomPermissionStorage;

    public function __construct(RoomStorage $roomStorage, RoomPermissionStorage $roomPermissionStorage) {
        $this->roomStorage = $roomStorage;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();
        $payload = $request->getPayload();

        if (!isset($payload->name) && !isset($payload->description)) {
            return new Error("bad_request", "either name or description must be set", 400);
        }

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $args->id));

        if (!isset($permissions[Permission::ADMIN])) {
            return Error::make("forbidden");
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
}