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

  build:js|js)
    echo "Building JavaScript only..."
    ./build-js.sh
    ;;

  build:css|css)
    echo "Building CSS only..."
    ./build-css.sh
    ;;

  watch)
    echo "Starting watch mode..."

    # Check if fswatch is available (macOS)
    if command -v fswatch &> /dev/null; then
      echo "Using fswatch for file watching..."

      # Watch JS files
      fswatch -o assets/js | while read; do
        echo "JavaScript files changed, rebuilding..."
        ./build-js.sh &
      done &

      # Watch Sass files
      fswatch -o assets/sass | while read; do
        echo "Sass files changed, rebuilding..."
        ./build-css.sh &
      done &

      echo "Watching for changes... Press Ctrl+C to stop."
      wait

    # Check if inotifywait is available (Linux)
    elif command -v inotifywait &> /dev/null; then
      echo "Using inotifywait for file watching..."

      while true; do
        inotifywait -r -e modify,create,delete assets/js assets/sass 2>/dev/null
        echo "Files changed, rebuilding..."
        ./build-js.sh &
        ./build-css.sh &
        wait
      done

    else
      echo "Error: Neither fswatch nor inotifywait found."
      echo "Install fswatch: brew install fswatch (macOS)"
      echo "Or run: npm run watch:poll (slower, but works without additional tools)"
      exit 1
    fi
    ;;

  watch:poll)
    echo "Starting watch mode (polling)..."
    echo "This uses polling and may be slower than native watch."

    # Simple polling-based watch
    while true; do
      # Use find to check for modified files in the last 2 seconds
      CHANGED_JS=$(find assets/js -type f -mtime -2s 2>/dev/null | wc -l)
      CHANGED_SASS=$(find assets/sass -type f -mtime -2s 2>/dev/null | wc -l)

      if [[ $CHANGED_JS -gt 0 ]]; then
        echo "JavaScript files changed, rebuilding..."
        ./build-js.sh
      fi

      if [[ $CHANGED_SASS -gt 0 ]]; then
        echo "Sass files changed, rebuilding..."
        ./build-css.sh
      fi

      sleep 2
    done
    ;;

  *)
    echo "Unknown task: $TASK"
    exit 1
    ;;
esac
