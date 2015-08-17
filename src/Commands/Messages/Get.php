<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\UserStorage;

class Get extends Command {
    private $messageStorage;
    private $userStorage;

    public function __construct(MessageStorage $messageStorage, UserStorage $userStorage) {
        $this->messageStorage = $messageStorage;
        $this->userStorage = $userStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $message = yield $this->messageStorage->get($args->id);

        if ($message === null) {
            return Error::make("not_found");
        }

        $message->user = yield $this->userStorage->get($message->user_id);
        unset($message->user_id);

        return new Data($message);
    }

    public function getPermissions() : array {
        return ["read"];
    }
}