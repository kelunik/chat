<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use Amp\Artax\FormBody;
use Amp\Artax\Request;
use Amp\Artax\Response;

class GitHub extends Provider {
    public function __construct (Client $client, $scope = "") {
        $this->authorizeUrl = "https://github.com/login/oauth/authorize";
        $this->accessTokenUrl = "https://github.com/login/oauth/access_token";
        $this->clientId = GH_CLIENT_ID;
        $this->clientSecret = GH_CLIENT_SECRET;
        $this->client = $client;
        $this->scope = $scope;
    }

    public function processAuthorizeResponse ($code) {
        $body = (new FormBody())
            ->addField("client_id", $this->clientId)
            ->addField("client_secret", $this->clientSecret)
            ->addField("code", $code);

        $request = (new Request())
            ->setMethod("POST")
            ->setUri($this->accessTokenUrl)
            ->setHeader("Accept", "application/json")
            ->setBody($body);

        /** @var Response $response */
        $response = yield $this->client->request($request);
        $data = json_decode($response->getBody(), true);

        if (isset($data["access_token"])) {
            return $data["access_token"];
        } else {
            throw new OAuthException($data["error_description"] ?? $data["error"] ?? "No access token provided");
        }
    }
}
