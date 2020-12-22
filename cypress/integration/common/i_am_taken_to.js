import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I am taken to {string}`, (url) => {
  cy.url().should('eq',Cypress.config().baseUrl + url);
})
 
Then(`I am taken to the post logout url`, () => {
  cy.log("I should be on " + Cypress.config().postLogoutUrl );
  cy.url().should('eq',Cypress.config().postLogoutUrl );
})
