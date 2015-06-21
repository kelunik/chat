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
use Kelunik\Template\TemplateService;
use LogicException;

class Auth {
    private $db;
    private $artax;
    private $redis;
    private $templateService;

    public function __construct (Pool $db, Redis $redis, Artax $artax, TemplateService $templateService) {
        $this->db = $db;
        $this->artax = $artax;
        $this->redis = $redis;
        $this->templateService = $templateService;
    }

    public function logIn (Request $request, Response $response) {
        $session = yield (new Session($request))->read();

        if ($session->get("login")) {
            $response->setStatus(302);
            $response->setHeader("location", "/rooms");
            $response->send("");

            return;
        }

        $template = $this->templateService->load(PHP_TEMPLATE_DIR . "/auth.php");
        $response->send($template->render());
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
            $args["provider"], $identity["id"]
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
            $session->set("auth:identity:id", $identity["id"]);
            $session->set("auth:identity:name", $identity["name"]);
            $response->setHeader("location", "/sign-up");
        }

        $response->send("");
        yield $session->save();
    }

    public function signUp (Request $request, Response $response) {
        $session = yield (new Session($request))->read();
        $template = $this->templateService->load(PHP_TEMPLATE_DIR . "/sign-up.php");
        $template->set("hint", $session->get("auth:identity:name") ?? "");
        $response->send($template->render());
    }

    public function doSignUp (Request $request, Response $response) {
        $session = yield (new Session($request))->open();
        parse_str(yield $request->getBody(), $post);

        $username = isset($post["username"]) && is_string($post["username"]) ? $post["username"] : "";
        $provider = $session->get("auth:provider");

        if (!$provider) {
            $response->setStatus(400);
            $response->setHeader("aerys-generic-response", "enable");
            $response->send("");

            return;
        }

        if (!preg_match("~^[a-z][a-z0-9-]+[a-z0-9]$~i", $username)) {
            $template = $this->templateService->load(PHP_TEMPLATE_DIR . "/sign-up.php");
            $template->set("hint", $username);
            $template->set("error", "username begin with a-z and only contain a-z, 0-9 or dashes afterwards");
            $response->send($template->render());

            return;
        }

        $query = yield $this->db->prepare("INSERT IGNORE INTO users (username) VALUES (?)", [
            $username
        ]);

        if ($query->affectedRows) {
            yield $this->db->prepare("INSERT INTO oauth (userId, provider, identity, label) VALUES (?, ?, ?, ?)", [
                $query->insertId, $provider, $session->get("auth:identity:id"), $session->get("auth:identity:name")
            ]);

            $response->setStatus(302);
            $response->setHeader("location", "/");
            $response->send("");
        } else {
            $template = $this->templateService->load(PHP_TEMPLATE_DIR . "/sign-up.php");
            $template->set("hint", $username);
            $template->set("error", "username already taken");
            $response->send($template->render());
        }

        yield $session->save();
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
