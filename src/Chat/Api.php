<?php

namespace App\Chat;

use Amp\Mysql\Pool;
use Amp\Redis\Client;
use Auryn\Injector;
use JsonSchema\Uri\UriRetriever;

class Api {
    private $retriever;
    private $mysql;
    private $redis;
    private $commands;

    public function __construct (UriRetriever $retriever, Pool $mysql, Client $redis) {
        $this->retriever = $retriever;
        $this->mysql = $mysql;
        $this->redis = $redis;
        $this->commands = [];
        $this->initialize();
    }

    private function initialize () {
        $injector = new Injector;
        $injector->share($this->mysql);
        $injector->share($this->redis);

        $namespace = __NAMESPACE__ . "\\Command\\";

        $commands = [
            "me" => "Me",
            "me:rooms" => "Me\\Rooms",
            "messages:get" => "Messages\\Get",
            "messages:create" => "Messages\\Create",
            "messages:edit" => "Messages\\Edit",
            "pings:edit" => "Pings\\Edit",
            "pings:get" => "Pings\\Get",
            "rooms:users:get" => "Rooms\\Users\\Get",
        ];

        foreach ($commands as $endpoint => $class) {
            $command = $injector->make($namespace . $class);
            $this->prepare($command);

            $this->commands[$endpoint] = $command;
        }
    }

    private function prepare (Command $command) {
        $basePath = $basePath = __DIR__ . "/../../res/schema/";
        $uri = $command->getSchemaUri();
        $uri = $basePath . $uri . "/";

        if ($path = realpath($uri . "args.json")) {
            $argsSchema = $this->retriever->retrieve("file://" . $path);
            $command->setArgsSchema($argsSchema);
        }

        if ($path = realpath($uri . "payload.json")) {
            $payloadSchema = $this->retriever->retrieve("file://" . realpath($uri . "payload.json"));
            $command->setPayloadSchema($payloadSchema);
        }
    }

    /**
     * @param string $command Key for the command.
     * @return Command Command that can be checked for validity and executed afterwards.
     */
    public function getCommand (string $command) {
        return $this->commands[$command] ?? null;
    }
}