<?php

namespace App;

use Amp\Redis\Client;
use App\Session\Session;
use App\Session\SessionNotFoundException;
use RandomLib\Generator;

class SessionManager {
    private $generator;
    private $redis;

    public function __construct (Generator $generator, Client $redis) {
        $this->generator = $generator;
        $this->redis = $redis;
    }

    public function get ($request) {
        if (isset($request["HTTP_COOKIE"]) && preg_match("~aerys_sess=([a-z0-9/.]{64})~i", $request["HTTP_COOKIE"], $m)) {
            $sessionId = $m[1];
        } else {
            $sessionId = $this->generator->generateString(64);
        }

        $session = new Session($sessionId, $this->redis);

        try {
            yield $session->load();
        } catch (SessionNotFoundException $e) {
            $session = new Session($this->generator->generateString(64), $this->redis);
        }

        return $session;
    }
}
