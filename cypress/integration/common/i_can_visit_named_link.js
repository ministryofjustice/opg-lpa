import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can visit link named {string}`, (linkName) => {
  cy.get(linkName).click()
})
