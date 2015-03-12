<?php

use Mysql\Pool;
use function Amp\all;
use function Amp\run;
use function Amp\stop;

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../general_config.php";

$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);

$run = function () use ($db) {
    $update = yield $db->prepare("UPDATE rooms SET messageCount = ? WHERE id = ?");
    $q = yield $db->query("SELECT roomId, COUNT(*) AS messageCount FROM messages GROUP BY roomId");
    $rooms = yield $q->fetchObjects();

    $promises = [];

    foreach ($rooms as $room) {
        $promises[] = $update->execute([$room->messageCount, $room->roomId]);
    }

    yield all($promises);

    stop();
};

print "cron start up @ " . date("c") . "\n";

run($run);

print "cron shut down @ " . date("c") . "\n";
