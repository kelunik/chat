<?php

namespace App;

use Amp\Redis\Redis;
use Exception;
use Mysql\Pool;
use Parsedown;
use Tpl;

class Auth {
	private $db;
	private $redis;
	private $sessionManager;

	public function __construct (Pool $db) {
		$this->db = $db;
		$this->redis = new Redis([
			"host" => "127.0.0.1:6380",
			"password" => REDIS_PASSWORD
		]);
		$this->sessionManager = new SessionManager;
	}

	public function redirect () {
		yield "status" => 302;
		yield "header" => "Location: /auth";
		yield "body" => "";
	}

	public function handleRequest () {
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
			yield "header" => "Location: /rooms/1";
			yield "header" => ("Set-Cookie: sess=" . $sessionId["client"] . "; PATH=/");
			yield "body" => "";
		} else {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
		}
	}

	private function getLoginData (GithubApi $api) {
		$result = yield $this->db->prepare("SELECT `id`, `name`, `mail`, `avatar_url` FROM `users` WHERE `github_token` = ?", [$api->getToken()]);

		if (yield $result->rowCount()) {
			yield $result->fetch();
		} else {
			try {
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
						yield;
					}
				}
			} catch (Exception $e) {
				yield;
			}
		}
	}

	public function handleLogout ($request) {
		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId === null) {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
		}

		$session = yield $this->sessionManager->getSession($sessionId);

		if ($session && isset($request["FORM"]["csrf-token"])) {
			$token = $request["FORM"]["csrf-token"];

			if (is_string($token) && safe_compare($session->csrfToken, $token)) {
				yield $this->redis->del("session.{$sessionId}");
				yield "header" => "Set-Cookie: sess=; PATH=/"; // TODO: Add negative expire
				yield "status" => 302;
				yield "header" => "Location: /auth";
				yield "body" => "";
				return;
			}
		}

		yield "status" => 401;
		yield "body" => "";
	}
}
