import { Given } from "cypress-cucumber-preprocessor/steps";

Given(`I sign up standard test user`, () => {
    signUp(Cypress.env("email"),Cypress.env("password"));
})

Given(`I sign up with email {string} and password {string}`, (email, password) => {
    signUp(email, password);
});

function signUp(user, password){
    cy.visit("/signup").title().should('include','Create an account');
    cy.OPGCheckA11y();
    cy.get('[data-cy=signup-email]').clear().type(user);
    cy.get('[data-cy=signup-email-confirm]').clear().type(user);
    cy.get('[data-cy=signup-password]').clear().type(password);
    cy.get('[data-cy=signup-password-confirm]').clear().type(password);
    cy.get('[data-cy=signup-terms]').check();
    cy.get('[data-cy=signup-submit-button]').click();
}
