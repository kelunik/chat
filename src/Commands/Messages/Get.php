<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use stdClass;

class Get extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(stdClass $args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

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