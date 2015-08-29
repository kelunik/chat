<?php

namespace Kelunik\Chat\Storage;

use Amp\Mysql\ConnectionState;
use Amp\Mysql\Pool;
use Amp\Mysql\ResultSet;
use Amp\Promise;
use Amp\Success;
use function Amp\pipe;

class MysqlMessageStorage implements MessageStorage {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function insert(int $userId, int $roomId, string $text, string $type, int $replyTo, int $time, $data = null): Promise {
        return pipe($this->mysql->prepare("INSERT INTO message (`room_id`, `user_id`, `text`, `data`, `reply_to`, `type`, `time`) VALUES (?, ?, ?, ?, ?, ?, ?)", [
            $roomId, $userId, $text, $data ? json_encode($data) : null, $replyTo ?: null, $type, $time,
        ]), function ($stmt): int {
            return $stmt->insertId;
        });
    }

    public function update(int $messageId, string $text, int $time): Promise {
        return pipe($this->mysql->prepare("UPDATE `message` SET `text` = ?, `type` = ?, `edit_time` = ? WHERE `id` = ?", [$text, "text", $time, $messageId]), function (ConnectionState $stmt): int {
            return (bool) $stmt->affectedRows;
        });
    }

    public function upgrade(int $messageId, string $type, $data): Promise {
        return pipe($this->mysql->prepare("UPDATE `message` SET `type` = ?, `data` = ? WHERE `id` = ?", [$type, $data ? json_encode($data) : null, $messageId]), function (ConnectionState $stmt): int {
            return (bool) $stmt->affectedRows;
        });
    }

    public function get(int $messageId): Promise {
        return pipe($this->mysql->prepare("SELECT `id`, `room_id`, `user_id`, `type`, `text`, `data`, `reply_to`, `edit_time`, `time` FROM `message` WHERE `id` = ?", [$messageId]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObject();
        });
    }

    public function getByIds(array $messageIds): Promise {
        if (empty($messageIds)) {
            return new Success([]);
        }

        $in = substr(str_repeat(",?", count($messageIds)), 1);

        return pipe($this->mysql->prepare("SELECT `id`, `room_id`, `user_id`, `type`, `text`, `data`, `reply_to`, `edit_time`, `time` FROM `message` WHERE `id` IN ({$in})", $messageIds), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }

    public function getHistory(int $room, int $cursor = -1, bool $asc = false, int $limit = 51): Promise {
        $order = $asc ? "ASC" : "DESC";
        $rel = $asc ? ">=" : "<=";
        $where = $cursor >= 0 ? "WHERE `id` {$rel} ? && room_id = ?" : "WHERE room_id = ?";
        $args = $cursor >= 0 ? [$cursor, $room] : [$room];

        return pipe($this->mysql->prepare("SELECT `id`, `room_id`, `user_id`, `type`, `text`, `data`, `reply_to`, `edit_time`, `time` FROM `message` {$where} ORDER BY id {$order} LIMIT {$limit}", $args), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }
}