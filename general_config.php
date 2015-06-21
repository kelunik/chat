<?php

require __DIR__ . "/config/config.php";

define("PHP_TEMPLATE_DIR", __DIR__ . "/html");

define("DEPLOY_AUTHORITY", DEPLOY_DOMAIN . (DEPLOY_PORT === 80 || DEPLOY_PORT === 443 ? "" : ":" . DEPLOY_PORT));
define("DEPLOY_URL", (DEPLOY_HTTPS ? "https" : "http") . "://" . DEPLOY_AUTHORITY);

define("TEMPLATE_DIR", __DIR__ . DIRECTORY_SEPARATOR . "html" . DIRECTORY_SEPARATOR);
define("PROJECT_ROOT", __DIR__ . DIRECTORY_SEPARATOR);

define("GIT_COMMIT_ID", `git rev-parse --short HEAD`);
