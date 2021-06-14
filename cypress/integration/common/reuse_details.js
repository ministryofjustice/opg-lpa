import { Then } from "cypress-cucumber-preprocessor/steps";

// should('be.checked')  or not checked exists here to ensure that cypress doesn't race off
// and carry out the next operation without making sure first that the check or uncheck has taken effect

Then(`I opt not to re-use details`, (checkable) => {
    cy.get("[data-cy=reuse-details--1]").check().should('be.checked');
})
 
Then(`I opt not to re-use details if lpa is a clone`, (checkable) => {
    if (Cypress.env('clonedLpa') === true) {
      cy.get("[data-cy=reuse-details--1]").check().should('be.checked');
      cy.get("[data-cy=continue]").click();
    }
})
 
