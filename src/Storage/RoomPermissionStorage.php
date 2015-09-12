<?php

namespace Kelunik\Chat\Storage;

interface RoomPermissionStorage {
    public function getPermissions(int $user, int $room);
}