import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I type {string} into {string}`, (text, object) => {
  cy.get(object).type(text)
})
