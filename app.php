<?php

use Aerys\Router;
use Amp\Artax\Client as ArtaxClient;
use Amp\Mysql\Pool;
use Amp\Redis\Client as RedisClient;
use Amp\Redis\SubscribeClient;
use App\Presentation\Auth;
use function Amp\reactor;

require __DIR__ . "/vendor/autoload.php";
require __DIR__ . "/general_config.php";
require __DIR__ . "/check_requirements.php";

$connect = sprintf("host=%s;user=%s;pass=%s;db=%s", DB_HOST, DB_USER, DB_PASS, DB_DB);
$db = new Pool($connect);

$redis = new RedisClient("tcp://127.0.0.1:6380", ["password" => REDIS_PASSWORD], reactor());
$pubSub = new SubscribeClient("tcp://127.0.0.1:6380", ["password" => REDIS_PASSWORD], reactor());
$artax = new ArtaxClient;

$authHandler = new Auth($db, $redis, $artax);

$router = (new Router)
    ->get("sign-in", [$authHandler, "logIn"])
    ->post("sign-in/{provider:github|stackexchange}", [$authHandler, "doLogInRedirect"])
    ->get("sign-in/{provider:github|stackexchange}", [$authHandler, "doLogIn"]);

$host = (new Aerys\Host)
    ->expose("*", 8080)
    ->use(Aerys\session(["driver" => new Aerys\Session\Redis(
        new Amp\Redis\Client("tcp://127.0.0.1:6379", [], reactor()),
        new Amp\Redis\Mutex("tcp://127.0.0.1:6379", [], reactor()),
        reactor()
    )]))
    ->use($router)
    ->use(new App\SecurityMiddleware("wss://" . DEPLOY_AUTHORITY));