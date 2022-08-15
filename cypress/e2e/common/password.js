import { When } from "@badeball/cypress-cucumber-preprocessor";

var newPassword = "NewPassword" + Cypress.env('userNumber');

When("I populate email fields with standard test user address", () => {
        cy.get("[data-cy=email]").type(Cypress.env("email"));
        cy.get("[data-cy=email_confirm]").type(Cypress.env("email"));
        cy.get('[data-cy=email-me-the-link]').click();
        cy.OPGCheckA11y();
});

When("I choose a new password", () => {
        cy.get("[data-cy=password]").type(newPassword);
        cy.get("[data-cy=password_confirm]").type(newPassword);
        cy.get('[data-cy=reset-my-password]').click();
        cy.OPGCheckA11y();
});

When("I try to change password with a mismatch", () => {
        cy.get("[data-cy=password_current]").clear().type(Cypress.env('seeded_password'));
        cy.get("[data-cy=password]").clear().type("mismatchedpassword2345");
        cy.get("[data-cy=password_confirm]").clear().type("mismatchedpassword1234");
        cy.get('[data-cy=save-new-password]').click();
        cy.OPGCheckA11y();
});

When("I try to change password with a mismatch on forgotten password link", () => {
        cy.get("[data-cy=password]").clear().type("mismatchedpassword2345");
        cy.get("[data-cy=password_confirm]").clear().type("mismatchedpassword1234");
        cy.get('[data-cy=reset-my-password]').click();
        cy.OPGCheckA11y();
});

When("I change password back to my old one", () => {
    // change from new password back to original cypress password
        cy.get("[data-cy=password_current]").clear().type(newPassword);
        cy.get("[data-cy=password]").clear().type(Cypress.env('password'));
        cy.get("[data-cy=password_confirm]").clear().type(Cypress.env('password'));
        cy.get('[data-cy=save-new-password]').click();
        cy.OPGCheckA11y();
});

When("I try to change password to an invalid one", () => {
    // same as in 35-ChangePassword Casper test
        cy.get("[data-cy=password_current]").clear().type(newPassword);
        cy.get("[data-cy=password]").clear().type("-");
        cy.get("[data-cy=password_confirm]").clear().type("*");
        cy.get('[data-cy=save-new-password]').click();
        cy.OPGCheckA11y();
});

When("I log in with new password", () => {
        cy.get('[data-cy=login-email]').clear().type(Cypress.env("email"));
        cy.get('[data-cy=login-password]').clear().type(newPassword);
        cy.get('[data-cy=login-submit-button]').click();
        cy.OPGCheckA11y();
});

When("I try to change password for {string} with a mismatch", (name) => {
        let password = Cypress.env(name + "-password");
        cy.get("[data-cy=password_current]").clear().type(password);
        cy.get("[data-cy=password]").clear().type("mismatchedPassword2345");
        cy.get("[data-cy=password_confirm]").clear().type("mismatchedPassword1234");
        cy.get('[data-cy=save-new-password]').click();
        cy.OPGCheckA11y();
});
