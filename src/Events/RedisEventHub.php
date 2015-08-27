<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;
use Amp\Redis\Client;

class RedisEventHub implements EventHub {
    private $client;

    public function __construct(Client $client) {
        $this->client = $client;
    }

    public function publish(string $channel, string $event, $payload): Promise {
        return $this->client->publish($channel, json_encode([
            "event" => $event,
            "payload" => $payload,
        ]));
    }
}