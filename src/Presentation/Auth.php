<?php

namespace App;

use Amp\Artax\Client as Artax;
use Amp\Redis\Client as Redis;
use App\Auth\OAuth\GitHub;
use App\Auth\OAuth\StackExchange;
use App\Log\Logger;
use App\Security\OAuthCsrfToken;
use App\Session\SessionInterface;
use LogicException;
use Mysql\Pool;
use Parsedown;
use RandomLib\Generator;
use Tpl;

class Auth {
    private $db;
    private $artax;
    private $redis;
    private $logger;
    private $sessionManager;
    private $generator;

    public function __construct (Pool $db, Redis $redis, Artax $artax, Logger $logger, SessionManager $sessionManager, Generator $generator) {
        $this->db = $db;
        $this->artax = $artax;
        $this->redis = $redis;
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->generator = $generator;
    }

    public function logIn ($request) {
        /** @var SessionInterface $session */
        $session = yield from $this->sessionManager->get($request);

        if ($session->get("loggedIn")) {
            return [
                "status" => 302,
                "header" => [
                    "Location: /rooms",
                    $session->getCookieHeader()
                ]
            ];
        }

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "auth.php", Tpl::LOAD_PHP);

        return [
            "body" => $tpl->page(),
            "header" => $session->getCookieHeader()
        ];
    }

    public function doLogIn ($request) {
        /** @var SessionInterface $session */
        $session = yield from $this->sessionManager->get($request);

        if ($session->get("loggedIn")) {
            return [
                "status" => 302,
                "header" => [
                    "Location: /rooms",
                    $session->getCookieHeader()
                ]
            ];
        }

        $providerString = $request["URI_ROUTE_ARGS"]["provider"];

        switch ($providerString) {
            case "github":
                $provider = new GitHub($this->artax);
                break;
            case "stackexchange":
                $provider = new StackExchange($this->artax);
                break;
            default:
                throw new LogicException("unknown provider: " . $providerString);
        }

        if (!isset($request["QUERY"]["code"])) {
            return [
                "status" => 400
            ];
        }

        $token = new OAuthCsrfToken($this->generator, $session);

        if (!$token->validate($request["QUERY"]["state"] ?? "")) {
            return [
                "status" => 400,
                "header" => $session->getCookieHeader()
            ];
        }

        $accessToken = yield from $provider->processAuthorizeResponse($request["QUERY"]["code"]);

        return [
            "body" => "not available..."
        ];
    }

    private function getLoginData (GithubApi $api) {
        $result = yield $this->db->prepare("SELECT `id`, `name`, `mail`, `githubId` FROM `users` WHERE `githubToken` = ?", [$api->getToken()]);

        if (yield $result->rowCount()) {
            yield $result->fetch();
        } else {
            $user = yield $api->queryUser();
            $result = yield $this->db->prepare("SELECT `id`, `name`, `mail`, `githubId` FROM `users` WHERE `githubId` = ?", [$user->id]);

            if (yield $result->rowCount()) {
                yield $this->db->prepare("UPDATE `users` SET `githubToken` = ? WHERE `githubId` = ?", [$api->getToken(), $user->id]);
                yield $result->fetch();
            } else {
                $mail = yield $api->queryPrimaryMail();

                $result = yield $this->db->prepare("INSERT INTO `users` (`name`, `mail`, `githubToken`, `githubId`) VALUES (?, ?, ?, ?)", [
                    $user->login, $mail, $api->getToken(), $user->id
                ]);

                if ($result) {
                    yield [$result->insertId, $user->login, $mail, $user->id];
                } else {
                    error_log("Couldn't insert new user: {$user->login} / {$mail}");
                }
            }
        }
    }

    public function handleLogout ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session && isset($request["FORM"]["csrf-token"])) {
            $token = $request["FORM"]["csrf-token"];

            if (is_string($token) && safe_compare($session->csrfToken, $token)) {
                yield $this->redis->publish("chat.session", json_encode([
                    "sessionId" => $sessionId,
                    "type" => "logout",
                    "payload" => "",
                ]));
                yield $this->redis->del("session.{$sessionId}");
                yield "header" => ("Set-Cookie: aerys_sess=; PATH=/; httpOnly" . (DEPLOY_HTTPS ? "; secure" : "") . "; EXPIRES=" . gmdate("D, d M Y H:i:s T", 0));
                yield "status" => 302;
                yield "header" => "Location: /login";
                yield "body" => "";
                return;
            }
        }

        yield "status" => 401;
        yield "body" => "";
    }
}
