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

const axeWrapper = require('./axe_wrapper');

Cypress.Commands.add("runPythonApiCommand", (pythonCommand) => {
    cy.exec('python3 tests/python-api-client/' + pythonCommand)
});

Cypress.Commands.add("visitWithChecks", (url) => {
    cy.visit(url);
    cy.document().then(doc => {
        expect(doc.documentElement.innerHTML).not.to.contain("Oops", "CSRF token mismatch problem detected");

        // check that the page title matches the content of the h1 element on
        // the page
        const heading = doc.querySelector("h1");
        const title = doc.head.querySelector("title");
        if (heading && title) {
            expect(title.text).to.contain(heading.textContent.trim());
        }
    });
    if (!Cypress.env("a11yCheckedPages").has(url)) {
        cy.OPGCheckA11y();
        Cypress.env("a11yCheckedPages").add(url);
    }
});

// window: DOM window instance
// options: passed directly to axe
// stopOnError: boolean, default=false; if true, if any violations are
//     found, an exception is thrown, stopping the test
Cypress.Commands.add("runAxe", (window, options, stopOnError) => {
    stopOnError = !!stopOnError;

    // wrap runAxe so that cypress understands the promise it returns
    cy.wrap(axeWrapper.runAxe(window, options)).then((violations) => {
        if (violations != null) {
            // wrap this so that all the cy.task('log', ...) calls complete before
            // throwing the error; without this, the error is thrown before
            // the logging is completed
            cy.wrap(axeWrapper.logViolations(violations, (msg) => {
                cy.task('log', msg);
            }))
            .then(() => {
                // throw an error to stop the test if configured to;
                // otherwise we just see log messages and the test continues
                if (stopOnError) {
                    throw new Error('accessibility violations caused test to fail');
                }
            });
        }
    });
});

Cypress.Commands.add("OPGCheckA11y", () => {
    cy.window({ log: false }).then((window) => {
        cy.runAxe(window);
    });
});
