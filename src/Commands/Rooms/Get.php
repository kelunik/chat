<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\RoomStorage;

class Get extends Command {
    private $roomStorage;

    public function __construct(RoomStorage $roomStorage) {
        $this->roomStorage = $roomStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $room = yield $this->roomStorage->get($args->id);

        if ($room) {
            return new Data($room);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return ["read"];
    }
}