import esbuild from 'esbuild';
import { readFileSync, writeFileSync, mkdirSync } from 'fs';
import { dirname } from 'path';

// Environment variable injection plugin
// Handlebars template compiler plugin 
const handlebarsPlugin = {
  name: 'handlebars-templates',
  setup(build) {
    build.onLoad({ filter: /lpa\.templates\.js$/ }, async (args) => {
      // For now, just ensure the file exists or create a placeholder
      try {
        const content = readFileSync(args.path, 'utf8');
        return { contents: content, loader: 'js' };
      } catch (e) {
        console.warn('Warning: lpa.templates.js not found, creating empty placeholder');
        return {
          contents: 'window.lpa = window.lpa || {}; window.lpa.templates = {};',
          loader: 'js'
        };
      }
    });
  },
};

async function buildApplication() {
  console.log('Building main application bundle...');

  // Main application bundle
  await esbuild.build({
    entryPoints: [
      // Create a virtual entry point that imports all files in order
      {
        in: 'assets/js/main.js',
        out: 'application'
      }
    ],
    bundle: true,
    outfile: 'public/assets/v2/js/application.min.js',
    minify: process.env.NODE_ENV === 'production',
    sourcemap: process.env.NODE_ENV !== 'production',
    target: ['es2015'],
    platform: 'browser',
    plugins: [handlebarsPlugin],
    // Inject dependencies in order
    inject: ['assets/js/shim.js'].filter(path => {
      try {
        readFileSync(path);
        return true;
      } catch {
        return false;
      }
    }),
  }).catch((e) => {
    console.error('Build failed:', e);
    process.exit(1);
  });

  console.log('✓ Main application bundle built');
}

async function buildIndividualScripts() {
  console.log('Building individual scripts...');

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
      minify: process.env.NODE_ENV === 'production',
      sourcemap: process.env.NODE_ENV !== 'production',
      target: ['es2015'],
      platform: 'browser',
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
