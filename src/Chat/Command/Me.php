<?php

namespace App\Chat\Command;

use App\Chat\Command;
use App\Chat\Response\Data;
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