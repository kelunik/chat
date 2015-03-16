<?php

error_reporting(E_ALL);

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../general_config.php";

use Amp\Redis\Redis;
use App\Log\FileDump;
use function Amp\run;

run(function ($reactor) {
    $client = new Redis(["host" => "localhost:6380", "password" => REDIS_PASSWORD], $reactor);
    $dumper = new FileDump($client, __DIR__ . "/../logs/app.log");
    yield $dumper->run();
});
