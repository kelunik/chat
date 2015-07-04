<?php

namespace App\Chat\Command\Ping;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use App\Chat\Command;
use JsonSchema\Validator;

class Delete extends Command {
    private $mysql;
    private $redis;

    public function __construct (Validator $validator, Pool $mysql, Client $redis) {
        parent::__construct($validator);
        $this->mysql = $mysql;
        $this->redis = $redis;
    }

    public function execute ($args, $payload) {
        yield $this->mysql->prepare("UPDATE pings SET seen = 1 WHERE userId = ? && messageId = ?", [
            $args->user_id, $args->message_id
        ]);

        yield $this->redis->publish("chat.user", json_encode([
            "userId" => $args->user_id,
            "type" => "ping-clear",
            "payload" => [
                "messageId" => $args->message_id,
            ]
        ]));
    }
}