<?php

namespace Kelunik\Chat\Commands\Me;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\RoomStorage;

class Rooms extends Command {
    private $roomStorage;

    public function __construct(RoomStorage $roomStorage) {
        $this->roomStorage = $roomStorage;
    }

    public function execute(Request $request, User $user) {
        // unauthenticated and special users can't join rooms
        if ($user->id <= 0) {
            return new Data([]);
        }

        $rooms = yield $this->roomStorage->getByUser($user->id);

        return new Data($rooms);
    }

    public function getPermissions() : array {
        return [];
    }
}