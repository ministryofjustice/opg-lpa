import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I visit link named {string}`, (linkName) => {
  cy.get(linkName).click()
})
