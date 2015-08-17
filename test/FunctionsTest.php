<?php

namespace Kelunik\Chat;

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
        $this->assertEquals([
            "foo" => true,
        ], getPingedNames("@foo"));

        $this->assertEquals([
            "foo" => true,
            "bar" => true
        ], getPingedNames("@foo @bar"));

        // FIXME Should actually ping @foo and @bar
        $this->assertEquals([
            "foo" => true,
        ], getPingedNames("@foo@bar"));

        $this->assertEquals([], getPingedNames("me@example.com"));
        $this->assertEquals([], getPingedNames("`@foo`"));
    }
}