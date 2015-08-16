<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\Response;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\MessageCrud;
use Kelunik\Chat\Boundaries\Data;
use stdClass;

class Create extends Command {
    private $messageCrud;

    public function __construct(MessageCrud $messageCrud) {
        $this->messageCrud = $messageCrud;
    }

    public function execute(Request $request, User $user): Response {
        $payload = $request->getPayload();

        if ($user->id < 0) {
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