/**
 * Wrapper for axe accessibility testing library.
 * Provides functionality to enable injection of axe into pages under
 * test in Cypress, followed by running an a11y audit on the page.
 */

/**
 * Inject axe into the page and run a11y audit
 *
 * window: browser window instance, typically derived via cy.window()
 * axeOptions: object containing options to pass directly to axe.run();
 *     see axe documentation for details
 */
function runAxe(window, axeOptions) {
    axeOptions = axeOptions || {};

    // only inject axe if <script id="axe"> isn't in the page already
    if (Cypress.$('#axe').length < 1) {
        const script = window.document.createElement('script');
        script.id = 'axe';
        script.async = false;
        script.innerHTML = require('axe-core/axe.js').source;
        window.document.body.appendChild(script);
    }

    // axe adds itself as a property on the window object
    return window.axe.run(axeOptions).then((results) => {
        let violations = results.violations;
        let location = window.location.href.replace(Cypress.config('baseUrl'), '');

        if (violations.length > 0) {
            let dedupedViolations = dedupeViolations(location, violations);

            // log to the console inside the browser
            logViolations(dedupedViolations, console.log);

            // this will be logged by cy.task('log', ...)
            return dedupedViolations;
        }

        return null;
    });
}

// Construct structured object for the violations;
// object returned looks like:
// { location: "<browser location where violations occurred>",
//   reports: [
//     { id: <id of violation>, impact: <impact of violation>,
//       description: <long description>, snippets: [ <violating node HTML>, ... ]
//   ] }
function dedupeViolations(location, violations) {
    // make a set of unique violations
    let reports = new Set();

    violations.forEach((violation) => {
        reports.add({
            id: violation.id,
            impact: violation.impact,
            description: violation.description,
            snippets: violation.nodes.map((node) => node.html),
        });
    });

    return {
        location: location,
        reports: reports
    };
}

// violations is a de-duplicated violations object, in the format
// produced by dedupeViolations;
// this calls logFn(string) for each string in the violations object, writing it
// to the desired output log
function logViolations(violations, logFn) {
    logFn(`\n******* ACCESSIBILITY VIOLATIONS ON ${violations.location}`);
    violations.reports.forEach((report) => {
        logFn(`------- ID: ${report.id}`);
        logFn(`Impact: ${report.impact}`);
        logFn(`Description: ${report.description}`);
        logFn('HTML elements causing violation:');
        logFn('* ' + report.snippets.join('\n* '));
    });
}

module.exports = {
    runAxe: runAxe,
    logViolations: logViolations
};
