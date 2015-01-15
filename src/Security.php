<?php

namespace App;

class Security {
	// http://chat.stackoverflow.com/transcript/message/20403562#20403562
	public static function generateSession () {
		$raw = openssl_random_pseudo_bytes(16);

		return [
			"client" => substr(base64_encode($raw), 0, -2),
			"server" => hash("sha256", $raw)
		];
	}

	public static function decodeSessionId ($sessionId) {
		return hash("sha256", base64_decode($sessionId));
	}
}
