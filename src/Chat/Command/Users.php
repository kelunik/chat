<?php

namespace App\Chat\Command;

use App\Chat\Command;
use stdClass;

class Users extends Command {
    public function execute (stdClass $args, $payload) {
        // TODO: Implement execute() method.
    }

    public function getPermissions () : array {
        return [];
    }
}