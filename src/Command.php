<?php

namespace Kelunik\Chat;

use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;

abstract class Command {
    public function getName(): string {
        $base = self::class . "s\\";
        $sub = str_replace($base, "", get_class($this));
        return strtolower(str_replace("\\", "/", $sub));
    }

    public abstract function execute(Request $request, User $user): Response;

    public abstract function getPermissions(): array;
}