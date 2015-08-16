<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;
use Kelunik\Chat\Boundaries\Data;
use stdClass;

class Edit extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(stdClass $args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

        $editedMessage = yield \Amp\resolve($this->messageCrud->update($user, $args->message_id, $payload->text));

        return new Data($editedMessage);
    }

    public function getPermissions() : array {
        return ["write"];
    }
}