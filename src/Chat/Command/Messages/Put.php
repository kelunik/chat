<?php

namespace App\Chat\Command\Messages;

use App\Chat\Command;
use App\Chat\MessageCrud;
use JsonSchema\Validator;
use stdClass;

class Put extends Command {
    private $messageCrud;

    public function __construct (Validator $validator, MessageCrud $messageCrud) {
        parent::__construct($validator);
        $this->messageCrud = $messageCrud;
    }

    public function execute ($args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

        return yield from $this->messageCrud->create($user, $payload->room_id, $payload->text);
    }
}