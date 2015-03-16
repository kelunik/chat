<?php

namespace App;

use Amp\Redis\Redis;
use Mysql\Pool;

class Session {
    private $db;
    private $redis;

    public function __construct (Pool $db, Redis $redis) {
        $this->db = $db;
        $this->redis = $redis;
    }

    public function getStatus ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 401;
            yield "body" => "";

            return;
        }

        $exists = yield $this->redis->exists("session.{$sessionId}");

        yield "status" => $exists ? 200 : 401;
        yield "body" => "";
    }
}
