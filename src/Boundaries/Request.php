<?php

namespace Kelunik\Chat\Boundaries;


interface Request {
    public function getUri(): string;
    public function getArgs(): array;
    public function getPayload();
}