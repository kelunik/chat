<?php

namespace App;

use Amp\Redis\Redis;
use Mysql\Pool;
use Parsedown;
use Tpl;

class Search {
    private $db;
    private $redis;
    private $sessionManager;

    public function __construct (Pool $db, Redis $redis) {
        $this->db = $db;
        $this->redis = $redis;
        $this->sessionManager = new SessionManager($redis);
    }

    public function rooms ($request) {
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

        $query = $request["QUERY"]["q"] ?? "";

        if (empty($query)) {
            yield "status" => 400;
            yield "body" => "<h1>400 – Empty Query String</h1>";
            return;
        }

        $escapedQuery = str_replace(["%", "_"], ["\\%", "\\_"], $query) . (strlen($query) > 2 ? "%" : "");

        $q = yield $this->db->prepare("SELECT * FROM rooms WHERE `name` LIKE ? || `description` LIKE ?", [
            $escapedQuery, $escapedQuery
        ]);

        $rooms = yield $q->fetchObjects();

        $tpl = new Tpl(new Parsedown);
        $tpl->load(TEMPLATE_DIR . "search_rooms.php", Tpl::LOAD_PHP);

        $tpl->set("query", $query);
        $tpl->set("rooms", $rooms);
        $tpl->set("session", $session);

        yield "body" => $tpl->page();
    }
}
