<?php

function isOriginAllowed (string $origin) {
    return isset(ALLOWED_ORIGINS[$origin]);
}