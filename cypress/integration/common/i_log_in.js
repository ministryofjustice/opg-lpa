import { When } from "cypress-cucumber-preprocessor/steps";

When(`I log in`, () => {
    cy.get("input#email.form-control").clear().type("seeded_test_user@digital.justice.gov.uk");
    cy.get("input#password.form-control").clear().type("Pass1234");
    cy.get('input#signin-form-submit.button').click()
})
