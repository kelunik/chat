<?php

namespace Kelunik\Chat;

use stdClass;

abstract class Command {
    public function getName(): string {
        $base = self::class . "\\";
        $sub = str_replace($base, "", get_class($this));
        return strtolower(str_replace("\\", "/", $sub));
    }

    public abstract function execute(stdClass $args, $payload);

    public abstract function getPermissions(): array;
}