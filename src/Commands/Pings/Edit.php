<?php

namespace Kelunik\Chat\Commands\Pings;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use stdClass;

class Edit extends Command {
    private $mysql;
    private $redis;

    public function __construct(Pool $mysql, Client $redis) {
        $this->mysql = $mysql;
        $this->redis = $redis;
    }

    public function execute(stdClass $args, $payload) {
        $stmt = yield $this->mysql->prepare("SELECT seen FROM ping WHERE user_id = ? && message_id = ?", [
            $args->user_id, $args->message_id
        ]);

        $ping = yield $stmt->fetch();

        if (isset($ping["seen"])) {
            $stmt = yield $this->mysql->prepare("UPDATE ping SET seen = ? WHERE user_id = ? && message_id = ?", [
                $payload->seen, $args->user_id, $args->message_id
            ]);

            if ($stmt->affectedRows === 1) {
                yield $this->redis->publish("chat:user:{$args->user_id}", json_encode([
                    "type" => $payload->seen ? "ping:remove" : "ping:add",
                    "payload" => [
                        "message_id" => $args->message_id,
                    ]
                ]));

                return new Data([
                    "seen" => $payload->seen
                ]);
            } else {
                return new Data(null, 304); // not modified
            }
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return [];
    }
}