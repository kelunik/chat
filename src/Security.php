<?php

namespace App;

class Security {
    // http://chat.stackoverflow.com/transcript/message/20403562#20403562
    public static function generateSession () {
        $raw = openssl_random_pseudo_bytes(32);

        return [
            "client" => base64_encode($raw),
            "server" => hash("sha256", $raw)
        ];
    }

    public static function decodeSessionId ($sessionId) {
        return hash("sha256", base64_decode($sessionId));
    }

    public static function generateToken () {
        $raw = openssl_random_pseudo_bytes(32);
        return base64_encode($raw);
    }
}
