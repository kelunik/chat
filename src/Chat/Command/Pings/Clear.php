<?php

namespace App\Chat\Command\Pings;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use App\Chat\Command;
use App\Chat\Response\Data;
use App\Chat\Response\Error;
use JsonSchema\Validator;
use stdClass;

class Clear extends Command {
    private $mysql;
    private $redis;

    public function __construct(Validator $validator, Pool $mysql, Client $redis) {
        parent::__construct($validator);
        $this->mysql = $mysql;
        $this->redis = $redis;
    }

    public function execute(stdClass $args, $payload) {
        $stmt = yield $this->mysql->prepare("SELECT seen FROM ping WHERE user_id = ? && message_id = ?", [
            $args->user_id, $args->message_id
        ]);

        $ping = yield $stmt->fetchRow();

        if (isset($ping["seen"])) {
            if (!$ping["seen"]) {
                $stmt = yield $this->mysql->prepare("UPDATE ping SET seen = 1 WHERE user_id = ? && message_id = ?", [
                    $args->user_id, $args->message_id
                ]);

                yield $this->redis->publish("chat:user:{$args->user_id}", json_encode([
                    "type" => "ping:clear",
                    "payload" => [
                        "message_id" => $args->message_id,
                    ]
                ]));

                return new Data(null, 204); // no content
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