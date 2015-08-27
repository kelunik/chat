<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;

interface Subscription {
    public function watch(callable $callback): Subscription;

    public function when(callable $callback): Subscription;

    public function cancel(): Promise;
}