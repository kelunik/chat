<?php

namespace App\Chat\Command\Messages;

use App\Chat\Command;
use App\Chat\MessageCrud;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use App\ChatAuthorization;
use JsonSchema\Validator;
use stdClass;

class Edit extends Command {
    private $messageCrud;
    private $authorization;

    public function __construct (Validator $validator, MessageCrud $messageCrud, ChatAuthorization $authorization) {
        parent::__construct($validator);
        $this->messageCrud = $messageCrud;
        $this->authorization = $authorization;
    }

    public function execute (stdClass $args, $payload) {
        $user = new stdClass;
        $user->id = $args->user_id;
        $user->name = $args->user_name;
        $user->avatar = $args->user_avatar;

        try {
            $editedMessage = yield from $this->messageCrud->update($user, $args->message_id, $payload->text);

            return new Data($editedMessage);
        } catch (Command\Exception $e) {
            return Error::make("bad_request");
        }
    }

    public function getPermissions () : array {
        return ["write"];
    }
}