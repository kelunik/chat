<?php

chdir(__DIR__);

print shell_exec("rm -rf ../gen/*");

print shell_exec("php deploy_css.php");

print shell_exec("php compile_templates.php");
print shell_exec("php deploy_js.php");