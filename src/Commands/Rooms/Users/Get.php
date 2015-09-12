<?php

namespace Kelunik\Chat\Commands\Rooms\Users;

use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\RoomStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Amp\resolve;
use function Kelunik\Chat\createPaginationResult;

class Get extends Command {
    private $roomStorage;
    private $userStorage;
    private $roomPermissionStorage;

    public function __construct(RoomStorage $roomStorage, UserStorage $userStorage, RoomPermissionStorage $roomPermissionStorage) {
        $this->roomStorage = $roomStorage;
        $this->userStorage = $userStorage;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $args->id));

        if (!isset($permissions[Permission::WRITE])) {
            return Error::make("forbidden");
        }

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? true;
        $args->cursor = $args->cursor ?? 0;

        $data = yield $this->roomStorage->getMembers($args->id, $args->cursor, $args->asc);
        $users = yield $this->userStorage->getByIds($data, $args->asc);

        return createPaginationResult($users);
    }
}