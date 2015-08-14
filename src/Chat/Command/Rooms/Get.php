<?php

namespace App\Chat\Command\Rooms;

use App\Chat\Command;
use stdClass;

class Get extends Command {
    public function execute (stdClass $args, $payload) {
        // TODO: Implement execute() method.
    }

    public function getPermissions () : array {
        return ["read"];
    }
}