import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I sign up standard test user`, () => {
    cy.visit("/signup").title().should('include','Create an account');
    cy.get("input#email.form-control").type(Cypress.env("email"));
    cy.get("input#email_confirm.form-control").type(Cypress.env("email"));
    cy.get("input#password.form-control").type(Cypress.env("password"));
    cy.get("input#password_confirm.form-control").type(Cypress.env("password"));
    cy.get("input#terms").check();
    cy.get("input#signin-form-submit.button").click();
})
