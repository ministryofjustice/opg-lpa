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

// work-around for "require.resolve is not a function" error
// as per https://github.com/component-driven/cypress-axe/issues/73#issuecomment-734909801
Cypress.Commands.add("injectAxe2", () => {
  cy.window({ log: false }).then(window => {
      const axe = require('axe-core/axe.js');
      const script = window.document.createElement('script');
      script.innerHTML = axe.source;
      window.document.body.appendChild(script);
  })
});

Cypress.Commands.add("OPGCheckA11y", (skipFailures) => {
    cy.injectAxe2();
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
        // make a set of unique violations; yes, I could have been clever
        // and done it in one line, but the previous code confused me and
        // I think this makes it clearer that we're populating a set
        let reports = new Set();

        violations.forEach((violation) => {
            reports.add({
                id: violation.id,
                impact: violation.impact,
                description: violation.description,
                snippets: violation.nodes.map((node) => node.html),
            });
        });

        location = `${location}`.replace(Cypress.config('baseUrl'), '');
        cy.task('log', `\n************** ACCESSIBILITY VIOLATIONS ON ${location}`);

        reports.forEach((report) => {
            cy.task('log', `-------------- ID: ${report.id}`);
            cy.task('log', `Impact: ${report.impact}`);
            cy.task('log', `Description: ${report.description}`);
            cy.task('log', 'HTML elements causing violation:')
            cy.task('log', '* ' + report.snippets.join('\n* '));
        });

        return new Cypress.Promise((resolve, reject) => { return resolve(true); });
    });
}

