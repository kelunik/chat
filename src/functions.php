<?php

namespace Kelunik\Chat;

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

    return $users;
}