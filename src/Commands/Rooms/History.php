<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\MessageStorage;
use function Kelunik\Chat\createPaginationResult;

class History extends Command {
    private $messageStorage;

    public function __construct(MessageStorage $messageStorage) {
        $this->messageStorage = $messageStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        // set default values, because there's no support for them in our JSON schema library currently.
        $args->asc = $args->asc ?? false;
        $args->cursor = $args->cursor ?? -1;

        $messages = yield $this->messageStorage->getHistory($args->id, $args->cursor, $args->asc);

        return createPaginationResult($messages);
    }

    public function getPermissions(): array {
        return ["read"];
    }
}