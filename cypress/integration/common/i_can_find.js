import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find {string}`, (object) => {
  cy.get(object);
})
