<?php

namespace App;

use Mysql\Pool;
use Parsedown;
use Tpl;

class Page {
    private $db;
    private $sessionManager;

    public function __construct (Pool $db) {
        $this->db = $db;
        $this->sessionManager = new SessionManager;
    }

    public function handleRequest ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "chat.php", Tpl::LOAD_PHP);
        $tpl->set('session', $session);
        yield "body" => $tpl->page();
    }

    public function roomOverview ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $q = yield $this->db->query("SELECT * FROM rooms");
        $rooms = yield $q->fetchObjects();

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "rooms.php", Tpl::LOAD_PHP);
        $tpl->set("rooms", $rooms);
        $tpl->set("session", $session);
        yield "body" => $tpl->page();
    }

    public function createRoom ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "room_create.php", Tpl::LOAD_PHP);
        $tpl->set("session", $session);
        yield "body" => $tpl->page();
    }

    public function createRoomSubmit ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $token = $request["FORM"]["csrf-token"] ?? "";

        if (!is_string($token) || !safe_compare($session->csrfToken, $token)) {
            yield "status" => 401;
            yield "body" => "";
            return;
        }

        $name = $request["FORM"]["name"] ?? null;
        $desc = $request["FORM"]["description"] ?? null;

        if (!is_string($name) || !is_string($desc)) {
            yield "status" => 400;
            yield "body" => "";
            return;
        }

        $q = yield $this->db->prepare("INSERT INTO rooms (`name`, `description`, `creationTime`) VALUES (?, ?, ?)", [
            $name, $desc, time()
        ]);

        yield $this->db->prepare("INSERT INTO room_users (`roomId`, `userId`, `role`, `joinedTime`) VALUES (?, ?, ?, ?)", [
            $q->insertId, $session->id, "ADMIN", time()
        ]);

        yield "status" => 302;
        yield "header" => "Location: /rooms/" . $q->insertId;
        yield "body" => "";
    }

    public function leaveRoom ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $roomId = $request["URI_ROUTE_ARGS"]["id"];

        $q = yield $this->db->prepare("SELECT * FROM room_users WHERE roomId = ? && userId = ?", [
            $roomId, $session->id
        ]);

        $roomUser = yield $q->fetchObject();

        $q = yield $this->db->prepare("SELECT * FROM rooms WHERE id = ?", [
            $roomId
        ]);

        $room = yield $q->fetchObject();

        if (!$roomUser || !$room) {
            yield "status" => 302;
            yield "header" => "Location: /rooms";
            yield "body" => "";
            return;
        }

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "room_leave.php", Tpl::LOAD_PHP);

        $tpl->set("isAdmin", $roomUser->role === "ADMIN");
        $tpl->set("room", $room);
        $tpl->set("session", $session);
        yield "body" => $tpl->page();
    }

    public function leaveRoomSubmit ($request) {
        $sessionId = SessionManager::getSessionId($request);

        if ($sessionId === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
        }

        $session = yield $this->sessionManager->getSession($sessionId);

        if ($session === null) {
            yield "status" => 302;
            yield "header" => "Location: /login";
            yield "body" => "";
            return;
        }

        $token = $request["FORM"]["csrf-token"] ?? "";

        if (!is_string($token) || !safe_compare($session->csrfToken, $token)) {
            yield "status" => 401;
            yield "body" => "";
            return;
        }

        $roomId = $request["URI_ROUTE_ARGS"]["id"];

        $q = yield $this->db->prepare("SELECT * FROM room_users WHERE roomId = ? && userId = ?", [
            $roomId, $session->id
        ]);

        $roomUser = yield $q->fetchObject();

        $q = yield $this->db->prepare("SELECT * FROM rooms WHERE id = ?", [
            $roomId
        ]);

        $room = yield $q->fetchObject();

        if (!$roomUser || !$room) {
            yield "status" => 302;
            yield "header" => "Location: /rooms";
            yield "body" => "";
            return;
        }

        if ($roomUser->role !== "ADMIN") {
            $q = yield $this->db->prepare("DELETE FROM room_users WHERE roomId = ? && userId = ?", [
                $roomId, $session->id
            ]);
        }

        yield "status" => 302;
        yield "header" => "Location: /rooms";
        yield "body" => "";
    }
}
