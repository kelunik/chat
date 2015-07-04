<?php

namespace App\Api;

use Amp\Artax\Client;
use Amp\Artax\Request;

class StackExchange {
    private $client;

    public function __construct (Client $client) {
        $this->client = $client;
    }

    public function query ($path, $token = null) {
        $uri = "https://api.stackexchange.com/{$path}";
        $query = [
            "key" => SE_CLIENT_KEY,
            "site" => "stackoverflow",
        ];

        if ($token) {
            $query["access_token"] = $token;
        }

        $uri .= "?" . http_build_query($query);

        $request = (new Request)
            ->setMethod("GET")
            ->setUri($uri);

        $response = yield $this->client->request($request);

        if ($response->getStatus() !== 200) {
            throw new Exception(sprintf(
                "bad status code: %s %s", $response->getStatus(), $response->getReason()
            ));
        }

        return json_decode($response->getBody(), true);
    }
}