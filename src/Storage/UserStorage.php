<?php

namespace Kelunik\Chat\Storage;

use Amp\Promise;

interface UserStorage {
    public function get(int $id): Promise;

    public function getAll (int $cursor = 0, bool $asc = true, int $limit = 51): Promise;

    public function getFromNames(array $names): Promise;

    public function getFromIds(array $ids, bool $asc = true): Promise;
}