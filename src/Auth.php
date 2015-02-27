<?php

namespace App;

use Amp\Redis\Redis;
use Mysql\Pool;
use Parsedown;
use Tpl;

class Auth {
    private $db;
    private $redis;
    private $sessionManager;

    public function __construct (Pool $db, Redis $redis) {
        $this->db = $db;
        $this->redis = $redis;
        $this->sessionManager = new SessionManager($redis);
    }

    public function redirect () {
        yield "status" => 302;
        yield "header" => "Location: /login";
        yield "body" => "";
    }

    public function handleRequest ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId !== null) {
            $session = yield $this->sessionManager->getSession($sessionId);

            if ($session !== null) {
                yield "status" => 302;
                yield "header" => "Location: /rooms";
                yield "body" => "";
                return;
            }
        }

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "auth.php", Tpl::LOAD_PHP);

        yield "body" => $tpl->page();
    }

    public function handleGitHubRequest () {
        // FIXME: Security Issue
        // State isn't saved and compared yet
        // Use Security::generateSession <-- has to be renamed
        $state = str_shuffle(md5(microtime()));

        $data = [
            "client_id" => GITHUB_CLIENT_ID,
            "scope" => "user:email",
            "state" => $state
        ];

        $url = "https://github.com/login/oauth/authorize?";
        $url .= http_build_query($data);

        yield "status" => 302;
        yield "header" => "Location: {$url}";
        yield "body" => "";
    }

    public function handleGitHubCallbackRequest ($request) {
        if (!isset($request["QUERY"]["code"], $request["QUERY"]["state"])) {
            yield "status" => 400;
            yield "body" => "";

            return;
        }

        $api = new GithubApi(null);

        if (yield $api->fetchToken($request["QUERY"]["code"])) {
            $login = yield $this->getLoginData($api);
            list($id, $username, $mail, $avatar_url) = $login;

            $sessionId = Security::generateSession();
            $token = Security::generateToken();

            $sessionData = json_encode([
                "id" => $id,
                "name" => $username,
                "mail" => $mail,
                "avatar" => $avatar_url,
                "csrfToken" => $token
            ]);

            yield $this->redis->set("session.{$sessionId['server']}", $sessionData);

            yield "status" => 302;
            yield "header" => "Location: /rooms";
            yield "header" => ("Set-Cookie: aerys_sess=" . $sessionId["client"] . "; PATH=/; httpOnly" . (DEPLOY_HTTPS ? "; secure" : ""));
            yield "body" => "";
        } else {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }
    }

    private function getLoginData (GithubApi $api) {
        $result = yield $this->db->prepare("SELECT `id`, `name`, `mail`, `avatar_url` FROM `users` WHERE `github_token` = ?", [$api->getToken()]);

        if (yield $result->rowCount()) {
            yield $result->fetch();
        } else {
            $user = yield $api->queryUser();
            $result = yield $this->db->prepare("SELECT `id`, `name`, `mail`, `avatar_url` FROM `users` WHERE `name` = ?", [$user->login]);

            if (yield $result->rowCount()) {
                yield $this->db->prepare("UPDATE `users` SET `github_token` = ? WHERE `name` = ?", [$api->getToken(), $user->login]);
                yield $result->fetch();
            } else {
                $mail = yield $api->queryPrimaryMail();

                $result = yield $this->db->prepare("INSERT INTO `users` (`name`, `mail`, `github_token`, `avatar_url`) VALUES (?, ?, ?, ?)", [
                    $user->login, $mail, $api->getToken(), $user->avatar_url
                ]);

                if ($result) {
                    yield [$result->insertId, $user->login, $mail, $user->avatar_url];
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
