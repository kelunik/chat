<?php

namespace Kelunik\Chat\Commands;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;

class Me extends Command {
    public function execute(Request $request, User $user) {
        return new Data([
            "id" => $user->id,
            "name" => $user->name,
            "avatar" => $user->avatar,
        ]);
    }
}