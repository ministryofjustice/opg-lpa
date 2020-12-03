import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I see standard test user in the page text`, () => {
  cy.get('[class="text"]').contains(Cypress.env("email"));
})
