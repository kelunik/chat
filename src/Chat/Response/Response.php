<?php

namespace App\Chat\Response;

interface Response {
    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return array
     */
    public function getLinks(): array;
}