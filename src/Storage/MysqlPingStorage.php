<?php

namespace Kelunik\Chat\Storage;

use Amp\Mysql\ConnectionState;
use Amp\Mysql\Pool;
use Amp\Mysql\ResultSet;
use Amp\Promise;
use function Amp\pipe;

class MysqlPingStorage implements PingStorage {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function get(int $userId, int $messageId): Promise {
        $sql = "SELECT seen FROM ping WHERE user_id = ? && message_id = ?";

        return pipe($this->mysql->prepare($sql, [$userId, $messageId]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObject();
        });
    }

    public function update(int $userId, int $messageId, bool $seen): Promise {
        $sql = "UPDATE ping SET seen = ? WHERE user_id = ? && message_id = ?";

        return pipe($this->mysql->prepare($sql, [$userId, $messageId]), function (ConnectionState $stmt): int {
            return (bool) $stmt->affectedRows;
        });
    }
}