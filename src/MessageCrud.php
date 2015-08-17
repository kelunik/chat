<?php

namespace Kelunik\Chat;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Exception;
use Generator;
use stdClass;

class MessageCrud {
    private $redis;
    private $mysql;

    public function __construct (Client $redis, Pool $mysql) {
        $this->redis = $redis;
        $this->mysql = $mysql;
    }

    public function create (stdClass $user, int $roomId, string $text, string $type) {
        $replyTo = $this->findReplyTo($text);
        $pings = [];

        if ($replyTo) {
            $reply = yield \Amp\resolve($this->read($replyTo));

            if ($reply) {
                if ($reply->room_id !== $roomId) {
                    $reply = null;
                    $replyTo = 0;
                }

                $pings[] = $reply->user->id;
            }
        }

        $time = time();

        $query = yield $this->mysql->prepare("INSERT INTO message (`room_id`, `user_id`, `text`, `reply_to`, `type`, `time`) VALUES (?, ?, ?, ?, ?, ?)", [
            $roomId, $user->id, $text, $replyTo, $type, $time
        ]);

        $pings = array_unique(array_merge($pings, yield \Amp\resolve($this->extractPings($text))));

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
            "type" => $type,
            "edit_time" => null,
            "time" => $time
        ];

        yield $this->redis->publish("chat:rooms:{$roomId}", json_encode([
            "type" => "message:create",
            "payload" => $payload,
        ]));

        yield \Amp\resolve($this->sendPingNotifications($user, $query->insertId, $roomId, $pings));

        return $payload;
    }

    public function read (int $messageId, bool $expandReply = false): Generator {
        $result = yield $this->mysql->prepare(
            <<<SQL
    SELECT m.`id`, m.`room_id`, u.`id` userId, u.`name` userName, u.`avatar` userAvatar, m.`type`, m.`text`, m.`data`, m.`reply_to`, m.`edit_time`, m.`time`
    FROM `message` m, `user` u
    WHERE u.id = m.user_id && m.id = ?
SQL
            , [$messageId]
        );

        $message = yield $result->fetchObject();

        if (!$message) {
            return null;
        }

        if ($expandReply && $message->replyTo) {
            $reply = yield \Amp\resolve($this->read($message->replyTo));
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
        $currentMessage = yield \Amp\resolve($this->read($messageId));

        if (!$currentMessage) {
            throw new Exception("message_not_found");
        }

        if ($currentMessage->user->id !== $user->id) {
            throw new Exception("wrong_user");
        }

        if ($currentMessage->time < time() - 5 * 60) {
            throw new Exception("message_too_old");
        }

        $replyTo = $this->findReplyTo($text);
        $pings = [];

        if ($replyTo) {
            $reply = yield \Amp\resolve($this->read($replyTo));

            if ($reply) {
                if ($reply->room_id !== $currentMessage->room_id) {
                    $reply = null;
                    $replyTo = 0;
                }

                $pings[] = $reply->user->id;
            }
        }

        yield $this->mysql->prepare("UPDATE message SET `text` = ?, `data` = NULL, `reply_to` = ?, `edit_time` = ? WHERE `id` = ?", [
            $text, $replyTo, $time = time(), $messageId
        ]);

        $pings = array_unique(array_merge($pings, yield \Amp\resolve($this->extractPings($text))));

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

        yield \Amp\resolve($this->sendPingNotifications($user, $messageId, $currentMessage->room_id, $pings));

        return $payload;
    }

    private function extractPings (string $text) {
        $users = getPingedNames($text);

        if ($count = count($users)) {
            $pings = [];

            $where = substr(str_repeat(" || u.name = ?", $count), 4);
            $result = yield $this->mysql->prepare("SELECT u.id FROM `user` u, `room_user` ru WHERE u.id = ru.user_id AND ru.room_id = ? AND {$where}",
                array_merge([$roomId], array_keys($users))
            );

            foreach (yield $result->fetchAll() as list($id)) {
                $pings[] = $id;
            }

            return $pings;
        }

        return [];
    }

    private function sendPingNotifications (stdClass $user, int $messageId, int $roomId, array $pings) {
        if (count($pings)) {
            $data = [];

            foreach ($pings as $ping) {
                $data[] = $messageId;
                $data[] = $ping;
            }

            $where = substr(str_repeat(", (?, ?)", count($pings)), 2);
            yield $this->mysql->prepare("INSERT IGNORE INTO `ping` (`message_id`, `user_id`) VALUES {$where}", $data);

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