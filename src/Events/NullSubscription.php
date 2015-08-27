<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;
use Amp\Success;

class NullSubscription implements Subscription {
    public function watch(callable $callback): Subscription {
        return $this;
    }

    public function when(callable $callback): Subscription {
        return $this;
    }

    public function cancel(): Promise {
        return new Success;
    }
}