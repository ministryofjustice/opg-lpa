const { defineConfig } = require("cypress");
const createBundler = require("@bahmutov/cypress-esbuild-preprocessor");
const { addCucumberPreprocessorPlugin } = require("@badeball/cypress-cucumber-preprocessor");
const { createEsbuildPlugin } = require("@badeball/cypress-cucumber-preprocessor/esbuild");
const fs = require("fs");
const path = require("path");
const { PNG } = require("pngjs");
const pixelmatch = require("pixelmatch").default;
const { dimensionsMismatchError, pixelsMismatchError } = require('./cypress/support/constants');

// This is used to store data between test steps; it's effectively a global
// variable container. The main purpose is to enable more natural expressions
// When fetching some content from a page Then checking it has expected
// properties.
const testStore = {}

async function setupNodeEvents(on, config) {
  await addCucumberPreprocessorPlugin(on, config);

  on(
    "file:preprocessor",
    createBundler({
      plugins: [createEsbuildPlugin(config)],
    })
  );

  on("task", {
    putValue({name, value}) {
      // prevent different tests using the same name or the same feature setting
      // the same value multiple times
      if (name in testStore) {
        throw new Error(name + ' is already set in the test store');
      }

      testStore[name] = value;
      return true;
    },

    deleteValue(key) {
      delete(testStore[key]);
      return true;
    },

    getValue(name) {
      return testStore[name];
    },

    // the following on('task') 10 lines are required for cypress.axe to use custom function to write to the log
    // and could be removed if we remove dependency on cypress-axe in future
    log(message) {
      console.log(message);
      return null;
    },

    table(message) {
      console.table(message);
      return null;
    },

    /**
     * Compare two screenshots using pixelmatch
     * @param {string} baselinePath - Path to baseline image
     * @param {string} snapshotPath - Path to current screenshot
     * @param {string} diffPath - Path to save diff image
     * @param {number} threshold - Sensitivity threshold (0-1). Smaller is more sensitive.
     *
     * @returns {object} Comparison results
     */
    compareScreenshots({ baselinePath, snapshotPath, diffPath, threshold = 0.3 }) {
      if (!fs.existsSync(snapshotPath)) {
        throw new Error(`Current screenshot not found: ${snapshotPath}`);
      }

      if (!fs.existsSync(baselinePath)) {
        console.log(`No baseline found. Creating baseline: ${baselinePath}`);
        fs.mkdirSync(path.dirname(baselinePath), { recursive: true });
        fs.copyFileSync(snapshotPath, baselinePath);
        return {
            diffPixels: 0,
            diffPercentage: 0,
            message: 'Baseline created'
        };
      }

      const baseline = PNG.sync.read(fs.readFileSync(baselinePath));
      const current = PNG.sync.read(fs.readFileSync(snapshotPath));

      if (baseline.width !== current.width || baseline.height !== current.height) {
        return {
          diffPixels: 0,
          diffPercentage: 0,
          message: `Image dimensions don't match for ${baselinePath}. Baseline: ${baseline.width}x${baseline.height}, Current: ${current.width}x${current.height}. Check for regressions or update baselines if the change is expected.`,
          error: dimensionsMismatchError
        };
      }

      const { width, height } = baseline;
      const diff = new PNG({ width, height });

      const numDiffPixels = pixelmatch(
        baseline.data,
        current.data,
        diff.data,
        width,
        height,
        {
            threshold,
            aaColor: [0, 0, 255], // set anti-aliased pixels to blue
        }
      );

      fs.mkdirSync(path.dirname(diffPath), { recursive: true });
      fs.writeFileSync(diffPath, PNG.sync.write(diff));

      const totalPixels = width * height;
      const diffPercentage = (numDiffPixels / totalPixels) * 100;

      return {
        diffPixels: numDiffPixels,
        diffPercentage: diffPercentage.toFixed(2),
        totalPixels,
        message: numDiffPixels === 0
            ? 'Screenshots match'
            : `Found ${numDiffPixels} different pixels (${diffPercentage.toFixed(2)}%) for ${baselinePath}. Check for regressions or update baselines if the change is expected.`,
        error: numDiffPixels === 0 ? '' : pixelsMismatchError
      };
    },

    updateBaselineScreenshot({ snapshotPath, baselinePath }) {
      if (!fs.existsSync(snapshotPath)) {
        throw new Error(`Current screenshot not found: ${snapshotPath}`);
      }
      fs.mkdirSync(path.dirname(baselinePath), { recursive: true });
      fs.copyFileSync(snapshotPath, baselinePath);
      return true;
    },

    deleteFile(source) {
      if (fs.existsSync(source)) {
        fs.unlinkSync(source);
        return true;
      }
      return false;
    },

    moveFile({ source, destination }) {
      if (fs.existsSync(source)) {
        fs.copyFileSync(source, destination);
        fs.unlinkSync(source);
        return true;
      }

      return false;
    }
  });

  return config;
}

module.exports = defineConfig({
  postLogoutUrl: "https://www.gov.uk/done/lasting-power-of-attorney",
  rootRedirectUrl: "https://www.gov.uk/power-of-attorney/make-lasting-power",
  numberOfGuidanceHelpTopics: 22,
  defaultCommandTimeout: 12000,
  requestTimeout: 12000,
  trashAssetsBeforeRuns: false,
  injectDocumentDomain: true,
  e2e: {
    specPattern: "cypress/e2e/**/*.feature",
    supportFile: "cypress/support/e2e.js",
    setupNodeEvents,
    screenshotOnRunFailure: false,
  },
  screenshotsFolder: 'cypress/screenshots',
  viewportWidth: 1280,
  viewportHeight: 720,
});
