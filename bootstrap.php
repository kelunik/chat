<?php

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use Amp\Artax\Client as Artax;
use Amp\Mysql\Pool as MySQL;
use Amp\Redis\Client as Redis;
use Amp\Redis\SubscribeClient;
use App\Chat;
use App\Presentation\Auth;
use Kelunik\Template\Cache;
use Kelunik\Template\Template;
use Kelunik\Template\TemplateService;
use function Aerys\root;
use function Aerys\router;
use function Aerys\session;
use function Amp\reactor;

$artax = new Artax;

$templateService = new TemplateService(new Cache);
$templateService->setBaseDirectory(__DIR__ . "/html");

$mysql = new MySQL(sprintf(
    "host=%s;user=%s;pass=%s;db=%s",
    DB_HOST, DB_USER, DB_PASS, DB_DB
));

$redis = new Redis(REDIS_URI);
$redisSubscribe = new SubscribeClient("tcp://127.0.0.1:6380");

$auth = new Auth($mysql, $redis, $artax, $templateService);

$router = router()
    ->get("", function (Request $req, Response $resp) use ($templateService) {
        $session = yield (new Session($req))->read();

        if ($session->get("login")) {
            $template = $templateService->load(__DIR__ . "/public/index.html", Template::LOAD_RAW);
            $resp->send($template->render());
        } else {
            $template = $templateService->load("main.php");
            $resp->send($template->render());
        }
    })
    ->get("login", [$auth, "logIn"])
    ->post("login/{provider:github|stack-exchange}", [$auth, "doLogInRedirect"])
    ->get("login/{provider:github|stack-exchange}", [$auth, "doLogIn"])
    ->get("join", [$auth, "join"])
    ->post("join", [$auth, "doJoin"])
    ->post("logout", [$auth, "doLogOut"]);

$router->get("ws", Aerys\websocket(new Chat($mysql, $redis, $redisSubscribe, new Chat\MessageCrud($redis, $mysql))));

$host = (new Aerys\Host)
    ->expose("*", DEPLOY_PORT)
    ->name("localhost")
    ->use($router)
    ->use(root(__DIR__ . "/public", ["fallback" => __DIR__ . "/public/index.html"]));

// Sessions
$host->use(session(["driver" => new Aerys\Session\Redis(
    new Amp\Redis\Client(REDIS_URI),
    new Amp\Redis\Mutex(REDIS_URI, []),
    reactor()
)]));

// CSP and other security related headers
$host->use(new App\SecurityMiddleware("wss://" . DEPLOY_AUTHORITY . " wss://dev.kelunik.com"));

$api = (new Aerys\Host)
    ->expose("*", DEPLOY_PORT + 30)
    ->name("api.localhost")
    ->use(require "api.php");

$api->use(session(["driver" => new Aerys\Session\Redis(
    new Amp\Redis\Client(REDIS_URI),
    new Amp\Redis\Mutex(REDIS_URI, []),
    reactor()
)]));