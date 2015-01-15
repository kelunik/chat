<?php

chdir(__DIR__ . "/../root");
error_reporting(E_ALL);
date_default_timezone_set("UTC");

require_once __DIR__ . "/../general_config.php";
require_once __DIR__ . "/../vendor/autoload.php";

$output = __DIR__ . "/../root/css/all.min.css";
$source_map_path = __DIR__ . "/../root/css/all.min.css.map";
$css = "";

foreach (explode(":", UI_CSS_FILES) as $file) {
	$css .= file_get_contents("css/{$file}");
}

$bef = $css;
$autoprefixer = new Autoprefixer("last 2 versions");
$css = $autoprefixer->compile($css);

$len = strlen($css);

$compressor = new CSSmin();
$compressor->set_memory_limit('256M');
$css .= $compressor->run($css);

print "compress css: " . $len / strlen($css) . "\n";

$before = md5(@file_get_contents($output));
$after = md5($css);

if ($after !== $before) {
	file_put_contents($output, $css);
}

$time = filemtime($output);
$config = "<?php const CSS_VERSION = {$time};";

file_put_contents(__DIR__ . '/../gen/version_css.php', $config);
