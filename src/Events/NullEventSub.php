<?php

namespace Kelunik\Chat\Events;

class NullEventSub implements EventSub {
    public function subscribe(string $channel): Subscription {
        return new NullEventSub;
    }

    public function getConnectionState(): int {
        return ConnectionState::CONNECTED;
    }
}