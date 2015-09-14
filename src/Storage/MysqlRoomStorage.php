<?php

namespace Kelunik\Chat\Storage;

use Amp\Mysql\Pool;
use Amp\Mysql\ResultSet;
use Amp\Promise;
use stdClass;
use function Amp\pipe;

class MysqlRoomStorage implements RoomStorage {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function getByUser(int $userId): Promise {
        $sql = "SELECT r.id, r.name, r.description, r.public, ru.permissions FROM `room` AS r, `room_user` AS ru WHERE r.id = ru.room_id && ru.user_id = ? ORDER BY r.name ASC";

        return pipe($this->mysql->prepare($sql, [$userId]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }

    public function getMembers(int $roomId, int $cursor = 0, bool $asc = true, int $limit = 51): Promise {
        $rel = $asc ? ">=" : "<=";
        $sql = "SELECT user_id FROM room_user ru WHERE ru.user_id {$rel} ? && ru.room_id = ? LIMIT " . $limit;

        return pipe($this->mysql->prepare($sql, [$cursor, $roomId]), function (ResultSet $stmt): Promise {
            return pipe($stmt->fetchObjects(), function (stdClass $obj): int {
                return $obj->id;
            });
        });
    }

    public function get(int $roomId): Promise {
        $sql = "SELECT `id`, `name`, `description`, `public` FROM `room` WHERE `id` = ?";

        return pipe($this->mysql->prepare($sql, [$roomId]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObject();
        });
    }

    public function getAll(int $userId = 0, int $cursor = 0, bool $asc = true, int $limit = 51): Promise {
        $order = $asc ? "ASC" : "DESC";

        if ($userId > 0) {
            $sql = "SELECT r.id, r.name, r.description, r.public FROM room r LEFT JOIN room_user ru ON (ru.room_id = r.id && ru.user_id = ?) WHERE (ru.user_id IS NOT NULL || r.public = 1) && r.id >= ? ORDER BY r.id {$order} LIMIT {$limit}";
            $args = [$userId, $cursor];
        } else {
            $sql = "SELECT id, name, description, public FROM room WHERE public = 1 && id >= ? ORDER BY id {$order} LIMIT {$limit}";
            $args = [$cursor];
        }

        return pipe($this->mysql->prepare($sql, $args), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }
}