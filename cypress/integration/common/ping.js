import { Given } from "cypress-cucumber-preprocessor/steps";

Given('I visit the ping page', (path) => {
    // note that cy.visit will follow redirects, and require the status code to be 2xx after that
    cy.visit('/ping');
})
