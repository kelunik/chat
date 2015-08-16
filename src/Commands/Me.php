<?php

namespace Kelunik\Chat\Commands;

use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use stdClass;

class Me extends Command {
    public function execute (stdClass $args, $payload) {
        return new Data([
            "id" => $args->user_id,
            "name" => $args->user_name,
            "avatar" => $args->user_avatar,
        ]);
    }

    public function getPermissions () : array {
        return [];
    }
}