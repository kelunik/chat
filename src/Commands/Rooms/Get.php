<?php

namespace Kelunik\Chat\Commands\Rooms;

use Amp\Mysql\Pool;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\Response;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use stdClass;

class Get extends Command {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function execute(Request $request, User $user): Response {
        $args = $request->getArgs();

        $stmt = yield $this->mysql->prepare("SELECT `id`, `name`, `description` FROM `room` WHERE `id` = ?", [
            $args->room_id
        ]);

        $room = yield $stmt->fetchObject();

        if ($room) {
            return new Data($room);
        }

        return Error::make("not_found");
    }

    public function getPermissions () : array {
        return ["read"];
    }
}