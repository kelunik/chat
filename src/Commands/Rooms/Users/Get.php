<?php

namespace Kelunik\Chat\Commands\Rooms\Users;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\RoomStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Kelunik\Chat\createPaginationResult;

class Get extends Command {
    private $roomStorage;
    private $userStorage;

    public function __construct(RoomStorage $roomStorage, UserStorage $userStorage) {
        $this->roomStorage = $roomStorage;
        $this->userStorage = $userStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? true;
        $args->cursor = $args->cursor ?? 0;

        $data = yield $this->roomStorage->getMembers($args->id, $args->cursor, $args->asc);
        $users = yield $this->userStorage->getFromIds($data, $args->asc);

        return createPaginationResult($users);
    }

    public function getPermissions() : array {
        return ["read"];
    }
}