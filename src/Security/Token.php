<?php

namespace App\Security;

interface Token {
    public function get ();

    public function generate ();

    public function validate ($token);
}
