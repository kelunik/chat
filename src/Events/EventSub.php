<?php

namespace Kelunik\Chat\Events;

interface EventSub {
    public function subscribe(string $channel): Subscription;
}