<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;

class Edit extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();
        $payload = $request->getPayload();

        $editedMessage = yield \Amp\resolve($this->messageCrud->update($user, $args->message_id, $payload->text));

        return new Data($editedMessage);
    }

    public function getPermissions() : array {
        return ["write"];
    }
}