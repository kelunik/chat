<?php

namespace Kelunik\Chat\Boundaries;

use Amp\Struct;

class User {
    use Struct;

    public $id;
    public $name;
    public $avatar;

    public function __construct(int $id, string $name, string $avatar = null) {
        $this->id = $id;
        $this->name = $name;
        $this->avatar = $avatar;
    }
}