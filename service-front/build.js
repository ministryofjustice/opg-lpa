import esbuild from 'esbuild';
import { readFileSync, writeFileSync, mkdirSync, existsSync, readdirSync } from 'fs';
import { createRequire } from 'module';
import path from 'path';

// Use require() to load Handlebars (CommonJS) from an ES module context.
const require = createRequire(import.meta.url);
const Handlebars = require('handlebars');

// All JS files to concatenate in order, matching the Grunt concat task and build-js.sh.
// These files rely on globals (jQuery, lodash, moj) set up by earlier files in the list,
// so they cannot be bundled with esbuild's module bundler - they must be concatenated
// first, then minified as a single pre-concatenated file.
const APPLICATION_JS_FILES = [
  // Dependencies
  'node_modules/handlebars/dist/handlebars.js',
  'node_modules/lodash/lodash.js',
  'node_modules/urijs/src/URI.min.js',
  'node_modules/govuk_frontend_toolkit/javascripts/govuk/show-hide-content.js',

  // OPG Scripts
  'assets/js/opg/jquery-plugin-opg-spinner.js',
  'assets/js/opg/session-timeout-dialog.js',
  'assets/js/opg/env-vars.js',
  'assets/js/opg/cache-busting.js',

  // MoJ Scripts - Base
  'assets/js/moj/moj.js',
  'assets/js/moj/moj.helpers.js',
  'assets/js/moj/moj.cookie-functions.js',

  // LPA Scripts - Templates
  'assets/js/lpa/lpa.templates.js',

  // MoJ Scripts - Modules
  'assets/js/moj/moj.modules/moj.password.js',
  'assets/js/moj/moj.modules/moj.popup.js',
  'assets/js/moj/moj.modules/moj.help-system.js',
  'assets/js/moj/moj.modules/moj.form-popup.js',
  'assets/js/moj/moj.modules/moj.title-switch.js',
  'assets/js/moj/moj.modules/moj.postcode-lookup.js',
  'assets/js/moj/moj.modules/moj.print-link.js',
  'assets/js/moj/moj.modules/moj.person-form.js',
  'assets/js/moj/moj.modules/moj.validation.js',
  'assets/js/moj/moj.modules/moj.repeat-application.js',
  'assets/js/moj/moj.modules/moj.dashboard.js',
  'assets/js/moj/moj.modules/moj.ui-behaviour.js',
  'assets/js/moj/moj.modules/moj.applicant.js',
  'assets/js/moj/moj.modules/moj.polyfill.js',
  'assets/js/moj/moj.modules/moj.single-use.js',
  'assets/js/moj/moj.modules/moj.analytics.js',
  'assets/js/moj/moj.modules/moj.cookie-consent.js',

  // Init Script
  'assets/js/main.js',
];

function buildHandlebarsTemplates() {
  const templatesDir = 'assets/js/lpa/templates';
  const outputFile = 'assets/js/lpa/lpa.templates.js';

  const files = readdirSync(templatesDir)
    .filter(f => f.endsWith('.html'))
    .sort();

  const lines = [
    'this["lpa"] = this["lpa"] || {};',
    'this["lpa"]["templates"] = this["lpa"]["templates"] || {};',
  ];

  for (const file of files) {
    const filePath = path.join(templatesDir, file);
    const content = readFileSync(filePath, 'utf8');

    // Replicate the Grunt processName function:
    // strip everything up to and including 'templates/' then remove extension.
    const fullPath = filePath.replace(/\\/g, '/');
    const afterTemplates = fullPath.slice(fullPath.indexOf('templates/') + 'templates/'.length);
    const templateName = afterTemplates.replace(/\.[^/.]+$/, '');

    const precompiled = Handlebars.precompile(content, {});
    lines.push(`\nthis["lpa"]["templates"]["${templateName}"] = Handlebars.template(${precompiled});`);
  }

  writeFileSync(outputFile, lines.join('\n'));
  console.log(`✓ Compiled ${files.length} Handlebars templates → ${outputFile}`);
}

