#!/bin/bash

set -e

TASK="${1:-build}"
WATCH_MODE=false

case "$TASK" in
  build)
    echo "Running full build..."
    ./build-js.sh
    ./build-css.sh
    echo "Build complete"
    ;;

  build:css|css)
    echo "Building CSS only..."
    ./build-css.sh
    ;;

  *)
    echo "Unknown task: $TASK"
    exit 1
    ;;
esac
