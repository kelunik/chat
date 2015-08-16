<?php

namespace Kelunik\Chat;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Auryn\Injector;
use JsonSchema\Uri\UriRetriever;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\Response;
use Kelunik\Chat\Boundaries\User;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class Chat {
    private $retriever;
    private $validator;
    private $mysql;
    private $redis;
    private $commands;

    public function __construct(UriRetriever $retriever, RequestValidator $validator, Pool $mysql, Client $redis) {
        $this->retriever = $retriever;
        $this->validator = $validator;
        $this->mysql = $mysql;
        $this->redis = $redis;
        $this->commands = [];
        $this->initialize();
    }

    private function initialize() {
        $injector = new Injector;
        $injector->share($this->mysql);
        $injector->share($this->redis);

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

    public function process(Request $request, User $user): Response {
        $uri = $request->getUri();

        if (!isset($this->commands[$uri])) {
            return Error::make("not_found");
        }

        $errors = $this->validator->validate($request);

        if ($errors) {
            return new Error("bad_request", "invalid input parameters", 422);
        }

        $args = $request->getArgs();
        $args->user_id = $user->id;
        $args->user_name = $user->name;
        $args->user_avatar = $user->avatar;

        // TODO add permission checks

        $command = $this->commands[$uri];
        return $command->execute($args, $request->getPayload());
    }
}