<?php

namespace Kelunik\Chat\Storage;

use Amp\Promise;

interface PingStorage {
    public function get(int $userId, int $messageId): Promise;

    public function update(int $userId, int $messageId, bool $seen): Promise;
}