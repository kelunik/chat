<?php

namespace Kelunik\Chat\Boundaries;

class Data implements Response {
    /**
     * Response data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Pagination parameters.
     *
     * @var array
     */
    protected $links;

    /**
     * Constructs a new instance.
     *
     * @param mixed $data
     * @param int   $status
     */
    public function __construct($data, int $status = 200) {
        $this->data = $data;
        $this->status = $status;
        $this->links = [];
    }

    /**
     * Adds an additional link or overrides an already existing one with the same relation.
     *
     * @param string $rel Relation to the current page, e.g. "next" or "previous".
     * @param array  $args Associative array: Keys represent query string keys and values the corresponding values.
     * @return self
     */
    public function addLink(string $rel, array $args): Response {
        $this->links[$rel] = $args;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @return mixed Previously set data.
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return array All added links as associative array.
     */
    public function getLinks(): array {
        return $this->links;
    }
}