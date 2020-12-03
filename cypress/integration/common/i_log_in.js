import { When } from "cypress-cucumber-preprocessor/steps";

Then(`I log in with user {string} password {string}`, (user, password) => {
    cy.get("input#email.form-control").clear().type(user);
    cy.get("input#password.form-control").clear().type(password);
    cy.get('input#signin-form-submit.button').click()
})
