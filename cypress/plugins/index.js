/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
const cucumber = require('cypress-cucumber-preprocessor').default;

// This is used to store data between test steps; it's effectively a global
// variable container. The main purpose is to enable more natural expressions
// When fetching some content from a page Then checking it has expected
// properties.
const testStore = {}

module.exports = (on, config) => {
  // `on` is used to hook into various events Cypress emits
  // `config` is the resolved Cypress config
  on('file:preprocessor', cucumber());

  on('task', {
    putValue({name, value}) {
      // prevent different tests using the same name or the same feature setting
      // the same value multiple times
      if (name in testStore) {
        throw new Error(name + ' is already set in the test store');
      }

      testStore[name] = value
      return true
    }
  });

  on('task', {
    getValue(name) {
      return testStore[name]
    }
  });

  return config;
}
