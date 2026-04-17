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
	node ./build.js
    echo "Starting watch mode..."

    if ! command -v fswatch >/dev/null 2>&1; then
      echo "Error: fswatch is not installed. Run: brew install fswatch"
      exit 1
    fi

    # Watch hand-edited JS source files and Handlebars HTML templates.
    # Exclude lpa.templates.js because build.js regenerates it on every run,
    # which would otherwise trigger an infinite rebuild loop.
    fswatch -o --exclude 'lpa\.templates\.js' assets/js | while read; do
      echo "JavaScript files changed, rebuilding..."
      node ./build.js &
    done &

    # Watch Sass files
    fswatch -o assets/sass | while read; do
      echo "Sass files changed, rebuilding..."
      ./build-css.sh &
    done &

    echo "Watching for changes... Press Ctrl+C to stop."
    wait
    ;;

  *)
    echo "Unknown task: $TASK"
    exit 1
    ;;
esac
