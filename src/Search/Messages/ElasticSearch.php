<?php

namespace Kelunik\Chat\Search\Messages;

use Amp\Artax\Client;
use Amp\Artax\Request;
use Amp\Artax\Response;
use Exception;
use Generator;
use Kelunik\Chat\Search\SearchException;
use Kelunik\Chat\Search\SearchResult;
use function Amp\coroutine;
use function Amp\pipe;
use function Amp\resolve;

class ElasticSearch implements MessageSearch {
    private $http;
    private $host;
    private $port;

    public function __construct(Client $http, string $host, int $port) {
        $this->http = $http;
        $this->host = $host;
        $this->port = $port;
    }

    public function query(MessageQuery $query, int $from, int $limit = 50) {
        $roomFilter = [
            "term" => [
                "room_id" => $query->getRoom(),
            ],
        ];

        if ($query->getUser() > 0) {
            $userFilter = [
                "term" => [
                    "user_id" => $query->getUser(),
                ],
            ];

            $filter = [
                "and" => [
                    $roomFilter,
                    $userFilter,
                ],
            ];
        } else {
            $filter = $roomFilter;
        }

        $payload = [
            "from" => $from,
            "size" => $limit,
            "fields" => [],
            "query" => [
                "filtered" => [
                    "filter" => $filter,
                    "query" => [
                        "match" => [
                            "text" => $query->getText(),
                        ],
                    ],
                ],
            ],
        ];

        $request = (new Request)
            ->setUri("http://{$this->host}:{$this->port}/chat/messages/_search")
            ->setBody(json_encode($payload));

        return resolve($this->performRequest($request));
    }

    private function performRequest(Request $request): Generator {
        try {
            /** @var Response $response */
            $response = yield $this->http->request($request);
        } catch (Exception $e) {
            throw new SearchException("HTTP request to elastic failed", 0, $e);
        }

        $body = $response->getBody();
        $body = json_decode($body);

        if ($response->getStatus() !== 200) {
            throw new SearchException($body->error ?? "unknown error");
        }

        $results = [];

        foreach ($body->hits->hits as $hit) {
            $results[(int) $hit->_id] = $hit->_score;
        }

        return new SearchResult($body->hits->total, $results);
    }
}