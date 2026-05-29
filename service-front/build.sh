#!/bin/sh

set -e

TASK="${1:-build}"

case "$TASK" in
  build)
    echo "Running full build..."
    ./build-css.sh
    node ./build.js
    echo "Build complete"
    ;;

  build:js|js)
    echo "Building JavaScript only..."
    node ./build.js
    ;;

  build:css|css)
    echo "Building CSS only..."
    ./build-css.sh
    ;;

  watch)
	echo "Running full build..."
	./build-css.sh
	node ./build.js
	echo "Build complete"
    echo "Watching for changes... Press Ctrl+C to stop."
	node ./watch.js
    wait
    ;;

  *)
    echo "Unknown task: $TASK"
    exit 1
    ;;
esac
