<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use App\GitHubApi;

class GitHub extends Provider {
    private $api;

    public function __construct (Client $client, GitHubApi $api, string $scope = "") {
        parent::__construct($client, $scope);
        $this->api = $api;

        $this->authorizeUrl = "https://github.com/login/oauth/authorize";
        $this->accessTokenUrl = "https://github.com/login/oauth/access_token";
        $this->clientId = GH_CLIENT_ID;
        $this->clientSecret = GH_CLIENT_SECRET;
        $this->client = $client;
        $this->scope = $scope;
    }

    public function getIdentity (string $token) {
        $response = yield from $this->api->query("user", $token);
        return $response["login"] ?? null;
    }
}
