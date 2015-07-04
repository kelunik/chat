<?php

namespace App\Chat\Command;

use App\Chat\Command;

class Me extends Command {
    public function execute ($args, $payload) {
        return [
            "id" => $args->user_id,
            "name" => $args->user_name,
            "avatar" => $args->user_avatar,
        ];
    }
}