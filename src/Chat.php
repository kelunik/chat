<?php

namespace App;

use Aerys\Websocket;
use Amp\Redis\Redis;
use Mysql\Pool;

class Chat implements Websocket {
    private $roomHandler;
    private $sessionManager;
    private $db;

    public function __construct (Pool $db, Redis $redis, Redis $pubSubListener) {
        $this->roomHandler = new RoomHandler($db, $redis, $pubSubListener);
        $this->sessionManager = new SessionManager($redis);
        $this->db = $db;
    }

    public function onStart () {
        yield $this->roomHandler->init();
    }

    public function onStop () {
        yield $this->roomHandler->stop();
    }

    public function onOpen ($clientId, array $request) {
        $origin = $request["HTTP_ORIGIN"] ?? null;

        if ($origin !== DEPLOY_URL) {
            yield "status" => 403;
            yield "reason" => "Forbidden";
            yield "header" => "Access-Control-Allow-Origin: " . DEPLOY_URL;
            return;
        }

        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 401;
            return;
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 401;
            return;
        }

        yield $this->roomHandler->addClient($clientId, $sessionId, $session);
    }

    public function onData ($clientId, $payload) {
        $data = json_decode($payload);

        if ($data !== null) {
            yield $this->roomHandler->onMessage($clientId, $data);
        }
    }

    public function onClose ($clientId, $code, $reason) {
        yield $this->roomHandler->removeClient($clientId);
    }
}
