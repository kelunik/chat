<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Amp\resolve;

class Get extends Command {
    private $messageStorage;
    private $userStorage;
    private $roomPermissionStorage;

    public function __construct(MessageStorage $messageStorage, UserStorage $userStorage, RoomPermissionStorage $roomPermissionStorage) {
        $this->messageStorage = $messageStorage;
        $this->userStorage = $userStorage;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $payload->room_id));

        if (!isset($permissions[Permission::READ])) {
            return Error::make("forbidden");
        }

        $message = yield $this->messageStorage->get($args->id);

        if ($message === null) {
            return Error::make("not_found");
        }

        $message->user = yield $this->userStorage->get($message->user_id);
        unset($message->user_id);

        return new Data($message);
    }
}