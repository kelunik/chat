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

$manifest = file_get_contents(__DIR__ . "/root/manifest.appcache");
$manifestResponder = function () use ($manifest) {
	return [
		"header" => DEVELOPMENT ? [] : [
			"Content-Type: text/cache-manifest; charset=utf-8",
			"Cache-Control: no-cache, no-store, must-revalidate",
			"Pragma: no-cache",
			"Expires: 0",
		],
		"status" => DEVELOPMENT ? 410 : 200,
		"body" => DEVELOPMENT ? "" : $manifest
	];
};


$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);
$tpl = new Tpl(new Parsedown);
$authHandler = new Auth($db, $tpl);
$chatHandler = new Chat($db, \Amp\getReactor());
$pageHandler = new Page($db);
$transcriptHandler = new Transcript($db);
$sessionHandler = new Session($db, \Amp\getReactor());
$settingsHandler = new Settings($db);

$host = (new Aerys\Host)
	->setPort(DEPLOY_PORT)
	->setName(DEPLOY_DOMAIN)
	->setRoot(__DIR__ . "/root", ["mimeTypes" => ["js" => "text/javascript", "appcache" => "text/cache-manifest"]])
	->addRoute("GET", "/rooms/{id:[0-9]+}", [$pageHandler, "handleRequest"])
	->addRoute("GET", "/rooms/{id:[0-9]+}/transcript/{year:[0-9]+}/{month:[0-9]+}/{day:[0-9]+}", [$transcriptHandler, "handleRequest"])
	->addRoute("GET", "/message/{id:[0-9]+}", [$transcriptHandler, "handleMessageRequest"])
	->addRoute("GET", "/", [$authHandler, "redirect"])
	->addRoute("GET", "/auth", [$authHandler, "handleRequest"])
	->addRoute("GET", "/auth/github", [$authHandler, "handleGitHubRequest"])
	->addRoute("GET", "/oauth/github", [$authHandler, "handleGitHubCallbackRequest"])
	->addRoute("POST", "/logout", [$authHandler, "handleLogout"])
	->addRoute("GET", "/settings", [$settingsHandler, "showSettings"])
	->addRoute("POST", "/settings", [$settingsHandler, "saveSettings"])
	->addRoute("GET", "/session/status", [$sessionHandler, "getStatus"])
	->addRoute("GET", "/manifest.appcache", $manifestResponder)
	->addWebsocket("/chat", $chatHandler);

if (DEPLOY_HTTPS) {
	$host->setCrypto(DEPLOY_HTTPS_CERT, [
		"ciphers" => DEPLOY_HTTPS_SUITES
	]);
	$port = defined("DEPLOY_HTTPS_REDIRECT_PORT") ? DEPLOY_HTTPS_REDIRECT_PORT : 80;
	$redirect = "https://" . DEPLOY_DOMAIN;
	$redirect .= DEPLOY_PORT === 433 ? "" : ":" . DEPLOY_PORT;
	(new Aerys\Host)->setPort($port)->setName(DEPLOY_DOMAIN)->redirectTo($redirect);
}
