#!/bin/sh

set -e

XDEBUG_MODE=coverage php ./vendor/bin/phpunit -c ./phpunit.xml $@ ./server/tests/UnitTests
echo "Ran the tests. Gathering coverage..."
rm -Rf "$(pwd)/docs/coverage/php/UnitTests"
mkdir -p "$(pwd)/docs/coverage/php/UnitTests"
mv "$(pwd)/php-coverage/html-coverage" "$(pwd)/docs/coverage/php/UnitTests/html"

echo ""
echo "Open the HTML test coverage in a web browser:"
echo "    file://$(pwd)/docs/coverage/php/UnitTests/html/index.html"
echo ""
