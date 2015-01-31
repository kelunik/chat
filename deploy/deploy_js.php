<?php

chdir(__DIR__ . "/../root");
error_reporting(E_ALL);
date_default_timezone_set("UTC");

require_once __DIR__ . "/../general_config.php";

$output = __DIR__ . "/../root/js/all.min.js";
$source_map_path = __DIR__ . "/../root/all.min.js.map";
$cmd = $js = "";

foreach (explode(":", UI_JS_FILES_EXTERNAL) as $file) {
	$cmd .= " --js='js/{$file}'";
	$js .= file_get_contents("js/{$file}") . "\n";
}

foreach (glob("../gen/*.handlebars.js") as $input) {
	$cmd .= " --js='{$input}'";
	$js .= file_get_contents($input) . "\n";
}

foreach (explode(":", UI_JS_FILES) as $file) {
	$cmd .= " --js='js/{$file}'";
	$js .= file_get_contents("js/{$file}") . "\n";
}

if (!DEVELOPMENT) {
	$js = shell_exec("java -jar ../deploy/compiler.jar --create_source_map {$source_map_path} --source_map_format=V3 --warning_level QUIET --charset UTF-8 --language_in ECMASCRIPT5 {$cmd}");
	$js = "//# sourceMappingURL=/all.min.js.map\n{$js}";
}

$before = md5(@file_get_contents($output));
$after = md5($js);

if ($after !== $before) {
	file_put_contents($output, $js);
}

$time = filemtime($output);
$config = "<?php const JS_VERSION = {$time};";

file_put_contents(__DIR__ . '/../gen/version_js.php', $config);
