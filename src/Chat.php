<?php

namespace Kelunik\Chat;

use Auryn\Injector;
use JsonSchema\Uri\UriRetriever;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\PingStorage;
use Kelunik\Chat\Storage\RoomStorage;
use Kelunik\Chat\Storage\UserStorage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class Chat {
    /**
     * @var UriRetriever
     */
    private $retriever;

    /**
     * @var RequestValidator
     */
    private $validator;

    /**
     * @var RoomStorage
     */
    private $roomStorage;

    /**
     * @var MessageStorage
     */
    private $messageStorage;

    /**
     * @var UserStorage
     */
    private $userStorage;

    /**
     * @var PingStorage
     */
    private $pingStorage;

    /**
     * @var Command[]
     */
    private $commands;

    public function __construct(UriRetriever $retriever, RequestValidator $validator, UserStorage $userStorage, RoomStorage $roomStorage, MessageStorage $messageStorage, PingStorage $pingStorage) {
        $this->retriever = $retriever;
        $this->validator = $validator;
        $this->roomStorage = $roomStorage;
        $this->messageStorage = $messageStorage;
        $this->pingStorage = $pingStorage;
        $this->userStorage = $userStorage;
        $this->commands = [];
        $this->initialize();
    }

    private function initialize() {
        $injector = new Injector;
        $injector->share($this->pingStorage);
        $injector->share($this->userStorage);
        $injector->share($this->roomStorage);
        $injector->share($this->messageStorage);

        $namespace = __NAMESPACE__;

        $dir = new RecursiveDirectoryIterator(__DIR__ . "/Commands");
        $iterator = new RecursiveIteratorIterator($dir);
        $regex = new RegexIterator($iterator, "~.+.php$~", RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as list($file)) {
            $item = str_replace([".php", __DIR__], "", $file);
            $class = str_replace("/", "\\", $item);
            $command = $injector->make($namespace . $class);
            $this->commands[$command->getName()] = $command;

            $this->prepare($command);
        }
    }

    private function prepare(Command $command) {
        $basePath = $basePath = __DIR__ . "/../res/schema/";
        $uri = $command->getName();
        $uri = $basePath . $uri . "/";

        if ($path = realpath($uri . "args.json")) {
            $schema = $this->retriever->retrieve("file://" . $path);
            $this->validator->setArgsSchema($command->getName(), $schema);
        }

        if ($path = realpath($uri . "payload.json")) {
            $schema = $this->retriever->retrieve("file://" . realpath($uri . "payload.json"));
            $this->validator->setPayloadSchema($command->getName(), $schema);
        }
    }

    public function process(Request $request, User $user) {
        $uri = $request->getUri();

        if (!isset($this->commands[$uri])) {
            return Error::make("not_found");
        }

        $errors = $this->validator->validate($request);

        if ($errors) {
            return new Error("bad_request", "invalid input parameters", 422);
        }

        // TODO add permission checks

        $command = $this->commands[$uri];

        return $command->execute($request, $user);
    }
}