<?php

namespace App\Chat\Command\Messages;

use App\Chat\Command;
use App\Chat\MessageCrud;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use JsonSchema\Validator;
use stdClass;

class Get extends Command {
    private $messageCrud;

    public function __construct (Validator $validator, MessageCrud $messageCrud) {
        parent::__construct($validator);
        $this->messageCrud = $messageCrud;
    }

    public function execute (stdClass $args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

        $message = yield from $this->messageCrud->read($args->message_id, true);

        if ($message === null) {
            return Error::make("not_found");
        }

        return new Data($message);
    }

    public function getPermissions () : array {
        return ["read"];
    }
}