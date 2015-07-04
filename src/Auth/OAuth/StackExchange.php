<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use App\Api\StackExchange as Api;

class StackExchange extends Provider {
    public function __construct (Client $client, Api $api, string $scope = "") {
        parent::__construct($client, $scope);
        $this->api = $api;

        $this->redirectUri = "http://192.168.1.106:3000/login/stack-exchange";
        $this->authorizeUrl = "https://stackexchange.com/oauth";
        $this->accessTokenUrl = "https://stackexchange.com/oauth/access_token";
        $this->clientId = SE_CLIENT_ID;
        $this->clientSecret = SE_CLIENT_SECRET;
        $this->client = $client;
        $this->scope = $scope;
    }

    public function getIdentity (string $token) {
        $response = yield from $this->api->query("me", $token);

        if (isset($response["items"][0]["user_id"], $response["items"][0]["display_name"])) {
            return [
                "id" => $response["items"][0]["user_id"],
                "name" => $response["items"][0]["display_name"],
            ];
        } else {
            return null;
        }
    }
}