function buildEnvVarsContent() {
  const template = readFileSync('assets/js/opg/env-vars.template.js', 'utf8');
  return template.replace(
    'window.BUILD_ENV = {};',
    `window.BUILD_ENV = ${JSON.stringify({
      revision: process.env.REVISION || 'dev'
    })};`
  );
}

async function buildApplication() {
  console.log('Building main application bundle...');

  // Always regenerate lpa.templates.js from source HTML templates before bundling.
  buildHandlebarsTemplates();

  mkdirSync('public/assets/v2/js', { recursive: true });

  // Concatenate all files in order, separated by ';\n' to match the Grunt
  // concat separator and prevent adjacent IIFEs being parsed as call expressions.
  const parts = [];
  for (const filePath of APPLICATION_JS_FILES) {
    let content;
    if (filePath === 'assets/js/opg/env-vars.js') {
      // Inject env vars from template at build time
      content = buildEnvVarsContent();
    } else if (!existsSync(filePath)) {
      console.warn(`Warning: ${filePath} not found, skipping`);
      continue;
    } else {
      content = readFileSync(filePath, 'utf8');
    }
    parts.push(content);
  }

  const concatenated = parts.join(';\n');
  const tmpFile = 'public/assets/v2/js/application.js';
  writeFileSync(tmpFile, concatenated);

  // Minify the concatenated file with esbuild using transform() rather than build().
  // build() auto-detects the file format from disk; because the concatenated file
  // contains CommonJS patterns (module.exports / exports / require) from the
  // Handlebars webpack bundle, esbuild classifies it as CommonJS and replaces
  // top-level `this` with `undefined`. transform() has no format auto-detection
  // and treats the content as a plain script, preserving the top-level `this`
  // that Handlebars' UMD wrapper relies on to attach itself to `window`.
  const minified = await esbuild.transform(concatenated, {
    minify: true,
    sourcemap: false,
    target: ['es2015'],
    platform: 'neutral',
    loader: 'js',
  }).catch((e) => {
    console.error('Build failed:', e);
    process.exit(1);
  });

  writeFileSync('public/assets/v2/js/application.min.js', minified.code);

  console.log('✓ Main application bundle built');
}

async function buildIndividualScripts() {
  console.log('Building individual scripts...');

  mkdirSync('public/assets/v2/js/opg', { recursive: true });

  const scripts = [
    { in: 'assets/js/opg/session-timeout-init.js', out: 'public/assets/v2/js/opg/session-timeout-init.min.js' },
    { in: 'assets/js/opg/dashboard-statuses.js', out: 'public/assets/v2/js/opg/dashboard-statuses.min.js' },
    { in: 'assets/js/opg/init-polyfill.js', out: 'public/assets/v2/js/opg/init-polyfill.min.js' },
    { in: 'assets/js/opg/govuk-init.js', out: 'public/assets/v2/js/govuk-init.js' },
  ];

  for (const script of scripts) {
    await esbuild.build({
      entryPoints: [script.in],
      outfile: script.out,
      minify: true,
      sourcemap: false,
      target: ['es2015'],
      platform: 'neutral',
    });
    console.log(`✓ Built ${script.out}`);
  }
}

async function copyVendorScripts() {
  console.log('Copying vendor scripts...');

  const { copyFileSync } = await import('fs');

  copyFileSync(
    'node_modules/govuk-frontend/dist/govuk/govuk-frontend.min.js',
    'public/assets/v2/js/govuk-frontend.min.js'
  );

  console.log('✓ Vendor scripts copied');
}

// Main build orchestration
(async () => {
  const width = process.stdout.columns || 80;
  const hr = '='.repeat(Math.min(width, 80));

  console.log(hr);
  console.log('Building service-front assets');
  console.log(hr);

  try {
    await buildApplication();
    await buildIndividualScripts();
    await copyVendorScripts();

    console.log(hr);
    console.log('✓ Build complete!');
    console.log(hr);
  } catch (error) {
    console.error('Build failed:', error);
    process.exit(1);
  }
})();
