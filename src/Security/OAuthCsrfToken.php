<?php

namespace App\Security;

class OAuthCsrfToken extends CsrfToken {
    const SESSION_KEY = "oauthToken";
}
