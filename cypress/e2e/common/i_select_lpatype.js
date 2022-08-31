import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then(`I choose Property and Finance`, () => {
    cy.get("[data-cy=type-property-and-financial]").should('not.be.disabled').check().should('be.checked');
})

Then(`I choose Health and Welfare`, () => {
    cy.get("[data-cy=type-health-and-welfare]").should('not.be.disabled').check().should('be.checked');
})
