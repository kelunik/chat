<?php

namespace Kelunik\Chat\Boundaries;

use stdClass;

class StandardRequest implements Request {
    private $uri;
    private $args;
    private $payload;

    public function __construct(string $uri, stdClass $args, $payload) {
        $this->uri = $uri;
        $this->args = $args;
        $this->payload = $payload;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getArgs(): stdClass {
        return $this->args;
    }

    public function getPayload() {
        return $this->payload;
    }
}