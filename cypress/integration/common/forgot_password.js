import { When } from "cypress-cucumber-preprocessor/steps";

When("I populate email fields with standard user address", () => {
        cy.get("[data-cy=email]").type(Cypress.env("email"));
        cy.get("[data-cy=email_confirm]").type(Cypress.env("email"));
        cy.get('[data-cy=email-me-the-link]').click();
});

