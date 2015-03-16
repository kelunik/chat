<?php

use Aerys\Root\Root;
use Amp\Redis\Redis;
use App\Auth;
use App\Chat;
use App\Log\Logger;
use App\Page;
use App\Search;
use App\Session;
use App\Settings;
use App\Transcript;
use Mysql\Pool;

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/general_config.php";
require __DIR__ . "/gen/version_css.php";
require __DIR__ . "/gen/version_js.php";
require __DIR__ . "/check_requirements.php";

$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);
$tpl = new Tpl(new Parsedown);
$redis = new Redis([
    "host" => "127.0.0.1:6380",
    "password" => REDIS_PASSWORD
], \Amp\getReactor());
$pubSub = new Redis([
    "host" => "127.0.0.1:6380",
    "password" => REDIS_PASSWORD
], \Amp\getReactor());

$logger = new Logger($redis);
$authHandler = new Auth($db, $redis, $logger);
$chatHandler = new Chat($db, $redis, $pubSub);
$pageHandler = new Page($db, $redis);
$transcriptHandler = new Transcript($db, $redis);
$sessionHandler = new Session($db, $redis);
$settingsHandler = new Settings($db, $redis);
$searchHandler = new Search($db, $redis);

$host = (new Aerys\Host)
    ->setPort(DEPLOY_PORT)
    ->setName(DEPLOY_DOMAIN)
    ->setRoot(__DIR__ . "/root", [
        Root::OP_MIME_TYPES => [
            "js" => "text/javascript",
        ],
        Root::OP_EXPIRES_PERIOD => 3600 * 24 * 14,
        Root::OP_AGGRESSIVE_CACHE_HEADER_ENABLED => true,
        Root::OP_AGGRESSIVE_CACHE_MULTIPLIER => 0.75
    ])
    ->addRoute("GET", "/", [$authHandler, "redirect"])
    ->addRoute("GET", "/auth", [$authHandler, "redirect"])
    ->addRoute("GET", "/login", [$authHandler, "handleRequest"])
    ->addRoute("GET", "/login/github", [$authHandler, "handleGitHubRequest"])
    ->addRoute("GET", "/oauth/github", [$authHandler, "handleGitHubCallbackRequest"])
    ->addRoute("POST", "/logout", [$authHandler, "handleLogout"])
    ->addRoute("GET", "/rooms", [$pageHandler, "roomOverview"])
    ->addRoute("GET", "/rooms/new", [$pageHandler, "createRoom"])
    ->addRoute("POST", "/rooms/new", [$pageHandler, "createRoomSubmit"])
    ->addRoute("GET", "/rooms/{id:[0-9]+}/leave", [$pageHandler, "leaveRoom"])
    ->addRoute("POST", "/rooms/{id:[0-9]+}/leave", [$pageHandler, "leaveRoomSubmit"])
    ->addRoute("GET", "/rooms/{id:[0-9]+}", [$pageHandler, "handleRequest"])
    ->addRoute("GET", "/messages/{id:[0-9]+}", [$transcriptHandler, "handleMessageRequest"])
    ->addRoute("GET", "/messages/{id:[0-9]+}.json", [$transcriptHandler, "messageJson"])
    ->addRoute("GET", "/settings", [$settingsHandler, "showSettings"])
    ->addRoute("POST", "/settings", [$settingsHandler, "saveSettings"])
    ->addRoute("GET", "/search/rooms", [$searchHandler, "rooms"])
    ->addRoute("GET", "/session/status", [$sessionHandler, "getStatus"])
    ->addRoute("GET", "/transcripts/{id:[0-9]+}/{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}", [$transcriptHandler, "handleRequest"])
    ->addWebsocket("/chat", $chatHandler);

if (DEPLOY_HTTPS) {
    $host->setCrypto(DEPLOY_HTTPS_CERT, [
        "ciphers" => DEPLOY_HTTPS_SUITES
    ]);
    $port = defined("DEPLOY_HTTPS_REDIRECT_PORT") ? DEPLOY_HTTPS_REDIRECT_PORT : 80;
    $redirect = "https://" . DEPLOY_DOMAIN;
    $redirect .= DEPLOY_PORT === 443 ? "" : ":" . DEPLOY_PORT;
    (new Aerys\Host)->setPort($port)->setName(DEPLOY_DOMAIN)->redirectTo($redirect);
}

if (defined("HOST_DOCS")) {
    (new Aerys\Host)
        ->setName(HOST_DOCS)
        ->setRoot(__DIR__ . "/vendor/amphp/aerys/doc");
}
