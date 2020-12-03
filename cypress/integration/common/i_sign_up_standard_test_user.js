import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I sign up standard test user`, () => {
  cy.get("input#email.form-control").type(Cypress.env("email"))
  cy.get("input#password.form-control").type(Cypress.env("password"))
})
