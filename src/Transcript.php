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

        try {
            $start = (new DateTime("{$year}-{$month}-{$day}"))->getTimestamp();
        } catch (\Exception $e) {
            yield "status" => 400;
            yield "body" => "<h1>bad request - error while parsing date</h1>";
            return;
        }

        $end = $start + 86400;

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "transcript.php", Tpl::LOAD_PHP);

        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId !== null) {
            $session = yield $this->sessionManager->getSession($sessionId);
        }

        $data = [$roomId, $start, $end, isset($session) ? $session->id : -1];
        $q = yield $this->db->prepare(MessageHandler::buildQuery("roomId = ? && time >= ? && time < ?"), $data);

        $messages = [];

        foreach (yield $q->fetchAll() as $message) {
            $messages[] = $this->createMessage($message, true);
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

        if (!$message) {
            yield "status" => 404;
            yield "body" => "";
            return;
        }

        $q = yield $this->db->prepare("SELECT m.id, m.roomId, m.userId, u.name AS userName, u.githubId AS userAvatar, m.text, m.edited, m.time, (SELECT COUNT(1) FROM `message_stars` AS ms WHERE ms.messageId = m.id) AS stars FROM `messages` AS m, `users` AS u, `room_users` AS ru, (SELECT `id`, `time` FROM messages WHERE id = ?) AS parent WHERE m.userId = ru.userId && ru.userId = u.id && m.roomId = ru.roomId && m.time > parent.time - 1440 && m.time < parent.time + 1440 && m.roomId = ? ORDER BY m.id ASC", [
            $messageId, $message->roomId
        ]);

        $messages = [];

        foreach (yield $q->fetchAll() as $message) {
            $message = array_merge($message, ["0", null, "0", ""]);
            $messages[] = $this->createMessage($message, true);
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

    private function createMessage ($message, $seen = false) {
        list($messageId, $roomId, $userId, $userName, $userAvatar, $text, $edit, $time, $stars, $starred, $replyTo, $replyToUserId, $replyToUserName) = $message;

        return [
            "messageId" => $messageId,
            "roomId" => $roomId,
            "messageText" => $text,
            "user" => [
                "id" => $userId,
                "name" => $userName,
                "avatar" => $userAvatar
            ],
            "stars" => (int) $stars,
            "starred" => (boolean) $starred,
            "time" => $time,
            "edit-time" => $edit > 0 ? $edit : null,
            "seen" => $seen,
            "reply" => isset($replyTo) ? [
                "messageId" => (int) $replyTo,
                "user" => [
                    "id" => (int) $replyToUserId,
                    "name" => $replyToUserName
                ]
            ] : null
        ];
    }
}
