<?php

namespace Kelunik\Chat\RateLimit;

interface RateLimit {
    public function get(string $id);

    public function increment(string $id);

    public function ttl(string $id);
}