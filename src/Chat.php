<?php

namespace Kelunik\Chat;

use Amp\Promise;
use Auryn\Injector;
use Generator;
use JsonSchema\Uri\UriRetriever;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Success;
use function Amp\resolve;

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
     * @var Injector
     */
    private $injector;

    /**
     * @var Command[]
     */
    private $commands;

    public function __construct(UriRetriever $retriever, RequestValidator $validator, Injector $injector) {
        $this->retriever = $retriever;
        $this->validator = $validator;
        $this->injector = $injector;
        $this->commands = [];
        $this->initialize();
    }

    private function initialize() {
        $namespace = __NAMESPACE__;

        $dir = new RecursiveDirectoryIterator(__DIR__ . "/Commands");
        $iterator = new RecursiveIteratorIterator($dir);
        $regex = new RegexIterator($iterator, "~.+.php$~", RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as list($file)) {
            $item = str_replace([".php", __DIR__], "", $file);
            $class = str_replace("/", "\\", $item);
            $command = $this->injector->make($namespace . $class);
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

    public function process(Request $request, User $user): Promise {
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
        $result = $command->execute($request, $user);

        if ($result instanceof Generator) {
            return resolve($result);
        } elseif ($result instanceof Promise) {
            return $result;
        } else {
            return new Success($result);
        }
    }
}