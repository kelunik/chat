<?php

namespace App\Presentation;

use Aerys\Request;
use Aerys\Response;
use Aerys\Session;
use Amp\Artax\Client as Artax;
use Amp\Mysql\Pool;
use Amp\Redis\Client as Redis;
use App\Auth\OAuth\GitHub;
use App\Auth\OAuth\OAuthException;
use App\Auth\OAuth\StackExchange;
use App\GitHubApi;
use LogicException;

class Auth {
    private $db;
    private $artax;
    private $redis;

    public function __construct (Pool $db, Redis $redis, Artax $artax) {
        $this->db = $db;
        $this->artax = $artax;
        $this->redis = $redis;
    }

    public function logIn (Request $request, Response $response) {
        /** @var Session $session */
        $session = yield (new Session($request))->read();

        if ($session->get("login")) {
            $response->setStatus(302);
            $response->setHeader("location", "/rooms");
            $response->send("");

            return;
        }

        $response->send("<h2>Login</h2><form action='/sign-in/github' method='post'><button type='submit'>github</button></form>");
    }

    public function doLogInRedirect (Request $request, Response $response, array $args) {
        $session = yield (new Session($request))->open();

        $token = base64_encode(random_bytes(24));
        $session->set("token:oauth", $token);

        yield $session->save();

        $provider = $this->getProviderFromString($args["provider"]);
        $url = $provider->getAuthorizeRedirectUrl($token);

        $response->setStatus(302);
        $response->setHeader("location", $url);
        $response->send("");
    }

    public function doLogIn (Request $request, Response $response, array $args) {
        $session = yield (new Session($request))->read();

        $provider = $this->getProviderFromString($args["provider"]);
        $token = $session->get("token:oauth");

        $get = $request->getQueryVars();

        $code = isset($get["code"]) && is_string($get["code"]) ? $get["code"] : "";
        $state = isset($get["state"]) && is_string($get["state"]) ? $get["state"] : "";

        if (empty($code) || empty($state) || empty($token) || !hash_equals($token, $state)) {
            $response->setStatus(400);
            $response->setHeader("aerys-generic-response", "enable");
            $response->send("");

            return;
        }

        try {
            $accessToken = yield from $provider->getAccessTokenFromCode($code);
        } catch (OAuthException $e) {
            $response->setStatus(403);
            $response->setHeader("aerys-generic-response", "enable");
            $response->send("");

            return;
        }

        $identity = yield from $provider->getIdentity($accessToken);

        if (!$identity) {
            $response->setStatus(403);
            $response->setHeader("aerys-generic-response", "enable");
            $response->send("");

            return;
        }

        $query = yield $this->db->prepare("SELECT userId FROM oauth WHERE provider = ? AND identity = ?", [
            $args["provider"], $identity
        ]);

        $response->setStatus(302);
        $user = yield $query->fetchObject();
        yield $session->open();

        if ($user) {
            $session->set("login", $user->userId);
            $session->set("loginTime", time());
            $response->setHeader("location", "/");
        } else {
            $session->set("auth:provider", $args["provider"]);
            $session->set("auth:identity", $identity);
            $response->setHeader("location", "/sign-up");
        }

        $response->send("");
    }

    private function getProviderFromString (string $provider) {
        switch ($provider) {
            case "github":
                return new GitHub($this->artax, new GitHubApi($this->artax));
            case "stackexchange":
                return new StackExchange($this->artax);
            default:
                throw new LogicException("unknown provider: " . $provider);
        }
    }

    /* public function doLogOut ($request) {
        $session = yield from $this->sessionManager->get($request);

        $token = new CsrfToken($this->generator, $session);

        if ($token->validate($request["FORM"]["csrf-token"] ?? "")) {
            $sessionId = $session->getId();

            yield $this->redis->publish("chat.session", json_encode([
                "sessionId" => $sessionId,
                "type" => "logout",
                "payload" => "",
            ]));

            yield $this->redis->del("session.{$sessionId}");

            return [
                "status" => 302,
                "header" => [
                    "Location: /login",
                    $session->getCookieHeader()
                ],
            ];
        }

        return [
            "status" => 401
        ];
    } */
}
