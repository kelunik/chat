<?php

namespace App;

use Mysql\Pool;
use Parsedown;
use Tpl;

class Settings {
	private $db;
	private $sessionManager;

	public function __construct (Pool $db) {
		$this->db = $db;
		$this->sessionManager = new SessionManager;
	}

	public function showSettings ($request) {
		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "settings.php", Tpl::LOAD_PHP);

		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId !== null) {
			$session = yield $this->sessionManager->getSession($sessionId, $this->db);
		}

		if ($sessionId === null || !isset($session)) {
			yield "status" => 401;
			yield "body" => "Please sign in and reload page afterwards.";

			return;
		}

		$q = yield $this->db->prepare("SELECT s.`key`, s.`default`, us.`value` FROM settings AS s LEFT JOIN (SELECT * FROM user_settings WHERE userId = ?) AS us ON (s.key = us.key)");
		$q = yield $q->execute([$session->id]);
		$data = yield $q->fetchObjects();

		$settings = [];

		foreach ($data as $row) {
			$settings[$row->key] = $row->value ?: $row->default;
		}

		$tpl->set('settings', $settings);
		$tpl->set('session', $session);
		yield "body" => $tpl->page();
	}

	public function saveSettings ($request) {
		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "settings.php", Tpl::LOAD_PHP);

		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId !== null) {
			$session = yield $this->sessionManager->getSession($sessionId, $this->db);
		}

		if ($sessionId === null || !isset($session)) {
			yield "status" => 401;
			yield "body" => "Please sign in and reload page afterwards.";

			return;
		}

		if (!isset($request["FORM"]["csrf-token"])) {
			yield "status" => 401;
			yield "body" => "";
			return;
		}

		$token = $request["FORM"]["csrf-token"];

		if (!is_string($token) || !safe_compare($session->csrfToken, $token)) {
			yield "status" => 401;
			yield "body" => "";
			return;
		}

		if(isset($request["FORM"]["mail_notifications"]) && is_string($request["FORM"]["mail_notifications"])) {
			$opts = ["default", "never"];

			if(in_array($request["FORM"]["mail_notifications"], $opts)) {
				$q = yield $this->db->prepare("REPLACE INTO user_settings (`userId`, `key`, `value`) VALUES (?, ?, ?)");
				$q->execute([$session->id, "MAIL_NOTIFICATIONS", $request["FORM"]["mail_notifications"]]);
			}
		}

		$q = yield $this->db->prepare("SELECT s.`key`, s.`default`, us.`value` FROM settings AS s LEFT JOIN (SELECT * FROM user_settings WHERE `userId` = ?) AS us ON (s.`key` = us.`key`)");
		$q = yield $q->execute([$session->id]);
		$data = yield $q->fetchObjects();

		$settings = [];

		foreach ($data as $row) {
			$settings[$row->key] = $row->value ?: $row->default;
		}

		$tpl->set('settings', $settings);
		$tpl->set('session', $session);
		yield "body" => $tpl->page();
	}
}
