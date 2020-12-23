import { When } from "cypress-cucumber-preprocessor/steps";

var newPassword = "NewPassword" + Cypress.env('userNumber');

When("I populate email fields with standard test user address", () => {
        cy.get("[data-cy=email]").type(Cypress.env("email"));
        cy.get("[data-cy=email_confirm]").type(Cypress.env("email"));
        cy.get('[data-cy=email-me-the-link]').click();
});

When("I choose a new password", () => {
        cy.get("[data-cy=password]").type(newPassword);
        cy.get("[data-cy=password_confirm]").type(newPassword);
        cy.get('[data-cy=reset-my-password]').click();
});

When("I change password back to my old one", () => {
    // change from new password back to original cypress password
        cy.get("[data-cy=password_current]").type(newPassword);
        cy.get("[data-cy=password]").type(Cypress.env('password'));
        cy.get("[data-cy=password_confirm]").type(Cypress.env('password'));
        cy.get('[data-cy=save-new-password]').click();
});

When("I log in with new password", () => {
    cy.get('[data-cy=login-email]').clear().type(Cypress.env("email"));
    cy.get('[data-cy=login-password]').clear().type(newPassword);
    cy.get('[data-cy=login-submit-button]').click();
});
