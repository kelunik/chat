<?php

namespace Kelunik\Chat\Commands\Messages;

use Kelunik\Chat\Boundaries\Data;
use Kelunik\Chat\Boundaries\Error;
use Kelunik\Chat\Boundaries\Request;
use Kelunik\Chat\Boundaries\User;
use Kelunik\Chat\Command;
use Kelunik\Chat\Events\EventHub;
use Kelunik\Chat\Storage\MessageStorage;
use Kelunik\Chat\Storage\UserStorage;
use function Kelunik\Chat\getPingedNames;
use function Kelunik\Chat\getReplyId;

class Edit extends Command {
    private $messageStorage;
    private $userStorage;
    private $eventHub;

    public function __construct(MessageStorage $messageStorage, UserStorage $userStorage, EventHub $eventHub) {
        $this->messageStorage = $messageStorage;
        $this->userStorage = $userStorage;
        $this->eventHub = $eventHub;
    }

    public function execute(Request $request, User $user) {
        $args = $request->getArgs();
        $payload = $request->getPayload();

        $message = yield $this->messageStorage->get($args->id);

        if (!$message) {
            return Error::make("not_found");
        } elseif ($user->id !== $message->user_id) {
            return Error::make("forbidden");
        } elseif ($message->time < time() - 300) {
            return Error::make("locked");
        } elseif ($message->text === $payload->text) {
            return new Data(null, 304);
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

        yield $this->messageStorage->update($args->id, $payload->text, $time);

        $message->edit_time = $time;
        $message->text = $payload->text;
        $message->type = "text";
        $message->data = null;
        $message->user = [
            "id" => $user->id,
            "name" => $user->name,
            "avatar" => $user->avatar,
        ];

        $this->eventHub->publish("chat:rooms:{$message->room_id}", "message/update", $message);

        return new Data($message);
    }
}