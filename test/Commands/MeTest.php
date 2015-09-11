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

    /**
     * @dataProvider provideArgs
     * @param int $id
     * @param string $name
     * @param string|null $avatar
     */
    public function test($id, $name, $avatar) {
        $user = new User($id, $name, $avatar);

        $request = new StandardRequest("me", new stdClass, null);
        $response = $this->command->execute($request, $user);

        $response = $response instanceof Generator ? wait(resolve($response)) : $response;

        $this->assertEquals([
            "id" => $id,
            "name" => $name,
            "avatar" => $avatar,
        ], $response->getData());

        $this->assertSame(200, $response->getStatus());
    }

    public function provideArgs() {
        return [
            [0, "System", null],
            [1, "User", null],
            [-1, "Notifications", null],
            [3, "Foobar", "http://example.com/avatar.png"],
        ];
    }
}