<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use App\Security\OAuthCsrfToken;

abstract class Provider {
    /** @var Client */
    protected $client;
    protected $redirectUri;
    protected $authorizeUrl;
    protected $accessTokenUrl;
    protected $clientId;
    protected $clientSecret;
    protected $scope;

    public function generateAuthorizeRedirect (OAuthCsrfToken $token) {
        $data = [
            "client_id" => $this->clientId,
            "scope" => $this->scope,
            "state" => $token->get(),
            "redirect_uri" => $this->redirectUri
        ];

        $url = $this->authorizeUrl . "?" . http_build_query($data);

        yield "status" => 302;
        yield "header" => "Location: {$url}";
    }

    public abstract function processAuthorizeResponse ($code);
}
