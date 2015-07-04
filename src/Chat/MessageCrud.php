<?php

namespace App\Chat;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use stdClass;

class MessageCrud {
    private $redis;
    private $mysql;

    public function __construct (Client $redis, Pool $mysql) {
        $this->redis = $redis;
        $this->mysql = $mysql;
    }

    public function create (stdClass $user, int $roomId, string $text) {
        $replyTo = $this->findReplyTo($text);
        $pings = [];

        if ($replyTo) {
            $reply = yield from $this->read($replyTo);

            if ($reply) {
                if ($reply->room_id !== $roomId) {
                    $reply = null;
                    $replyTo = 0;
                }

                $pings[] = $reply->user->id;
            }
        }

        $query = yield $this->mysql->prepare("INSERT INTO messages (`roomId`, `userId`, `text`, `replyTo`, `time`) VALUES (?, ?, ?, ?, ?)", [
            $roomId, $user->id, $text, $replyTo, $time = time()
        ]);

        $pings = array_unique(array_merge($pings, yield from $this->extractPings($text)));

        // remove self-pings
        if (false !== $key = array_search($user->id, $pings)) {
            unset($pings[$key]);
        }

        $payload = (object) [
            "message_id" => $query->insertId,
            "room_id" => $roomId,
            "text" => $text,
            "user" => (object) [
                "id" => $user->id,
                "name" => $user->name,
                "avatar" => $user->avatar,
            ],
            "reply" => $reply ?? null,
            "edit_time" => null,
            "time" => $time
        ];

        yield $this->redis->publish("chat:rooms:{$roomId}", json_encode([
            "type" => "message:new",
            "payload" => $payload,
        ]));

        yield from $this->sendPingNotifications($user, $query->insertId, $roomId, $pings);

        return $payload;
    }

    public function read (int $messageId, bool $expandReply = false) {
        $result = yield $this->mysql->prepare(
            <<<SQL
    SELECT m.`id`, m.`roomId`, u.`id` userId, u.`name` userName, u.`avatar` userAvatar, m.`type`, m.`text`, m.`data`, m.`replyTo`, m.`editTime`, m.`time`
    FROM messages m, users u
    WHERE u.id = m.userId && m.id = ?
SQL
            , [$messageId]
        );

        $message = yield $result->fetchObject();

        if (!$message) {
            return null;
        }

        if ($expandReply && $message->replyTo) {
            $reply = yield from $this->read($message->replyTo);
        }

        return (object) [
            "message_id" => $message->id,
            "room_id" => $message->roomId,
            "type" => $message->type,
            "text" => $message->text,
            "data" => $message->data,
            "user" => (object) [
                "id" => $message->userId,
                "name" => $message->userName,
                "avatar" => $message->userAvatar,
            ],
            "reply" => $reply ?? null,
            "edit_time" => $message->editTime,
            "time" => $message->time
        ];
    }

    public function update (stdClass $user, int $messageId, string $text) {
        $currentMessage = yield from $this->read($messageId);

        if (!$currentMessage) {
            throw new Command\Exception("message_not_found");
        }

        if ($currentMessage->user->id !== $user->id) {
            throw new Command\Exception("wrong_user");
        }

        if ($currentMessage->time < time() - 5 * 60) {
            throw new Command\Exception("message_too_old");
        }

        $replyTo = $this->findReplyTo($text);
        $pings = [];

        if ($replyTo) {
            $reply = yield from $this->read($replyTo);

            if ($reply) {
                if ($reply->room_id !== $currentMessage->room_id) {
                    $reply = null;
                    $replyTo = 0;
                }

                $pings[] = $reply->user->id;
            }
        }

        yield $this->mysql->prepare("UPDATE messages SET `text` = ?, `data` = NULL, `replyTo` = ?, `editTime` = ? WHERE `id` = ?", [
            $text, $replyTo, $time = time(), $messageId
        ]);

        $pings = array_unique(array_merge($pings, yield from $this->extractPings($text)));

        // remove self-pings
        if (false !== $key = array_search($user->id, $pings)) {
            unset($pings[$key]);
        }

        $payload = (object) [
            "message_id" => $messageId,
            "room_id" => $currentMessage->room_id,
            "text" => $text,
            "user" => (object) [
                "id" => $user->id,
                "name" => $user->name,
                "avatar" => $user->avatar,
            ],
            "reply" => $reply ?? null,
            "edit_time" => $time,
            "time" => $currentMessage->time
        ];

        yield $this->redis->publish("chat:rooms:{$currentMessage->message_id}", json_encode([
            "type" => "message:edit",
            "payload" => $payload
        ]));

        yield from $this->sendPingNotifications($user, $messageId, $currentMessage->room_id, $pings);

        return $payload;
    }

    public function delete (int $id, int $authorId) {
        // TODO implement
    }

    private function extractPings (string $text) {
        $pattern = "~\\b@([a-z][a-z0-9-]*)\\b~i";
        $users = [];

        // remove code blocks, we don't want code to ping people
        $text = preg_replace("~(`(?:``)?)([^`]+?)(\1)~", "", $text);

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $users[$match[1]] = true;
            }
        }

        if ($count = count($users)) {
            $pings = [];

            $where = str_repeat(" || u.name = ?", $count);
            $result = yield $this->mysql->prepare("SELECT u.id FROM users u, room_users ru WHERE u.id = ru.userId AND ru.roomId = ? AND (0 {$where})",
                array_merge([$roomId], array_keys($users))
            );

            foreach (yield $result->fetchAll() as list($id)) {
                $pings[] = $id;
            }

            return $pings;
        }

        return [];
    }

    private function findReplyTo ($text): int {
        if (preg_match("~^:(\\d+)\\b~", $text, $match)) {
            return (int) $match[1];
        }

        // use 0 as no-reply value, because it's no valid ID
        // and works nicely with if ($replyTo) { ... }
        return 0;
    }

    private function sendPingNotifications (stdClass $user, int $messageId, int $roomId, array $pings) {
        if (count($pings)) {
            $data = [];

            foreach ($pings as $ping) {
                $data[] = $messageId;
                $data[] = $ping;
            }

            $where = substr(str_repeat(", (?, ?)", count($pings)), 2);
            yield $this->mysql->prepare("INSERT IGNORE INTO `pings` (`messageId`, `userId`) VALUES {$where}", $data);

            foreach ($pings as $ping) {
                yield $this->redis->publish("chat.user", json_encode([
                    "userId" => $ping,
                    "type" => "ping",
                    "payload" => [
                        "messageId" => $messageId,
                        "roomId" => $roomId,
                        "user" => [
                            "id" => $user->id,
                            "name" => $user->name,
                            "avatar" => $user->avatar,
                        ],
                    ],
                ]));
            }
        }
    }
}