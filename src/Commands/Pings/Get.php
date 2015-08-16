<?php

namespace Kelunik\Chat\Commands\Pings;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Kelunik\Chat\Command;
use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use stdClass;

class Get extends Command {
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

        $ping = yield $stmt->fetchObject();

        if ($ping) {
            return new Data([
                "seen" => (bool) $ping->seen
            ]);
        }

        return Error::make("not_found");
    }

    public function getPermissions() : array {
        return [];
    }
}