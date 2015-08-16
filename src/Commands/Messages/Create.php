<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;
use Kelunik\Chat\Boundaries\Data;
use stdClass;

class Create extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(stdClass $args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

        if ($args->user_id < 0) {
            $type = $payload->type ?? "text";
        } else {
            $type = "text";
        }

        $insertedMessage = yield \Amp\resolve($this->messageCrud->create($user, $payload->room_id, $payload->text, $type));

        return new Data($insertedMessage);
    }

    public function getPermissions() : array {
        return ["write"];
    }
}