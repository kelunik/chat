<?php

namespace Kelunik\Chat\Commands\Rooms;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Search\Messages\MessageQuery;
use Kelunik\Chat\Search\Messages\MessageSearch;
use Kelunik\Chat\Search\SearchException;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Amp\resolve;

class Search extends Command {
    private $search;
    private $userStorage;
    private $messageStorage;
    private $roomPermissionStorage;

    public function __construct(MessageSearch $search, UserStorage $userStorage, MessageStorage $messageStorage, RoomPermissionStorage $roomPermissionStorage) {
        $this->search = $search;
        $this->userStorage = $userStorage;
        $this->messageStorage = $messageStorage;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $args->room_id));

        if (!isset($permissions[Permission::READ])) {
            return Error::make("forbidden");
        }

        $userId = 0;

        if (isset($args->author)) {
            $author = yield $this->userStorage->getByName($args->author);

            if ($author) {
                $userId = $author->id;
            }
        }

        try {
            $searchResult = yield $this->search->query(new MessageQuery($args->room_id, $args->q, $userId), $args->start ?? 0);
            $hits = $searchResult->getHits();

            $messages = yield $this->messageStorage->getByIds(array_keys($hits));
            $userIds = array_column($messages, "user_id");
            $users = [];

            foreach (yield $this->userStorage->getByIds($userIds) as $user) {
                $users[$user->id] = $user;
            }

            foreach ($messages as &$message) {
                $message->_score = $hits[$message->id];
                $message->user = $users[$message->user_id] ?? null;
            }

            usort($messages, function ($a, $b) {
                return (-1) * ($a->_score <=> $b->_score);
            });

            foreach ($messages as &$message) {
                unset($message->_score, $message->user_id);
            }

            return new Data([
                "total" => $searchResult->getTotal(),
                "hits" => $messages,
            ]);
        } catch (SearchException $e) {
            return new Error("internal_error", "your search could not be completed", 500);
        }
    }
}