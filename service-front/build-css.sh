#!/bin/bash

set -e

echo "======================================"
echo "Building CSS assets"
echo "======================================"

echo "→ Compiling Sass files..."
npx sass \
  --load-path=node_modules/govuk_frontend_toolkit/stylesheets \
  --load-path=node_modules/govuk-elements-sass/public/sass \
  --no-source-map \
  assets/sass/application.scss:public/assets/v2/css/application.css \
  assets/sass/download-message.scss:public/assets/v2/css/download-message.css \
  assets/sass/print.scss:public/assets/v2/css/print.css

echo "Copying vendor CSS files..."

# Copy govuk template CSS
# TODO maybe we should not overwrite the ones that are there already in git and cannot be taken out
cp node_modules/govuk_template_mustache/assets/stylesheets/govuk-template-print.css public/assets/v2/css/
cp node_modules/govuk_template_mustache/assets/stylesheets/govuk-template.css public/assets/v2/css/
cp node_modules/govuk-frontend/dist/govuk/govuk-frontend.min.css public/assets/v2/css/

echo "Patching colours in CSS files..."

# Replace image-url helper (compass dependency)
for file in public/assets/v2/css/*.css; do
  if [[ -f "$file" ]]; then
    # Replace image-url with url
    sed -i.bak 's/image-url/url/g' "$file" && rm "${file}.bak" || true

    # Replace deprecated focus colour (ffbf47 -> ffdd00)
    sed -i.bak 's/ffbf47/ffdd00/g' "$file" && rm "${file}.bak" || true
    sed -i.bak 's/FFBF47/FFDD00/g' "$file" && rm "${file}.bak" || true

    # Replace deprecated button colour (00823b -> 00703c)
    sed -i.bak 's/00823b/00703c/g' "$file" && rm "${file}.bak" || true
    sed -i.bak 's/00823B/00703C/g' "$file" && rm "${file}.bak" || true
  fi
done

echo "Minifying CSS files..."
for file in public/assets/v2/css/*.css; do
  if [[ -f "$file" ]] && [[ ! "$file" =~ \.min\.css$ ]]; then
    base="${file%.css}"
    # Use esbuild for minification (it handles CSS too)
    npx esbuild "$file" --minify --outfile="${base}.min.css"
  fi
done

echo "Copying fonts..."
cp -r node_modules/govuk-frontend/dist/govuk/assets/fonts/* public/assets/v2/fonts/ 2>/dev/null || true
