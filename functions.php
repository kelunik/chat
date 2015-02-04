<?php

/**
 * A timing safe equals comparison
 *
 * To prevent leaking length information, it is important
 * that user input is always used as the second parameter.
 *
 * @param string $safe The internal (safe) value to be checked
 * @param string $user The user submitted (unsafe) value
 *
 * @return boolean True if the two strings are identical.
 *
 * @link http://stackoverflow.com/a/17266448/2373138
 */
function safe_compare ($safe, $user) {
    $safe .= chr(0);
    $user .= chr(0);

    $safeLen = strlen($safe);
    $userLen = strlen($user);

    $result = $safeLen - $userLen;

    for ($i = 0; $i < $userLen; $i++) {
        $result |= (ord($safe[$i % $safeLen]) ^ ord($user[$i]));
    }

    return $result === 0;
}
