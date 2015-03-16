<?php

namespace App\Session;

interface SessionInterface {
    public function getCookieHeader ();

    public function get ($key);

    public function has ($key);

    public function set ($key, $value);
}
