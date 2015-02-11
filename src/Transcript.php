<?php

namespace App;

use Amp\Redis\Redis;
use DateTime;
use Mysql\Pool;
use Parsedown;
use Tpl;

class Transcript {
    private $db;
    private $sessionManager;

    public function __construct (Pool $db, Redis $redis) {
        $this->db = $db;
        $this->sessionManager = new SessionManager($redis);
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

        $q = yield $this->db->prepare(MessageHandler::buildQuery("roomId = ? && time >= ? && time < ?"), [$roomId, $start, $end, isset($session) ? $session->id : -1]);

        $messages = [];

        $md = new Parsedown;
        $md->setMarkupEscaped(true);

        foreach (yield $q->fetchObjects() as $message) {
            $message->messageText = htmlspecialchars($message->text); // $md->text($message->text);
            $messages[] = $message;
        }

        $tpl->set("messages", $messages);
        yield "body" => $tpl->page();
    }

    public function handleMessageRequest ($request) {
        $messageId = $request["URI_ROUTE_ARGS"]["id"];

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "transcript.php", Tpl::LOAD_PHP);

        $q = yield $this->db->prepare("SELECT roomId FROM messages WHERE id = ?", [$messageId]);
        $message = yield $q->fetchObject();

        if(!$message) {
            yield "status" => 404;
            yield "body" => "";
            return;
        }

        $q = yield $this->db->prepare("SELECT m.id, m.roomId, m.userId, u.name AS userName, u.avatar_url AS userAvatar, m.text, m.edited, m.time, (SELECT COUNT(1) FROM `message_stars` AS ms WHERE ms.messageId = m.id) AS stars FROM `messages` AS m, `users` AS u, `room_users` AS ru, (SELECT `id`, `time` FROM messages WHERE id = ?) AS parent WHERE m.userId = ru.userId && ru.userId = u.id && m.roomId = ru.roomId && m.time > parent.time - 1440 && m.time < parent.time + 1440 && m.roomId = ? ORDER BY m.id ASC", [
            $messageId, $message->roomId
        ]);

        $messages = [];

        foreach (yield $q->fetchObjects() as $message) {
            $message->messageText = htmlspecialchars($message->text); // (new Parsedown())->parse($message->text);
            $messages[] = $message;
        }

        $tpl->set("messages", $messages);
        yield "body" => $tpl->page();
    }

    public function messageJson ($request) {
        $messageId = $request["URI_ROUTE_ARGS"]["id"];

        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId !== null) {
            $session = yield $this->sessionManager->getSession($sessionId);
        }

        $q = yield $this->db->prepare(MessageHandler::buildQuery("id = ?"), [$messageId, isset($session) ? $session->id : -1]);
        $message = yield $q->fetchObject();

        if ($message === null) {
            yield "status" => 404;
            yield "header" => "Content-Type: application/json";
            yield "body" => "null";
        }

        $message = [
            "id" => $message->id,
            "roomId" => $message->roomId,
            "user" => [
                "id" => $message->userId,
                "name" => $message->userName,
                "avatar" => $message->userAvatar
            ],
            "text" => $message->text,
            "edited" => (bool) $message->edited,
            "time" => $message->time,
            "stars" => (int) $message->stars,
            "starred" => (bool) $message->starred,
            "reply" => $message->replyMessageId ? [
                "messageId" => $message->replyMessageId,
                "user" => [
                    "id" => $message->replyUserId,
                    "name" => $message->replyUserName
                ]
            ] : null
        ];

        $indent = isset($request["QUERY"]["pretty"]) ? JSON_PRETTY_PRINT : 0;

        yield "header" => "Content-Type: application/json";
        yield "body" => json_encode($message, $indent);
    }
}
