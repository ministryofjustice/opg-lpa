import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I am taken to {string}`, (url) => {
  cy.url().should('eq',Cypress.config().baseUrl + url);
})
