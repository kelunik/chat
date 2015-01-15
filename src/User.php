<?php

namespace App;

use Mysql\Pool;

class User {
	private $db;
	private $id;
	private $name;
	private $avatar;

	public function __construct (Pool $db, $id, $name, $avatar) {
		$this->db = $db;
		$this->id = (int) $id;
		$this->name = $name;
		$this->avatar = $avatar;
	}

	public function getId () {
		return $this->id;
	}

	public function getName () {
		return $this->name;
	}

	public function getAvatar () {
		return $this->avatar;
	}

	public function canWrite ($roomId) {
		$result = yield $this->db->prepare("SELECT `role` FROM `room_users` WHERE `userId` = ? && `roomId` = ?", [
			$this->id, $roomId
		]);

		$result = yield $result->fetchObject();

		yield isset($result) && ($result->role === "WRITER" || $result->role === "ADMIN");
	}

	public function canEdit ($messageId) {
		$result = yield $this->db->prepare("SELECT `userId`, `time` FROM `messages` WHERE `id` = ?", [
			$messageId
		]);

		$result = yield $result->fetchObject();

		yield isset($result) && (int) $result->userId === $this->id && (int) $result->time > time() - 300;
	}
}
