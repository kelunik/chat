<?php

chdir(__DIR__ . "/..");

foreach (glob("html/*.handlebars") as $input) {
	$output = "gen/" . substr($input, 5) . ".js";
	shell_exec("handlebars {$input} -f {$output}");
}