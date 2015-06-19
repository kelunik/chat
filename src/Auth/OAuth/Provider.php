<?php

namespace App\Auth\OAuth;

use Amp\Artax\Client;
use Amp\Artax\FormBody;
use Amp\Artax\Request;
use Amp\Artax\Response as HttpResponse;

abstract class Provider {
    /** @var Client */
    protected $client;
    protected $redirectUri;
    protected $authorizeUrl;
    protected $accessTokenUrl;
    protected $clientId;
    protected $clientSecret;
    protected $scope;

    public function __construct(Client $client, string $scope) {
        $this->client = $client;
        $this->scope = $scope;
    }

    public function getAuthorizeRedirectUrl (string $token) {
        $data = [
            "client_id" => $this->clientId,
            "scope" => $this->scope,
            "state" => $token,
            "redirect_uri" => $this->redirectUri
        ];

        return $this->authorizeUrl . "?" . http_build_query($data);
    }

    public function getAccessTokenFromCode (string $code) {
        $body = (new FormBody)
            ->addField("redirect_uri", $this->redirectUri)
            ->addField("client_id", $this->clientId)
            ->addField("client_secret", $this->clientSecret)
            ->addField("code", $code);

        $request = (new Request)
            ->setMethod("POST")
            ->setUri($this->accessTokenUrl)
            ->setBody($body);

        /** @var HttpResponse $response */
        $response = yield $this->client->request($request);
        $body = $response->getBody();

        parse_str($body, $data);

        if (!isset($data["access_token"])) {
            throw new OAuthException($data["error_description"] ?? $data["error"] ?? "no access token provided");
        }

        return $data["access_token"];
    }

    public abstract function getIdentity (string $token);
}
