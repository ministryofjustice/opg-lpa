import { Given } from "cypress-cucumber-preprocessor/steps";
 
Given(`I sign up standard test user`, () => {
    signUp(Cypress.env("email"),Cypress.env("password"));
})

function signUp(user, password){
    cy.visit("/signup").title().should('include','Create an account');
    cy.get('[data-cy=signup-email]').clear().type(user);
    cy.get('[data-cy=signup-email-confirm]').clear().type(user);
    cy.get('[data-cy=signup-password]').clear().type(password);
    cy.get('[data-cy=signup-password-confirm]').clear().type(password);
    cy.get('[data-cy=signup-terms]').check();
    cy.get('[data-cy=signup-submit-button]').click();
}
