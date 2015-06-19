<?php

namespace App;

use Aerys\Middleware;
use Aerys\Options;
use Aerys\InternalRequest;

class SecurityMiddleware implements Middleware {
    private $webSocketSrc;

    public function __construct ($webSocketSrc) {
        $this->webSocketSrc = $webSocketSrc;
    }

    public function do (InternalRequest $request) {
        $headers = yield;

        $headers["x-frame-options"] = ["SAMEORIGIN"];
        $headers["x-xss-protection"] = ["1; mode=block"];
        $headers["x-ua-compatible"] = ["IE=Edge,chrome=1"];
        $headers["x-content-type-options"] = ["nosniff"];
        $headers["content-security-policy"] = [
            "default-src 'self'; " .
            "img-src 'self' data: *.github.com *.gravatar.com *.google-analytics.com; " .
            "connect-src: 'self' {$this->webSocketSrc} *.google-analytics.com; " .
            "frame-ancestors 'self'; " .
            "object-src 'none'"
        ];

        return $headers;
    }
}