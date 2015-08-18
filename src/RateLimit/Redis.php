<?php

namespace Kelunik\Chat\RateLimit;

use Amp\Redis\Client;
use function Amp\pipe;

class Redis implements RateLimit {
    private $redis;
    private $ttl;

    public function __construct(Client $redis, int $ttl) {
        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    public function get(string $id) {
        $count = yield $this->redis->get($key);

        return (int) $count;
    }

    public function increment(string $id) {
        $count = yield $this->redis->incr($key);

        if ($count === 1) {
            yield $this->redis->expire($id, $this->ttl);
        }

        return $count;
    }

    public function ttl(string $id) {
        $ttl = yield $this->redis->ttl($id);

        if ($ttl < 0) {
            return $this->ttl;
        } else {
            return $ttl;
        }
    }
}