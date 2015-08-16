<?php

namespace Kelunik\Chat\Boundaries;

use Amp\Struct;

class User {
    use Struct;

    public $id;
    public $name;
    public $avatar;
}