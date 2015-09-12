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

class Get extends Command {
    private $roomStorage;
    private $roomPermissionStorage;

    public function __construct(RoomStorage $roomStorage, RoomPermissionStorage $roomPermissionStorage) {
        $this->roomStorage = $roomStorage;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $args->id));

        if (!isset($permissions[Permission::READ])) {
            return Error::make("forbidden");
        }

        $room = yield $this->roomStorage->get($args->id);

        if ($room) {
            return new Data($room);
        }

        return Error::make("not_found");
    }
}