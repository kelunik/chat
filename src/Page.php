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
		$this->sessionManager = new SessionManager;
	}

	public function handleRequest ($request) {
		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId === null) {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$session = yield $this->sessionManager->getSession($sessionId);

		if ($session === null) {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "chat.php", Tpl::LOAD_PHP);
		$tpl->set('session', $session);
		yield "body" => $tpl->page();
	}

	public function roomOverview ($request) {
		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId === null) {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$session = yield $this->sessionManager->getSession($sessionId);

		if ($session === null) {
			yield "status" => 302;
			yield "header" => "Location: /auth";
			yield "body" => "";
			return;
		}

		$q = yield $this->db->query("SELECT * FROM rooms");
		$rooms = yield $q->fetchObjects();

		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "rooms.php", Tpl::LOAD_PHP);
		$tpl->set('rooms', $rooms);
		$tpl->set('session', $session);
		yield "body" => $tpl->page();
	}
}
