<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;

interface EventHub {
    public function publish(string $channel, string $event, $payload): Promise;

    public function subscribe(string $channel, callable $callback);
}