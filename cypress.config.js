const { defineConfig } = require('cypress')

module.exports = defineConfig({
  postLogoutUrl: 'https://www.gov.uk/done/lasting-power-of-attorney',
  rootRedirectUrl: 'https://www.gov.uk/power-of-attorney/make-lasting-power',
  numberOfGuidanceHelpTopics: 22,
  defaultCommandTimeout: 12000,
  requestTimeout: 12000,
  trashAssetsBeforeRuns: false,
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
    baseUrl: 'https://localhost:7002',
    specPattern: 'cypress/e2e/**/*.feature',
  },
})
