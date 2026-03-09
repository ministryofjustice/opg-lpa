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

  test|lint)
    echo "Running linters..."

    # Lint JavaScript
    if [[ -f ".jshintrc" ]]; then
      echo "Running JSHint..."
      npx jshint assets/js/moj/**/*.js assets/js/lpa/**/*.js assets/js/main.js Gruntfile.js || true
    fi

    # Lint Sass
    if [[ -f ".scss-lint.yml" ]]; then
      echo "Running SCSS lint..."
      if command -v scss-lint &> /dev/null; then
        scss-lint assets/sass/**/*.scss || true
      else
        echo "scss-lint not found, skipping SCSS linting"
      fi
    fi
    ;;

  clean)
    echo "Cleaning build artifacts..."
    rm -rf public/assets/v2/css/*
    rm -rf public/assets/v2/js/*
    rm -rf public/assets/v2/fonts/*
    ;;

  help|--help|-h)
    echo "Build script for service-front"
    echo ""
    echo "Usage: ./build.sh [task]"
    echo ""
    echo "Tasks:"
    echo "  build       - Build all assets (default)"
    echo "  js          - Build JavaScript only"
    echo "  css         - Build CSS only"
    echo "  watch       - Watch for changes and rebuild (requires fswatch/inotifywait)"
    echo "  watch:poll  - Watch for changes using polling (slower, no deps)"
    echo "  test        - Run linters"
    echo "  clean       - Remove build artifacts"
    echo "  help        - Show this help message"
    echo ""
    echo "Environment variables:"
    echo "  NODE_ENV       - Set to 'production' for minified builds"
    ;;

  *)
    echo "Unknown task: $TASK"
    echo "Run './build.sh help' for usage information"
    exit 1
    ;;
esac
