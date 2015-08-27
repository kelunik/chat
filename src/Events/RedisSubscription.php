<?php

namespace Kelunik\Chat\Events;

use Amp\Promise;
use Amp\Redis\SubscribeClient;
use Throwable;

class RedisSubscription implements Subscription {
    /**
     * @var SubscribeClient
     */
    private $client;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var Promise
     */
    private $promise;

    /**
     * @var bool
     */
    private $promiseResolved;

    /**
     * @var mixed
     */
    private $resolution;

    /**
     * @var callable[]
     */
    private $watchs;

    /**
     * @var callable[]
     */
    private $whens;

    public function __construct(SubscribeClient $client, string $channel) {
        $this->client = $client;
        $this->channel = $channel;
        $this->watchs = [];
        $this->whens = [];

        $this->promise = $this->client->subscribe($channel);
        $this->promiseResolved = false;

        $this->promise->watch(function ($data) {
            $data = json_decode($data);

            foreach ($this->watchs as $watch) {
                $watch($data);
            }
        });

        $this->promise->when(function ($error, $data) {
            $this->resolution = $error ?? $data;
            $this->promiseResolved = true;

            foreach ($this->whens as $when) {
                $when($error, $data);
            }
        });
    }

    public function watch(callable $callback): Subscription {
        $this->watchs[] = $callback;

        return $this;
    }

    public function when(callable $callback): Subscription {
        if ($this->promiseResolved) {
            $args = $this->resolution instanceof Throwable ? [$this->resolution, null] : [null, $this->resolution];
            $callback(...$args);
        } else {
            $this->whens[] = $callback;
        }

        return $this;
    }

    public function cancel(): Promise {
        return $this->client->unsubscribe($this->channel);
    }
}