<?php

namespace Kelunik\Chat\Commands;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\RoomStorage;
use function Kelunik\Chat\createPaginationResult;

class Rooms extends Command {
    private $roomStorage;

    public function __construct(RoomStorage $roomStorage) {
        $this->roomStorage = $roomStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? true;
        $args->cursor = $args->cursor ?? 0;

        $rooms = yield $this->roomStorage->getAll($user->id, $args->cursor, $args->asc);

        return createPaginationResult($rooms);
    }
}