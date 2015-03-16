<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use Amp\Artax\FormBody;
use Amp\Artax\Request;
use Amp\Artax\Response;

class StackExchange extends Provider {
    public function __construct (Client $client, $scope = "") {
        $this->redirectUri = DEPLOY_URL . "/oauth/stackexchange";
        $this->authorizeUrl = "https://stackexchange.com/oauth";
        $this->accessTokenUrl = "https://stackexchange.com/oauth/access_token";
        $this->clientId = SE_CLIENT_ID;
        $this->clientSecret = SE_CLIENT_SECRET;
        $this->client = $client;
        $this->scope = $scope;
    }

    public function processAuthorizeResponse ($code) {
        $body = (new FormBody())
            ->addField("redirect_uri", $this->redirectUri)
            ->addField("client_id", $this->clientId)
            ->addField("client_secret", $this->clientSecret)
            ->addField("code", $code);

        $request = (new Request())
            ->setMethod("POST")
            ->setUri($this->accessTokenUrl)
            ->setBody($body);

        /** @var Response $response */
        $response = yield $this->client->request($request);
        parse_str($response->getBody(), $data);

        if (isset($data["access_token"])) {
            return $data["access_token"];
        } else {
            throw new OAuthException($data["error_description"] ?? $data["error"] ?? "No access token provided");
        }
    }
}
