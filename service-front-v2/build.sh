curl -L https://github.com/alphagov/govuk-frontend/releases/download/v3.14.0/release-v3.14.0.zip > govuk_frontend.zip
rm -rf app/static
unzip -o govuk_frontend.zip -d app/static
mv app/static/assets/* app/static
rm -rf app/static/assets
rm -rf govuk_frontend.zip

curl -L https://github.com/alphagov/govuk-frontend/archive/v3.14.0.zip > govuk_frontend_source.zip
unzip -o govuk_frontend_source.zip -d govuk_frontend_source
rm -rf govuk_components
mkdir govuk_components
mv govuk_frontend_source/govuk-frontend-3.14.0/package/govuk/components/** govuk_components
find govuk_components -type f ! -name 'fixtures.json' -delete
rm -rf govuk_frontend_source
rm -rf govuk_frontend_source.zip
