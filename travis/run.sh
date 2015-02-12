[ "${TESTS}" = "php" ] && bash travis/run-php.sh || true
[ "${TESTS}" = "js" ] && bash travis/run-js.sh || true
