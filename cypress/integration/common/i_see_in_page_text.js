import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I see {string} in the page text`, (text) => {
  cy.contains(text);
})
 
Then(`I see standard test user in the page text`, () => {
  cy.contains(Cypress.env("email"));
})
