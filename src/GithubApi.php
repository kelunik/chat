<?php

namespace App;

use Amp\Artax\Client;
use Amp\Artax\FormBody;
use Amp\Artax\Request;
use Amp\Artax\Response;
use Amp\Future;

class GithubApi {
    private $client;
    private $token;

    public function __construct ($token) {
        $this->token = $token;
        $this->client = new Client();
    }

    public function getToken () {
        return $this->token;
    }

    public function fetchToken ($code) {
        $body = (new FormBody())
            ->addField("client_id", GITHUB_CLIENT_ID)
            ->addField("client_secret", GITHUB_CLIENT_SECRET)
            ->addField("code", $code);

        $request = (new Request())
            ->setMethod("POST")
            ->setUri("https://github.com/login/oauth/access_token")
            ->setHeader("Accept", "application/json")
            ->setBody($body);

        $future = new Future;

        $this->client->request($request)->when(function ($error, Response $response = null) use ($future) {
            if ($error) {
                $future->fail($error);
            } else {
                if ($response->getStatus() !== 200) {
                    $future->fail(new GithubApiException(sprintf(
                        "bad status code: %s %s", $response->getStatus(), $response->getReason()
                    )));
                } else {
                    $data = json_decode($response->getBody());

                    if (isset($data->access_token)) {
                        $this->token = $data->access_token;
                        $future->succeed($data->access_token);
                    } else {
                        $future->fail(new GithubApiException("No Token: " . $data->error));
                    }
                }
            }
        });

        return $future;
    }

    private function query ($path, $token = null) {
        $token = $token ?: $this->token;

        $request = (new Request)
            ->setMethod("GET")
            ->setUri("https://api.github.com/{$path}")
            ->setHeader("Authorization", "token {$token}")
            ->setHeader("Accept", "application/vnd.github.v3+json");

        $future = new Future;

        $this->client->request($request)->when(function ($error, Response $response = null) use ($future) {
            if ($error) {
                $future->fail($error);
            } else {
                if ($response->getStatus() !== 200) {
                    $future->fail(new GithubApiException(sprintf(
                        "bad status code: %s %s", $response->getStatus(), $response->getReason()
                    )));
                } else {
                    $future->succeed(json_decode($response->getBody()));
                }
            }
        });

        return $future;
    }

    public function queryPrimaryMail ($token = null) {
        $future = new Future;

        $this->query("user/emails", $token)->when(function ($error, $data) use ($future) {
            if ($error) {
                $future->fail($error);
            } else {
                foreach ($data as $entry) {
                    if ($entry->primary) {
                        $future->succeed($entry->email);
                        return;
                    }
                }

                $future->fail(new GithubApiException("user without primary email"));
            }
        });

        return $future;
    }

    public function queryUser ($token = null) {
        return $this->query("user", $token);
    }
}
