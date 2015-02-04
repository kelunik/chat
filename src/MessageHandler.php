<?php

namespace App;

class MessageHandler {
    public static function buildQuery ($where = "1", $order = "id DESC", $limit = 40) {
        $query = <<<SQL
SELECT
	m.id, m.roomId, m.userId,
	u.name AS userName, u.avatar_url AS userAvatar,
	m.text, m.edited, m.time,
	stars.count AS stars, ms.userId AS starred,
	m.replyTo AS replyMessageId,
	u2.id AS replyUserId,
	u2.name AS replyUserName
FROM (SELECT * FROM `messages` WHERE {$where} ORDER BY {$order} LIMIT {$limit}) AS m
	JOIN `users` AS u ON (m.userId = u.id)
	LEFT JOIN (SELECT m.id AS messageId, u.id, u.name FROM messages AS m, users AS u WHERE m.userId = u.id) AS u2 ON (u2.messageId = m.replyTo)
	LEFT JOIN (SELECT messageId, COUNT(*) AS `count` FROM `message_stars` GROUP BY messageId) AS stars ON (stars.messageId = m.id)
	LEFT JOIN message_stars AS ms ON (ms.userId = ? && ms.messageId = m.id)
SQL;

        return $query;
    }
}
