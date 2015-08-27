<?php

namespace Kelunik\Chat\Events;

use Amp\Redis\SubscribeClient;

class RedisEventSub implements EventSub {
    private $client;

    public function __construct(SubscribeClient $client) {
        $this->client = $client;
    }

    public function subscribe(string $channel): Subscription {
        return new RedisSubscription($this->client, $channel);
    }
}