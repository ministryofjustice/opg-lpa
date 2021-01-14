import { Given } from "cypress-cucumber-preprocessor/steps";

Given('I visit the ping page', (path) => {
    // note that cy.visit will follow redirects, and require the status code to be 2xx after that
    cy.visit('/ping');
})

Given('I visit the ping JSON endpoint', (path) => {
    cy.request('/ping/json').then((response) => {
        expect(response.status).to.eq(200);

        // TODO response.body.api.ok is currently false but should
        // be true in the live environment; we can't test it here as it
        // will fail in the dev environment
        expect(response.body.dynamo.ok).to.eq(true);
    });
})
