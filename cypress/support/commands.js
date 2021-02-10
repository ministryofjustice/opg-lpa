// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
//
Cypress.Commands.add("OPGCheckA11y", (skipFailures) => {
    cy.injectAxe();
    cy.checkA11y(null, null, printAccessibilityViolations, true);
});

Cypress.Commands.add("getLpaId", () => {
    cy.get('@donorPageUrl').then((donorPageUrl) => {
        return donorPageUrl.match(/\/(\d+)\//)[1];
    });
});

// Print cypress-axe violations to the terminal
function printAccessibilityViolations(violations) {
    cy.location().then((location) => {
        cy.task(
            'table',
            violations.map(({ id, impact, description, nodes }) => ({
                impact,
                location: `${location}`.replace(Cypress.config('baseUrl'), ''),
                description: `${description} (${id})`,
                nodes: nodes.length,
            })),
        );
    });
}

