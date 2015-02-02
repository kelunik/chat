<?php

namespace App;

use DateTime;
use Mysql\Pool;
use Parsedown;
use Tpl;

class Transcript {
	private $db;
	private $sessionManager;

	public function __construct (Pool $db) {
		$this->db = $db;
		$this->sessionManager = new SessionManager;
	}

	public function handleRequest ($request) {
		$roomId = $request["URI_ROUTE_ARGS"]["id"];
		$year = $request["URI_ROUTE_ARGS"]["year"];
		$month = $request["URI_ROUTE_ARGS"]["month"];
		$day = $request["URI_ROUTE_ARGS"]["day"];

		$start = (new DateTime("{$year}-{$month}-{$day}"))->getTimestamp();
		$end = $start + 86400;

		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "transcript.php", Tpl::LOAD_PHP);

		$sessionId = SessionManager::getSessionId($request);

		if ($sessionId !== null) {
			$session = yield $this->sessionManager->getSession($sessionId);
		}

		$q = (yield $this->db->prepare(MessageHandler::buildQuery("time >= ? && time < ?"), [$roomId, $start, $end, isset($session) ? $session->id : -1]));

		$messages = [];

		$md = new Parsedown;
		$md->setMarkupEscaped(true);

		foreach (yield $q->fetchObjects() as $message) {
			$message->messageText = $md->text($message->text);
			$messages[] = $message;
		}

		$tpl->set("messages", $messages);
		yield "body" => $tpl->page();
	}

	public function handleMessageRequest ($request) {
		$messageId = $request["URI_ROUTE_ARGS"]["id"];

		$tpl = new Tpl(new Parsedown);
		$tpl->load(TEMPLATE_DIR . "transcript.php", Tpl::LOAD_PHP);

		$q = yield $this->db->prepare("SELECT m.id, m.roomId, m.userId, u.name AS userName, u.avatar_url AS userAvatar, m.text, m.edited, m.time, (SELECT COUNT(1) FROM `message_stars` AS ms WHERE ms.messageId = m.id) AS stars FROM `messages` AS m, `users` AS u, `room_users` AS ru, (SELECT `id`, `time` FROM messages WHERE id = ?) AS parent WHERE m.userId = ru.userId && ru.userId = u.id && m.roomId = ru.roomId && m.time > parent.time - 1440 && m.time < parent.time + 1440 ORDER BY m.id ASC", [$messageId]);

		$messages = [];

		foreach (yield $q->fetchObjects() as $message) {
			$message->messageText = (new Parsedown())->parse($message->text);
			$messages[] = $message;
		}

		$tpl->set("messages", $messages);
		yield "body" => $tpl->page();
	}
}
