<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Amp\resolve;
use function Kelunik\Chat\createPaginationResult;

class History extends Command {
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

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $args->id));

        if (!isset($permissions[Permission::READ])) {
            return Error::make("forbidden");
        }

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? false;
        $args->cursor = $args->cursor ?? -1;

        $messages = yield $this->messageStorage->getHistory($args->id, $args->cursor, $args->asc);

        $userIds = array_column($messages, "user_id");
        $users = [];

        foreach (yield $this->userStorage->getByIds($userIds) as $user) {
            $users[$user->id] = $user;
        }

        $defaultUser = (object) [
            "id" => 0,
            "name" => "anonymous",
            "avatar" => null,
        ];

        foreach ($messages as &$message) {
            $message->user = $users[$message->user_id] ?? $defaultUser;

            unset($message->user_id);
        }

        return createPaginationResult($messages);
    }
}