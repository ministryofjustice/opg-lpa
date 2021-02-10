import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I choose Property and Finance`, () => {
    //    ultimately this wants to use a data-cy tag, but that is not straightforward to add in the twig/php
    cy.get('#type').check();
})

Then(`I choose Health and Welfare`, () => {
    //    ultimately this wants to use a data-cy tag, but that is not straightforward to add in the twig/php
    cy.get('#type-health-and-welfare').check();
})
