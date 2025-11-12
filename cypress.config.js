const { defineConfig } = require("cypress");
const createBundler = require("@bahmutov/cypress-esbuild-preprocessor");
const { addCucumberPreprocessorPlugin } = require("@badeball/cypress-cucumber-preprocessor");
const { createEsbuildPlugin } = require("@badeball/cypress-cucumber-preprocessor/esbuild");

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
  },
});
