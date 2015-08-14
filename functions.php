<?php

function isOriginAllowed (string $origin): bool {
    return isset(ALLOWED_ORIGINS[$origin]);
}
