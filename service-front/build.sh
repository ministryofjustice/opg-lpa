#!/bin/sh

set -e

TASK="${1:-build}"

case "$TASK" in
  build)
    echo "Running full build..."
    npm run build:css
    npm run build:js
    echo "Build complete"
    ;;

  watch)
    echo "Starting watch mode..."

    # Simple polling-based watch
    TIMESTAMP_FILE=$(mktemp)
    while true; do
      sleep 2
      # Use find to check for modified files newer than the timestamp file
      CHANGED_JS=$(find assets/js -type f -newer "$TIMESTAMP_FILE" 2>/dev/null | wc -l)
      CHANGED_SASS=$(find assets/sass -type f -newer "$TIMESTAMP_FILE" 2>/dev/null | wc -l)
      touch "$TIMESTAMP_FILE"

      if [ "$CHANGED_JS" -gt 0 ]; then
        echo "JavaScript files changed, rebuilding..."
        npm run build:js &
      fi

      if [ "$CHANGED_SASS" -gt 0 ]; then
        echo "Sass files changed, rebuilding..."
        ./build-css.sh
      fi
    done
    ;;

  *)
    echo "Unknown task: $TASK"
    exit 1
    ;;
esac
