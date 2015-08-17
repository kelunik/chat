<?php

namespace Kelunik\Chat\Boundaries;

class Error implements Response {
    private static $instances = null;

    /**
     * Use flyweight immutable error objects to save object instantiations.
     *
     * @param string $code
     * @return Error
     */
    public static function make(string $code) {
        if (self::$instances === null) {
            self::$instances = [
                "bad_request" => new self("bad_request", "there was a problem with your request, but we can't tell you more", 400),
                "forbidden" => new self("forbidden", "access to the requested resource was not granted", 403),
                "not_found" => new self("not_found", "the requested resource does not exist", 404),
                "locked" => new self("locked", "the requested resource is locked and cannot be changed", 423),
            ];
        }

        return self::$instances[$code] ?? new self($code);
    }

    private $code;
    private $message;
    private $status;

    public function __construct(string $code, string $message = "", int $status = 400) {
        $this->code = $code;
        $this->message = $message;
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return [
            "code" => $this->code,
            "message" => $this->message,
        ];
    }

    /**
     * @return array
     */
    public function getLinks(): array {
        return [];
    }
}