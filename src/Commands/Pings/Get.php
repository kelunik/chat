<?php

namespace Kelunik\Chat\Commands\Pings;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Storage\PingStorage;

class Get extends Command {
    private $pingStorage;

    public function __construct(PingStorage $pingStorage) {
        $this->pingStorage = $pingStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $ping = yield $this->pingStorage->get($user->id, $args->id);

        if ($ping) {
            return new Data([
                "seen" => (bool) $ping->seen,
            ]);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return [];
    }
}