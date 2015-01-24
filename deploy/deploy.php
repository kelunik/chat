<?php

chdir(__DIR__);

print shell_exec("rm -rf ../gen/*");

print shell_exec("php deploy_css.php");

print shell_exec("php compile_templates.php");
print shell_exec("php deploy_js.php");

require(__DIR__ . "/../gen/version_css.php");
require(__DIR__ . "/../gen/version_js.php");

$appCache = file_get_contents(__DIR__ . "/../root/manifest.raw.appcache");
file_put_contents(__DIR__ . "/../root/manifest.appcache", $appCache . "\n# " . max(CSS_VERSION, JS_VERSION));
