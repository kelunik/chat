<?php

namespace Kelunik\Chat\Commands;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\UserStorage;
use function Kelunik\Chat\createPaginationResult;

class Users extends Command {
    private $userStorage;

    public function __construct(UserStorage $userStorage) {
        $this->userStorage = $userStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? true;
        $args->cursor = $args->cursor ?? 0;

        $data = yield $this->userStorage->getAll($args->cursor, $args->asc);

        return createPaginationResult($data);
    }

    public function getPermissions() : array {
        return [];
    }
}