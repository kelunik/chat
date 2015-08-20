<?php

namespace Kelunik\Chat\Search;

class SearchResult {
    private $total;
    private $results;

    public function __construct(int $total, array $results) {
        $this->total = $total;
        $this->results = $results;
    }

    public function getTotal() {
        return $this->total;
    }

    public function getHits() {
        return $this->results;
    }
}