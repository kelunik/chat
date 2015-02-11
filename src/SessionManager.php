<?php

namespace App;

use Amp\Redis\Redis;

class SessionManager {
    private $redis;

    public function __construct (Redis $redis) {
        $this->redis = $redis;
    }

    public static function getSessionId ($request) {
        if (!isset($request["HTTP_COOKIE"])) {
            return null;
        }

        if (preg_match("~aerys_sess=([a-z0-9/+]+)~i", $request["HTTP_COOKIE"], $match)) {
            return Security::decodeSessionId($match[1]);
        } else {
            return null;
        }
    }

    public function getSession ($sessionId) {
        yield json_decode(yield $this->redis->get("session.{$sessionId}"));
    }
}
