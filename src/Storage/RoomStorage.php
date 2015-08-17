<?php

namespace Kelunik\Chat\Storage;

use Amp\Promise;

interface RoomStorage {
    public function get(int $roomId): Promise;

    public function getAll (int $userId = 0, int $cursor = 0, bool $asc = true, int $limit = 51): Promise;

    public function getByUser(int $userId): Promise;

    public function getMembers(int $roomId, int $cursor = 0, bool $asc = true, int $limit = 51): Promise;
}