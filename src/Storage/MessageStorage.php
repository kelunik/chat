<?php

namespace Kelunik\Chat\Storage;

use Amp\Promise;

interface MessageStorage {
    public function insert(int $userId, int $roomId, string $text, string $type, int $replyTo, int $time, $data = null): Promise;

    public function update(int $messageId, string $text, int $time): Promise;

    public function upgrade(int $messageId, string $type, $data): Promise;

    public function get(int $messageId): Promise;

    public function getList(array $messageIds): Promise;
}