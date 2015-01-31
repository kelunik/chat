<?php

chdir(__DIR__);

print shell_exec("rm -rf ../gen/*");

print shell_exec(PHP_BINARY . " deploy_css.php");

print shell_exec(PHP_BINARY . " compile_templates.php");
print shell_exec(PHP_BINARY . " deploy_js.php");

require(__DIR__ . "/../gen/version_css.php");
require(__DIR__ . "/../gen/version_js.php");

$appCache = file_get_contents(__DIR__ . "/../root/manifest.raw.appcache");
file_put_contents(__DIR__ . "/../root/manifest.appcache", $appCache . "\n/css/all.min.css?v=" . CSS_VERSION . "\n/js/all.min.js?v=" . JS_VERSION);
