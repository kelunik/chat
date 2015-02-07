<?php

chdir(__DIR__ . "/../root/js");
error_reporting(E_ALL);
date_default_timezone_set("UTC");

$output = __DIR__ . "/../root/js/all.min.js";

print shell_exec("npm run build");

$time = filemtime($output);
$config = "<?php const JS_VERSION = {$time};";

file_put_contents(__DIR__ . "/../gen/version_js.php", $config);
