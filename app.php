<?php

use App\Auth;
use App\Chat;
use App\Page;
use App\Session;
use App\Settings;
use App\Transcript;
use Mysql\Pool;

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/general_config.php";
require __DIR__ . "/gen/version_css.php";
require __DIR__ . "/gen/version_js.php";
require __DIR__ . "/check_requirements.php";

/* --- Global server options here --------------------------------------------------------------- */

const KEEP_ALIVE_TIMEOUT = 30;

/* --- http://localhost:8080/ or http://127.0.0.1:8080/  (all IPv4 interfaces) ------------------ */

$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);
$tpl = new Tpl(new Parsedown);
$authHandler = new Auth($db, $tpl);
$chatHandler = new Chat($db, \Amp\getReactor());
$pageHandler = new Page($db);
$transcriptHandler = new Transcript($db);
$sessionHandler = new Session($db, \Amp\getReactor());
$settingsHandler = new Settings($db);

$host = (new Aerys\Host(DEPLOY_DOMAIN))
	->setPort(DEPLOY_PORT)
	->setRoot(__DIR__ . "/root", ["mimeTypes" => ["js" => "text/mime"]])
	->addRoute("GET", "/rooms/{id:[0-9]+}", [$pageHandler, "handleRequest"])
	->addRoute("GET", "/rooms/{id:[0-9]+}/transcript/{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}", [$transcriptHandler, "handleRequest"])
	->addRoute("GET", "/message/{id:[0-9]+}", [$transcriptHandler, "handleMessageRequest"])
	->addRoute("GET", "/auth", [$authHandler, "handleRequest"])
	->addRoute("GET", "/auth/github", [$authHandler, "handleGitHubRequest"])
	->addRoute("GET", "/oauth/github", [$authHandler, "handleGitHubCallbackRequest"])
	->addRoute("GET", "/logout", [$authHandler, "handleLogout"])
	->addRoute("GET", "/settings", [$settingsHandler, "showSettings"])
	->addRoute("POST", "/settings", [$settingsHandler, "saveSettings"])
	->addRoute("GET", "/session/status", [$sessionHandler, "getStatus"])
	->addWebsocket("/chat", $chatHandler);
