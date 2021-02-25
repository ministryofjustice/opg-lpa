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

Cypress.Commands.add("getLpaId", () => {
    cy.get('@donorPageUrl').then((donorPageUrl) => {
        return donorPageUrl.match(/\/(\d+)\//)[1];
    });
});

Cypress.Commands.add("OPGCheckA11y", () => {
    cy.window({ log: false }).then((window) => {
        if (Cypress.$('#axe').length < 1) {
            const script = window.document.createElement('script');
            script.id = 'axe';
            script.async = false;
            script.innerHTML = require('axe-core/axe.js').source;
            window.document.body.appendChild(script);
        }

        let logFn = (msg) => {
            Cypress.log({name: 'axe', message: msg});
        };

        window.axe.run().then((results) => {
            let violations = results.violations;

            if (violations.length > 0) {
                showAccessibilityViolations(violations, logFn);
            }
        });
    });
});

// Show axe accessibility violations on the terminal
function showAccessibilityViolations(violations, logFn) {
    let location = window.location.href;

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

    logFn(`\n************** ACCESSIBILITY VIOLATIONS ON ${location}`);

    reports.forEach((report) => {
        logFn(`-------------- ID: ${report.id}`);
        logFn(`Impact: ${report.impact}`);
        logFn(`Description: ${report.description}`);
        logFn('HTML elements causing violation:');
        logFn('* ' + report.snippets.join('\n* '));
    });
}
