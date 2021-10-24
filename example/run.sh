#!/bin/sh

set -e

# Start webpack file watcher
npm run webpack-watch &
WEBPACK_WATCH_PID=$!
echo "Webpack Watch PID: $WEBPACK_WATCH_PID"

# Run dev server, allow aborting
set +e
php -S 127.0.0.1:30270 -t ./web/

# Clean up
kill -9 $WEBPACK_WATCH_PID
