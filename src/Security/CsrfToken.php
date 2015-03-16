<?php

namespace App\Security;

use RandomLib\Generator;

class CsrfToken implements Token {
    const SESSION_KEY = "csrfToken";

    protected $generator;
    protected $session;

    public function __construct (Generator $generator, SessionInterface $session) {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function generate () {
        $this->session->set($this::SESSION_KEY, $this->generator->generate(32));
    }

    public function get () {
        if (!$this->session->has($this::SESSION_KEY)) {
            $this->generate();
        }

        return $this->session->get($this::SESSION_KEY);
    }

    public function validate ($token) {
        return hash_equals($this->get(), $token);
    }
}
