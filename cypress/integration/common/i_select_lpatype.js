import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I choose Property and Finance`, () => {
    cy.get("[data-cy=type-property-and-financial]").check();
})

Then(`I choose Health and Welfare`, () => {
    cy.get("[data-cy=type-health-and-welfare]").check();
})
