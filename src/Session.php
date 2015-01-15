<?php

namespace App;

use Amp\Reactor;
use Amp\Redis\Redis;
use Mysql\Pool;

class Session {
	private $db;
	private $redis;

	public function __construct (Pool $db, Reactor $reactor) {
		$this->db = $db;
		$this->redis = new Redis([
			"host" => "127.0.0.1:6380",
			"password" => REDIS_PASSWORD
		], $reactor);
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
