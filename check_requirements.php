<?php

if (PHP_VERSION_ID < 70000) {
    throw new Exception("PHP 7 required!");
}

if (!function_exists("openssl_random_pseudo_bytes")) {
    throw new Exception("openssl_random_pseudo_bytes not available!");
}

$needed_constants = [
    "CSS_VERSION", "JS_VERSION",
    "DB_USER", "DB_PASS", "DB_HOST", "DB_DB",
    "DEPLOY_DOMAIN", "DEPLOY_PORT", "DEPLOY_HTTPS",
    "GITHUB_CLIENT_ID", "GITHUB_CLIENT_SECRET"
];

foreach ($needed_constants as $needle) {
    if (!defined($needle)) {
        if ($needle === "CSS_VERSION" || $needle === "JS_VERSION") {
            throw new Exception("deploy constant not specified, run php deploy/deploy.php");
        }

        throw new Exception("config constant not specified: " . $needle);
    }
}
