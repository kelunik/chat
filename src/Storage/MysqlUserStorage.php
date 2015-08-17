<?php

namespace Kelunik\Chat\Storage;

use Amp\Mysql\Pool;
use Amp\Mysql\ResultSet;
use Amp\Promise;
use Amp\Success;
use function Amp\pipe;

class MysqlUserStorage implements UserStorage {
    private $mysql;

    public function __construct(Pool $mysql) {
        $this->mysql = $mysql;
    }

    public function get(int $id): Promise {
        return pipe($this->mysql->prepare("SELECT `id`, `name`, `avatar` FROM `user` WHERE id = ? LIMIT 1"), function (ResultSet $stmt): Promise {
            return $stmt->fetchObject();
        });
    }

    public function getFromNames(array $names): Promise {
        if (empty($names)) {
            return new Success([]);
        }

        $in = substr(str_repeat(",?", count($names)), 1);

        return pipe($this->mysql->prepare("SELECT `id`, `name`, `avatar` FROM `user` WHERE `name` IN ({$in}) ORDER BY id ASC", [$names]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }

    public function getFromIds(array $ids, bool $asc = true): Promise {
        if (empty($ids)) {
            return new Success([]);
        }

        $in = substr(str_repeat(",?", count($ids)), 1);
        $order = $asc ? "ASC" : "DESC";

        return pipe($this->mysql->prepare("SELECT `id`, `name`, `avatar` FROM `user` WHERE `id` IN ({$in}) ORDER BY id {$order}", [$ids]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }

    public function getAll(int $cursor = 0, bool $asc = true, int $limit = 51): Promise {
        $order = $asc ? "ASC" : "DESC";

        $sql = "SELECT id, name, avatar FROM user WHERE id >= ? ORDER BY id {$order} LIMIT {$limit}";

        return pipe($this->mysql->prepare($sql, [$cursor]), function (ResultSet $stmt): Promise {
            return $stmt->fetchObjects();
        });
    }
}