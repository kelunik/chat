<?php

namespace Kelunik\Chat\Commands\Users;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\UserStorage;

class Get extends Command {
    private $userStorage;

    public function __construct(UserStorage $userStorage) {
        $this->userStorage = $userStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $user = yield $this->userStorage->get($args->id);

        if ($user) {
            return new Data($user);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return [];
    }
}