<?php

namespace App;

use Amp\Redis\Redis;
use Mysql\Pool;

class ChatApi {
	private $db;
	private $redis;

	public function __construct (Pool $db, Redis $redis) {
		$this->db = $db;
		$this->redis = $redis;
	}

	public function addMessage ($roomId, User $user, $messageText, $token, $time) {
		if (!yield $user->canWrite($roomId)) {
			throw new PermissionException(sprintf(
				"%s doesn't have the right to post messages to %d",
				$user->getName(), $roomId
			));
		}

		$reply = yield $this->getReply($messageText);

		$pings = $reply ? [$reply->user->id] : [];
		$pings = array_unique(array_merge($pings, yield $this->getPings($roomId, $messageText)));

		$result = yield $this->db->prepare("INSERT INTO messages (`roomId`, `userId`, `text`, `replyTo`, `time`) VALUES (?, ?, ?, ?, ?)", [
			$roomId, $user->getId(), $messageText, $reply->messageId ?? null, $time
		]);

		yield $this->addPings($user, $roomId, $result->insertId, $pings);

		yield $this->redis->publish("chat.room", json_encode([
			"roomId" => $roomId,
			"type" => "message",
			"payload" => [
				"messageId" => $result->insertId,
				"roomId" => $roomId,
				"messageText" => $messageText,
				"user" => [
					"id" => $user->getId(),
					"name" => $user->getName(),
					"avatar" => $user->getAvatar()
				],
				"reply" => $reply,
				"token" => $token,
				"time" => $time
			]
		]));

		yield (object) [
			"messageId" => $result->insertId,
			"reply" => $reply,
			"pings" => $pings,
		];
	}

	public function editMessage ($messageId, User $user, $messageText, $token, $time) {
		if (!yield $user->canEdit($messageId)) {
			throw new PermissionException(sprintf(
				"%s doesn't have the right to edit message %d",
				$user->getName(), $messageId
			));
		}

		$roomId = yield $this->getRoomFromMessage($messageId);

		$reply = yield $this->getReply($messageText);

		$pings = $reply ? [$reply->user->id] : [];
		$pings = array_unique(array_merge($pings, yield $this->getPings($roomId, $messageText)));

		$result = yield $this->db->prepare("UPDATE messages SET `text` = ?, `replyTo` = ?, `edited` = ? WHERE id = ?", [
			$messageText, $reply->messageId ?? null, $time, $messageId
		]);

		yield $this->addPings($user, $roomId, $messageId, $pings);

		yield $this->redis->publish("chat.room", json_encode([
			"roomId" => $roomId,
			"type" => "message-edit",
			"payload" => [
				"messageId" => $messageId,
				"text" => $messageText,
				"user" => [
					"id" => $user->getId(),
					"name" => $user->getName(),
					"avatar" => $user->getAvatar()
				],
				"reply" => $reply,
				"token" => $token,
				"time" => $time
			]
		]));

		yield (object) [
			"reply" => $reply,
			"pings" => $pings
		];
	}

	public function addStar ($userId, $messageId, $time) {
		$roomId = yield $this->getRoomFromMessage($messageId);

		yield $this->db->prepare("INSERT IGNORE INTO message_stars(`messageId`, `userId`, `time`) VALUES(?, ?, ?)", [
			$messageId, $userId, $time
		]);

		$stars = yield $this->getStars($messageId);

		yield $this->redis->publish("chat.room", json_encode([
			"roomId" => $roomId,
			"type" => "star",
			"payload" => [
				"messageId" => $messageId,
				"action" => "star",
				"user" => $userId,
				"stars" => $stars
			]
		]));
	}

	public function removeStar ($userId, $messageId) {
		$roomId = yield $this->getRoomFromMessage($messageId);

		yield $this->db->prepare("DELETE FROM message_stars WHERE messageId = ? && userId = ?", [
			$messageId, $userId
		]);

		$stars = yield $this->getStars($messageId);

		yield $this->redis->publish("chat.room", json_encode([
			"roomId" => $roomId,
			"type" => "star",
			"payload" => [
				"messageId" => $messageId,
				"action" => "unstar",
				"user" => $userId,
				"stars" => $stars
			]
		]));
	}

	public function getStars ($messageId) {
		$result = yield $this->db->prepare("SELECT COUNT(*) AS stars FROM `message_stars` WHERE `messageId` = ?", [
			$messageId
		]);

		$result = yield $result->fetch();

		yield $result[0];
	}

	public function clearPing ($userId, $messageId) {
		$roomId = yield $this->getRoomFromMessage($messageId);

		yield $this->db->prepare("UPDATE pings SET seen = 1 WHERE userId = ? && messageId = ?", [
			$userId, $messageId
		]);

		yield $this->redis->publish("chat.user", json_encode([
			"userId" => $userId,
			"type" => "ping-clear",
			"payload" => [
				"messageId" => $messageId,
			]
		]));
	}

	private function addPings (User $user, $roomId, $messageId, array $pings) {
		if (sizeof($pings)) {
			$data = [];

			foreach ($pings as $ping) {
				$data[] = $messageId;
				$data[] = $ping;
			}

			$where = substr(str_repeat(", (?, ?)", sizeof($pings)), 2);
			yield $this->db->prepare("INSERT IGNORE INTO `pings` (`messageId`, `userId`) VALUES {$where}", $data);

			foreach ($pings as $ping) {
				yield $this->redis->publish("chat.user", json_encode([
					"userId" => $ping,
					"type" => "ping",
					"payload" => [
						"roomId" => $roomId,
						"user" => [
							"id" => $user->getId(),
							"name" => $user->getName()
						],
						"messageId" => $messageId
					]
				]));
			}
		}
	}

	private function getReply ($messageText) {
		if (preg_match("~^:([0-9]+) ~", $messageText, $match)) {
			$result = yield $this->db->prepare("SELECT m.id, u.id, u.name, u.avatar_url FROM users AS u, messages AS m WHERE u.id = m.userId && m.id = ?", [
				$match[1]
			]);

			if (yield $result->rowCount()) {
				list($messageId, $userId, $username, $avatar) = yield $result->fetch();

				yield (object) [
					"messageId" => (int) $match[1],
					"user" => (object) [
						"id" => (int) $userId,
						"name" => $username,
						"avatar" => $avatar
					]
				];
			}
		}
	}

	private function getPings ($roomId, $messageText) {
		$users = [];

		if (preg_match_all("~@([a-z][a-z-]*)~i", $messageText, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$users[$match[1]] = $match[1];
			}
		}

		if (sizeof($users) > 0) {
			$pings = [];

			$where = str_repeat(" || u.name = ?", sizeof($users));
			$result = yield $this->db->prepare("SELECT u.id FROM users AS u, room_users AS ru WHERE u.id = ru.userId && ru.roomId = ? && (0 {$where})",
				array_merge([$roomId], array_values($users))
			);

			foreach (yield $result->fetchObjects() as $row) {
				$pings[$row->id] = $row->id;
			}

			yield $pings;
		} else {
			yield [];
		}
	}

	private function getRoomFromMessage ($messageId) {
		$roomId = yield $this->db->prepare("SELECT roomId FROM messages WHERE `id` = ?", [$messageId]);
		$roomId = yield $roomId->fetch();

		yield (int) $roomId[0];
	}
}
