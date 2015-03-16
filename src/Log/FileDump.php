<?php

namespace App\Log;

use Amp\Redis\Client;
use Exception;
use InvalidArgumentException;

class FileDump {
    private $client;
    private $filename;
    private $key;

    public function __construct (Client $client, $filename, $key = "log-queue") {
        if (!is_string($filename)) {
            throw new InvalidArgumentException(sprintf(
                "filename must be string, %s given",
                gettype($filename)
            ));
        }

        if (!is_writable(dirname($filename))) {
            throw new InvalidArgumentException("specified file is not writable");
        }

        if (!is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "key must be string, %s given",
                gettype($key)
            ));
        }

        $this->client = $client;
        $this->filename = $filename;
        $this->key = $key;
    }

    public function run () {
        $handle = fopen($this->filename, "a");

        while (true) {
            try {
                $data = yield $this->client->blPop($this->key);
                $data = json_decode($data[1]);

                fputs($handle, sprintf("[%s] [%s] [%s] %s", date("c", $data->time), $data->type, $data->tag, $data->message));
                fputs($handle, "\n");
            } catch (Exception $e) {
                print (string) $e;
                exit(1);
            }
        }
    }
}
