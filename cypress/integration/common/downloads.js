import { When } from "cypress-cucumber-preprocessor/steps";
const path = require('path')

const downloadsFolder = 'cypress/downloads'

Then(`I can download {string}`, (fileName) => {
    const downloadedFilename = path.join(downloadsFolder, fileName)

    // ensure the file has been saved before trying to parse it
    cy.readFile(downloadedFilename, 'binary', { timeout: 15000 })
    .should((buffer) => {
      // by having length assertion we ensure the file has text
      // since we don't know when the browser finishes writing it to disk

      // use expect() form to avoid dumping binary contents
      // of the buffer into the Command Log
      expect(buffer.length).to.be.gt(100)
    })
})
