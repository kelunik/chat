<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Events\EventHub;
use Kelunik\Chat\Permission;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\RoomPermissionStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Amp\resolve;
use function Kelunik\Chat\getPingedNames;
use function Kelunik\Chat\getReplyId;

class Create extends Command {
    private $messageStorage;
    private $userStorage;
    private $eventHub;
    private $roomPermissionStorage;

    public function __construct(MessageStorage $messageStorage, UserStorage $userStorage, EventHub $eventHub, RoomPermissionStorage $roomPermissionStorage) {
        $this->messageStorage = $messageStorage;
        $this->userStorage = $userStorage;
        $this->eventHub = $eventHub;
        $this->roomPermissionStorage = $roomPermissionStorage;
    }

    public function execute(Request $request, User $user) {
        $payload = $request->getPayload();

        $permissions = yield resolve($this->roomPermissionStorage->getPermissions($user->id, $payload->room_id));

        if (!isset($permissions[Permission::WRITE])) {
            return Error::make("forbidden");
        }

        if ($user->id < 0) {
            $type = $payload->type ?? "text";
        } else {
            $type = "text";
        }

        $replyTo = getReplyId($payload->text);
        $pings = [];
        $time = time();

        if ($replyTo) {
            $reply = yield $messageStorage->get($replyTo);

            if ($reply) {
                if ($reply->room_id !== $payload->room_id) {
                    $reply = null;
                    $replyTo = 0;
                } else {
                    $pings[] = $reply->user_id;
                    $userId = $reply->user_id;

                    unset($reply->user_id);
                    $reply->user = yield $this->userStorage->get($userId);
                }
            }
        }

        $id = yield $this->messageStorage->insert($user->id, $payload->room_id, $payload->text, $type, $replyTo, $time);

        $pingNames = getPingedNames($payload->text);
        $pingIds = array_map(function ($user): int {
            return $user->id;
        }, yield $this->userStorage->getByNames($pingNames));

        $pings = array_unique(array_merge($pings, $pingIds));

        // remove self-pings
        $key = array_search($user->id, $pings);
        if ($key !== false) {
            unset($pings[$key]);
        }

        $payload = [
            "id" => $id,
            "room_id" => $payload->room_id,
            "text" => $payload->text,
            "user" => [
                "id" => $user->id,
                "name" => $user->name,
                "avatar" => $user->avatar,
            ],
            "reply" => $reply ?? null,
            "type" => $type,
            "edit_time" => null,
            "time" => $time,
        ];

        $this->eventHub->publish("chat:rooms:{$payload->room_id}", "message/create", $payload);

        return new Data($payload);
    }
}