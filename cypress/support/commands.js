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

Cypress.Commands.add("OPGGetLpaId", () => {
    // This relies upon alias @donorPageUrl, which is set when we visit the donor page
    // LpaID will therefore always reflect the lpaid last time the donor page was visited.
    cy.get('@donorPageUrl').then(($url) => {
        var lpaId = $url.match(/\/(\d+)\//)[1];
        cy.log("lpa id is " + lpaId );
    });
});

// Print cypress-axe violations to the terminal
function printAccessibilityViolations(violations) {
  cy.task(
    'table',
    violations.map(({ id, impact, description, nodes }) => ({
      impact,
      description: `${description} (${id})`,
      nodes: nodes.length,
    })),
  );
}

