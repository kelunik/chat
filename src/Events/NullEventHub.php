<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;
use Amp\Success;

class NullEventHub implements EventHub {
    public function publish(string $channel, string $event, $payload): Promise {
        return new Success;
    }

    public function subscribe(string $channel, callable $callback) {
        // intentionally left blank...
    }
}