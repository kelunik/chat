<?php

namespace Kelunik\Chat\Search\Messages;

class MessageQuery {
    private $room;
    private $text;
    private $user;

    public function __construct(int $room, string $text, int $user = 0) {
        $this->room = $room;
        $this->text = $text;
        $this->user = $user;
    }

    public function getRoom(): int {
        return $this->room;
    }

    public function getText(): string {
        return $this->text;
    }

    public function getUser(): int {
        return $this->user;
    }
}