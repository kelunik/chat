<?php

namespace App\Chat\Command\Messages;

use App\Chat\Command;
use App\Chat\MessageCrud;
use App\Chat\Response\Data;
use App\ChatAuthorization;
use JsonSchema\Validator;
use stdClass;

class Create extends Command {
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

        if ($args->user_id < 0) {
            $type = $payload->type ?? "text";
        } else {
            $type = "text";
        }

        $insertedMessage = yield from $this->messageCrud->create($user, $payload->room_id, $payload->text, $type);

        return new Data($insertedMessage);
    }

    public function getPermissions () : array {
        return ["write"];
    }
}