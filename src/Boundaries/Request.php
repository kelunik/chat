<?php

namespace Kelunik\Chat\Boundaries;

use stdClass;

interface Request {
    public function getUri(): string;
    public function getArgs(): stdClass;
    public function getPayload();
}