import { Then } from "cypress-cucumber-preprocessor/steps";

// should('be.checked')  or not checked exists here to ensure that cypress doesn't race off
// and carry out the next operation without making sure first that the check or uncheck has taken effect

Then(`I check {string}`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").check().should('be.checked');
})
 
Then(`I check occurrence {int} of radio button`, (seq) => {
    cy.get('[type="radio"]').eq(seq).check().should('be.checked')
})
Then(`I check occurrence {int} of checkbox`, (seq) => {
    cy.get('[type="checkbox"]').eq(seq).check().should('be.checked')
})

Then(`I uncheck {string}`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").uncheck().should('not.be.checked');
})

Then(`{string} is checked`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").should('be.checked');
})
