<?php

namespace App;

use Amp\Artax\Client;
use Amp\Artax\Request;

class GitHubApi {
    private $client;

    public function __construct (Client $client) {
        $this->client = $client;
    }

    public function query ($path, $token = null) {
        $request = (new Request)
            ->setMethod("GET")
            ->setUri("https://api.github.com/{$path}")
            ->setHeader("Accept", "application/vnd.github.v3+json");

        if ($token) {
            $request->setHeader("Authorization", "token {$token}");
        }

        $response = yield $this->client->request($request);

        if($response->getStatus() !== 200) {
            throw new GitHubApiException(sprintf(
                "bad status code: %s %s", $response->getStatus(), $response->getReason()
            ));
        }

        return json_decode($response->getBody(), true);
    }
}
