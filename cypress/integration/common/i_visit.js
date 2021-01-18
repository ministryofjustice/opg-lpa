import { Given, Then } from "cypress-cucumber-preprocessor/steps";

Given(`I visit {string}`, (url) => {
    // note that cy.visit will follow redirects, and require the status code to be 2xx after that
    cy.visit(url);
})

Then(`I visit the login page`, () => {
    cy.visit('/login');
})

Then(`I visit the type page`, () => {
    cy.visit('/lpa/type');
})
