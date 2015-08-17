<?php

namespace Kelunik\Chat;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Response;
use RuntimeException;

function getReplyId($text): int {
    if (preg_match("~^:(\\d+) ~", $text, $match)) {
        return (int) $match[1];
    }

    // use 0 as no-reply value, because it's no valid ID
    // and works nicely with if ($replyTo) { ... }
    return 0;
}

function getPingedNames(string $text): array {
    $pattern = "~\\B@([a-z][a-z0-9-]*?)\\b~i";
    $users = [];

    // remove code blocks, we don't want code to ping people
    $text = preg_replace("~(`|```)([^`]+?)(\\1)~", "", $text);

    if ($text === false) {
        throw new RuntimeException("preg_replace failed");
    }

    $count = preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

    if ($count === false) {
        throw new RuntimeException("preg_match_all failed");
    }

    if ($count) {
        foreach ($matches as $match) {
            $users[$match[1]] = true;
        }
    }

    return array_keys($users);
}

function createPaginationResult(array $data, string $cursorProperty = "id", int $limit = 50): Response {
    if (isset($data[$limit])) {
        $next = is_array($data[$limit])
            ? $data[$limit][$cursorProperty]
            : $data[$limit]->$cursorProperty;

        unset($data[$limit]);
    } else {
        $next = false;
    }

    if (empty($data)) {
        return Error::make("not_found");
    }

    $response = new Data($data);

    if ($next) {
        $response->addLink("next", [
            "cursor" => $next,
        ]);
    }

    $first = is_array($data[0])
        ? $data[0][$cursorProperty]
        : $data[0]->$cursorProperty;

    if ($first > 1) {
        // This may result in a 404, but it's cheaper than a second query every time!
        $response->addLink("previous", [
            "cursor" => $first - 1,
        ]);
    }

    return $response;
}