<?php

namespace App\Log;

use Amp\Redis\Client;
use Exception;
use InvalidArgumentException;

class Logger {
    private $client;
    private $key;

    public function __construct (Client $client, $key = "log-queue") {
        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "key must be string, %s given",
                gettype($key)
            ));
        }

        $this->client = $client;
        $this->key = $key;
    }

    private function log ($type, $tag, $message) {
        if (!is_string($type)) {
            throw new InvalidArgumentException(sprintf(
                "message must be string, %s given",
                gettype($message)
            ));
        }

        if (!is_string($message)) {
            throw new InvalidArgumentException(sprintf(
                "message must be string, %s given",
                gettype($message)
            ));
        }

        $this->client->rPush($this->key, json_encode([
            "type" => $type,
            "tag" => $tag,
            "message" => $message,
            "time" => time()
        ]));
    }

    public function debug ($tag, $message) {
        $this->log("DEBUG", $tag, $message);
    }

    public function info ($tag, $message) {
        $this->log("INFO", $tag, $message);
    }

    public function warning ($tag, $message) {
        $this->log("WARNING", $tag, $message);
    }

    public function error ($tag, $message) {
        $this->log("ERROR", $tag, $message);
    }

    public function exception ($tag, Exception $exception) {
        $this->log("EXCEPTION", $tag, (string) $exception);
    }
}
