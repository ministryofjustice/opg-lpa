import { When } from "cypress-cucumber-preprocessor/steps";

When(`I log in as standard test user`, () => {
    cy.get("input#email.form-control").clear().type(Cypress.env("email"));
    cy.get("input#password.form-control").clear().type(Cypress.env("password"));
    cy.get('input#signin-form-submit.button').click()
})
