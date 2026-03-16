import esbuild from 'esbuild';
import { readFileSync, writeFileSync, mkdirSync } from 'fs';
import { dirname } from 'path';

// Environment variable injection plugin
const envVarsPlugin = {
  name: 'env-vars',
  setup(build) {
    build.onLoad({ filter: /env-vars\.js$/ }, async (args) => {
      const template = readFileSync('assets/js/opg/env-vars.template.js', 'utf8');

      // Inject REVISION if available
      let envVarsContent = template.replace(
        'window.BUILD_ENV = {};',
        `window.BUILD_ENV = ${JSON.stringify({
          revision: process.env.REVISION || 'dev'
        })};`
      );

      return {
        contents: envVarsContent,
        loader: 'js',
      };
    });
  },
};

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
    minify: true,
    sourcemap: false,
    target: ['es2015'],
    platform: 'browser',
    plugins: [envVarsPlugin, handlebarsPlugin],
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
    { in: 'assets/js/opg/govuk-init.js', out: 'public/assets/v2/js/govuk-init.js' },
  ];

  for (const script of scripts) {
    await esbuild.build({
      entryPoints: [script.in],
      outfile: script.out,
      minify: true,
      sourcemap: false,
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
