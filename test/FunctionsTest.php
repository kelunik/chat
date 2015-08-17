<?php

namespace Kelunik\Chat;

use Kelunik\Chat\Boundaries\Response;
use PHPUnit_Framework_TestCase;

class FunctionsTest extends PHPUnit_Framework_TestCase {
    /**
     * @test
     */
    public function getReplyId() {
        $this->assertSame(42, getReplyId(":42 123"));
        $this->assertSame(0, getReplyId(":42"));
        $this->assertSame(0, getReplyId(":-1 123"));
        $this->assertSame(0, getReplyId(":42..."));
        $this->assertSame(0, getReplyId("42"));
        $this->assertSame(0, getReplyId("::42 123"));
    }

    /**
     * @test
     */
    public function getPingedNames() {
        $this->assertEquals(["foo"], getPingedNames("@foo"));
        $this->assertEquals(["foo", "bar"], getPingedNames("@foo @bar"));

        // FIXME Should actually ping @foo and @bar
        $this->assertEquals(["foo"], getPingedNames("@foo@bar"));

        $this->assertEquals([], getPingedNames("me@example.com"));
        $this->assertEquals([], getPingedNames("`@foo`"));
    }

    /**
     * @test
     */
    public function getPingedNamesException() {
        // regex stack limit
        $this->assertEquals([], getPingedNames("@" . str_repeat("a", 1000000)));
    }

    /**
     * @test
     */
    public function createPaginationResult1() {
        $data = [
            [
                "id" => 1,
                "data" => "foo",
            ],
            [
                "id" => 2,
                "data" => "bar",
            ],
            [
                "id" => 3,
                "data" => "baz",
            ],
        ];

        /** @var Response $result */
        $result = createPaginationResult($data, "id", 2);

        $this->assertEquals([
            [
                "id" => 1,
                "data" => "foo",
            ],
            [
                "id" => 2,
                "data" => "bar",
            ],
        ], $result->getData());

        $this->assertEquals([
            "next" => ["cursor" => 3],
        ], $result->getLinks());

        $this->assertSame(200, $result->getStatus());
    }

    /**
     * @test
     */
    public function createPaginationResult2() {
        $data = [
            [
                "id" => 5,
                "data" => "foo",
            ],
            [
                "id" => 6,
                "data" => "bar",
            ],
            [
                "id" => 7,
                "data" => "baz",
            ],
        ];

        /** @var Response $result */
        $result = createPaginationResult($data, "id", 2);

        $this->assertEquals([
            [
                "id" => 5,
                "data" => "foo",
            ],
            [
                "id" => 6,
                "data" => "bar",
            ],
        ], $result->getData());

        $this->assertEquals([
            "next" => ["cursor" => 7],
            "previous" => ["cursor" => 4],
        ], $result->getLinks());

        $this->assertSame(200, $result->getStatus());
    }

    /**
     * @test
     */
    public function createPaginationResult3() {
        $data = [
            [
                "id" => 5,
                "data" => "foo",
            ],
        ];

        /** @var Response $result */
        $result = createPaginationResult($data, "id", 1);
        $this->assertEquals($data, $result->getData());

        $this->assertEquals([
            "previous" => ["cursor" => 4],
        ], $result->getLinks());

        $this->assertSame(200, $result->getStatus());
    }

    /**
     * @test
     */
    public function createPaginationResult4() {
        $data = [];

        /** @var Response $result */
        $result = createPaginationResult($data, "id", 2);
        $this->assertSame(404, $result->getStatus());
    }
}