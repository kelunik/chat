<?php

namespace App\Session;

use Amp\Future;
use Amp\Redis\Client;

class Session implements SessionInterface {
    private $id;
    private $data;
    private $redis;

    public function __construct ($id, Client $redis) {
        $this->id = $id;
        $this->data = [];
        $this->redis = $redis;
    }

    public function load () {
        $promise = new Future;

        $this->redis->get("session.{$this->id}")->when(function ($error, $result) use ($promise) {
            if ($error) {
                $promise->fail($error);
            } else if ($result === null) {
                $promise->fail(new SessionNotFoundException);
            } else {
                $this->data = json_decode($result);
            }
        });

        return $promise;
    }

    public function getCookieHeader () {
        return "Set-Cookie: user_session=" . $this->id . "; PATH=/; httpOnly" . (DEPLOY_HTTPS ? "; secure" : "");
    }

    public function get ($key) {
        return $this->data[$key] ?? null;
    }

    public function has ($key) {
        return isset($this->data[$key]);
    }

    public function set ($key, $value) {
        $this->data[$key] = $value;
    }

    public function __destruct () {
        $this->redis->set("session.{$this->id}", json_encode($this->data), 3600);
    }
}
