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
}
