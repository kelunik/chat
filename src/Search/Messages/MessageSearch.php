<?php

namespace Kelunik\Chat\Search\Messages;

interface MessageSearch {
    public function query(MessageQuery $query, int $from, int $limit = 50);
}