<?php

namespace Kelunik\Chat\Commands\Pings;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Events\EventHub;
use Kelunik\Chat\Storage\PingStorage;

class Edit extends Command {
    private $pingStorage;
    private $eventHub;

    public function __construct(PingStorage $pingStorage, EventHub $eventHub) {
        $this->pingStorage = $pingStorage;
        $this->eventHub = $eventHub;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();
        $payload = $request->getPayload();

        $ping = yield $this->pingStorage->get($user->id, $args->id);

        if (!$ping) {
            return Error::make("not_found");
        }

        $changed = yield $this->pingStorage->update($user->id, $args->id, $payload->seen);

        if ($changed) {
            $this->eventHub->publish("chat:user:{$user->id}", $payload->seen ? "ping/remove" : "ping/add", ["id" => $args->id]);

            return new Data([
                "seen" => $payload->seen,
            ]);
        } else {
            return new Data(null, 304); // not modified
        }
    }

    public function getPermissions() : array {
        return [];
    }
}