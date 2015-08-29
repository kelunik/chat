<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Kelunik\Chat\createPaginationResult;

class History extends Command {
    private $messageStorage;
    private $userStorage;

    public function __construct(MessageStorage $messageStorage, UserStorage $userStorage) {
        $this->messageStorage = $messageStorage;
        $this->userStorage = $userStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? false;
        $args->cursor = $args->cursor ?? -1;

        $messages = yield $this->messageStorage->getHistory($args->id, $args->cursor, $args->asc);

        $userIds = array_column($messages, "user_id");
        $users = [];

        foreach (yield $this->userStorage->getByIds($userIds) as $user) {
            $users[$user->id] = $user;
        }

        foreach ($messages as &$message) {
            $message->user = $users[$message->user_id] ?? null;
            unset($message->user_id);
        }

        return createPaginationResult($messages);
    }

    public function getPermissions(): array {
        return ["read"];
    }
}