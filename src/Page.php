<?php

namespace App;

use Mysql\Pool;
use Parsedown;
use Tpl;

class Page {
	private $db;
	private $sessionManager;

	public function __construct (Pool $db) {
		$this->db = $db;
		$this->sessionManager = new SessionManager($db);
	}

	public function handleRequest ($request) {
		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId === null) {
			yield "status" => 303;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$session = yield $this->sessionManager->getSession($sessionId);

		if ($session === null) {
			yield "status" => 303;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "chat.php", Tpl::LOAD_PHP);
		$tpl->set('session', $session);
		yield "body" => $tpl->page();
	}
}
