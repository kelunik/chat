<?php

namespace Kelunik\Chat\Commands;

use Generator;
use Kelunik\Chat\Boundaries\StandardRequest;
use Kelunik\Chat\Boundaries\User;
use PHPUnit_Framework_TestCase;
use stdClass;
use function Amp\resolve;
use function Amp\wait;

class MeTest extends PHPUnit_Framework_TestCase {
    private $command;

    public function setUp() {
        $this->command = new Me;
    }

    public function test() {
        $user = new User(0, "System", null);

        $request = new StandardRequest("me", new stdClass, null);
        $response = $this->command->execute($request, $user);

        $response = $response instanceof Generator ? wait(resolve($response)) : $response;

        $this->assertEquals([
            "id" => 0,
            "name" => "System",
            "avatar" => null,
        ], $response->getData());

        $this->assertSame(200, $response->getStatus());
    }
}