#!/bin/bash

set -e

echo "======================================"
echo "Building JavaScript assets"
echo "======================================"

# Ensure output directories exist
mkdir -p public/assets/v2/js/opg
mkdir -p assets/js/opg
mkdir -p assets/js/lpa

echo "→ Processing environment variables..."

echo "→ Compiling Handlebars templates..."

if [[ -d "assets/js/lpa/templates" ]]; then
  # Always write the namespace preamble first so lpa is defined on window
  # before the compiled Handlebars IIFE tries to reference it
  {
    echo "window.lpa = window.lpa || {};"
    echo "window.lpa.templates = window.lpa.templates || {};"
  } > assets/js/lpa/lpa.templates.js

  if command -v handlebars &> /dev/null; then
    # Compile to a temp file then append so the preamble is never overwritten
    npx handlebars assets/js/lpa/templates/*.html \
      -n "lpa.templates" 2>/dev/null >> assets/js/lpa/lpa.templates.js \
      || echo "Note: Handlebars compilation skipped"
    # Strip the .html extension from template keys to match the old Grunt
    # processName behaviour - e.g. templates['popup.mask.html'] -> templates['popup.mask']
    # Use a portable tmp-file approach (BSD sed -i '' vs GNU sed -i differ)
    sed "s/templates\['\([^']*\)\.html'\]/templates['\1']/g" \
      assets/js/lpa/lpa.templates.js > assets/js/lpa/lpa.templates.js.tmp \
      && mv assets/js/lpa/lpa.templates.js.tmp assets/js/lpa/lpa.templates.js
  fi
else
  echo "Warning: No templates directory found"
  echo "window.lpa = window.lpa || {}; window.lpa.templates = window.lpa.templates || {};" > assets/js/lpa/lpa.templates.js
fi

echo "Concatenating JavaScript files..."
# Use a semicolon separator between files (matching the old Grunt concat separator: ';\n')
# to prevent adjacent IIFEs from being parsed as function calls.
JS_FILES=(
  node_modules/handlebars/dist/handlebars.js
  node_modules/lodash/lodash.js
  node_modules/urijs/src/URI.min.js
  node_modules/govuk_frontend_toolkit/javascripts/govuk/show-hide-content.js
  assets/js/opg/jquery-plugin-opg-spinner.js
  assets/js/opg/session-timeout-dialog.js
  assets/js/opg/cache-busting.js
  assets/js/moj/moj.js
  assets/js/moj/moj.helpers.js
  assets/js/moj/moj.cookie-functions.js
  assets/js/lpa/lpa.templates.js
  assets/js/moj/moj.modules/moj.password.js
  assets/js/moj/moj.modules/moj.popup.js
  assets/js/moj/moj.modules/moj.help-system.js
  assets/js/moj/moj.modules/moj.form-popup.js
  assets/js/moj/moj.modules/moj.title-switch.js
  assets/js/moj/moj.modules/moj.postcode-lookup.js
  assets/js/moj/moj.modules/moj.print-link.js
  assets/js/moj/moj.modules/moj.person-form.js
  assets/js/moj/moj.modules/moj.validation.js
  assets/js/moj/moj.modules/moj.repeat-application.js
  assets/js/moj/moj.modules/moj.dashboard.js
  assets/js/moj/moj.modules/moj.ui-behaviour.js
  assets/js/moj/moj.modules/moj.applicant.js
  assets/js/moj/moj.modules/moj.polyfill.js
  assets/js/moj/moj.modules/moj.single-use.js
  assets/js/moj/moj.modules/moj.analytics.js
  assets/js/moj/moj.modules/moj.cookie-consent.js
  assets/js/main.js
)
> public/assets/v2/js/application.js
for f in "${JS_FILES[@]}"; do
  cat "$f" >> public/assets/v2/js/application.js
  printf ';\n' >> public/assets/v2/js/application.js
done

# Check if we're in CI mode
if [[ "${NODE_ENV}" == "CI" ]] || [[ "${BUILD_ENV}" == "CI" ]]; then
  echo "→ Minifying JavaScript files (CI mode)..."

  # Minify main application bundle
  npx esbuild public/assets/v2/js/application.js \
    --minify \
    --outfile=public/assets/v2/js/application.min.js

  # Minify individual scripts
  npx esbuild assets/js/opg/session-timeout-init.js \
    --minify \
    --outfile=public/assets/v2/js/opg/session-timeout-init.min.js

  npx esbuild assets/js/opg/dashboard-statuses.js \
    --minify \
    --outfile=public/assets/v2/js/opg/dashboard-statuses.min.js

  npx esbuild assets/js/opg/init-polyfill.js \
    --minify \
    --outfile=public/assets/v2/js/opg/init-polyfill.min.js

  echo "✓ JavaScript minification complete"
else
  echo "→ Development mode - copying without minification..."

  # In dev mode, just copy files
  cp public/assets/v2/js/application.js public/assets/v2/js/application.min.js
  cp assets/js/opg/session-timeout-init.js public/assets/v2/js/opg/session-timeout-init.min.js
  cp assets/js/opg/dashboard-statuses.js public/assets/v2/js/opg/dashboard-statuses.min.js
  cp assets/js/opg/init-polyfill.js public/assets/v2/js/opg/init-polyfill.min.js

  echo "✓ JavaScript files copied (dev mode)"
fi

echo "→ Copying vendor files..."

# Copy govuk frontend JavaScript
cp node_modules/govuk-frontend/dist/govuk/govuk-frontend.min.js public/assets/v2/js/

# Copy govuk init script
cp assets/js/opg/govuk-init.js public/assets/v2/js/

echo "✓ Vendor files copied"

echo "======================================"
echo "✓ JavaScript build complete!"
echo "======================================"
