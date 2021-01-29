import { Then } from "cypress-cucumber-preprocessor/steps";

Then('I wait for focus on {string}', (focusable) => {
    cy.get("[data-cy=" + focusable + "]").focus();
})

Then('I am focused on {string}', (focusable) => {
    cy.focused().then((el) => {
        expect(Cypress.$(el).attr('data-cy')).to.equal(focusable);
    });
})
