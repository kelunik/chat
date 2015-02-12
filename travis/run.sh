set -e

if [ "${TESTS}" = "php" ]
then
    bash travis/run-php.sh
else
    bash travis/run-js.sh
fi
