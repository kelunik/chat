<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;

class Get extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $message = yield \Amp\resolve($this->messageCrud->read($args->message_id, true));

        if ($message === null) {
            return Error::make("not_found");
        }

        return new Data($message);
    }

    public function getPermissions() : array {
        return ["read"];
    }
}