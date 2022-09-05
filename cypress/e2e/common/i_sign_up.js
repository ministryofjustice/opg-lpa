import { Given } from "@badeball/cypress-cucumber-preprocessor";

Given(`I sign up standard test user`, () => {
    signUp(Cypress.env("email"),Cypress.env("password"));
})

Given(`I sign up with email {string} and password {string}`, (email, password) => {
    signUp(email, password);
})

Given(`I sign up {string} test user with password {string}`, (name, password) => {
    // Create unique identifier based on the name
    let identifier = "" + (new Date()).getTime() + Math.floor(Math.random() * 999999999);

    // Create unique email/username based on the identifier
    let user = "caspertests+" + identifier + "@lpa.opg.service.justice.gov.uk";

    // Store the identifier, username and password in the cypress session;
    // they can be retrieved using the name+suffix in subsequent tests
    Cypress.env(name + "-identifier", identifier);
    Cypress.env(name + "-user", user);
    Cypress.env(name + "-password", password);

    signUp(user, password);
})

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
