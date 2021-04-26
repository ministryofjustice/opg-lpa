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
 * url: URL potentially being checked
 *
 * returns a structure like this:
 * {
 *   location: <url visited>,
 *   skipped: true|false, // was the test skipped for this url?
 *   violations: [<see dedupeViolations() for structure of objects in this list>, ...]
 * }
 */
function run(window, axeOptions, url) {
    // check url against env; early return if already checked this URL
    if (Cypress.env('a11yCheckedPages').has(url)) {
        const skipResults = {
            location: url,
            violations: [],
            skipped: true
        };

        logResults(skipResults, console.log);
        return skipResults;
    }

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
    return window.axe.run(axeOptions).then((axeResults) => {
        // add urlStr to env
        Cypress.env('a11yCheckedPages').add(url);

        let results = {
            location: url,
            violations: [],
            skipped: false
        };

        let violations = axeResults.violations;
        let location = window.location.href.replace(Cypress.config('baseUrl'), '');

        if (violations.length > 0) {
            // this will be logged by cy.task('log', ...) when returned
            // to the calling runAxe() command
            results.violations = dedupeViolations(violations);

            // log to the console inside the browser
            logResults(results, console.log);
        }

        return results;
    });
}

// Construct structured object for the violations;
// object returned looks like:
// [
//     { id: <id of violation>, impact: <impact of violation>,
//       description: <long description>, snippets: [ <violating node HTML>, ... ]
// ]
function dedupeViolations(violations) {
    // make a set of unique violations
    let deduped = new Set();

    violations.forEach((violation) => {
        deduped.add({
            id: violation.id,
            impact: violation.impact,
            description: violation.description,
            snippets: violation.nodes.map((node) => node.html),
        });
    });

    return deduped;
}

// If skipped, this shows the skipped URL; if tested, this shows the tested URL.
// If there were violations, this logs each object in the violations object, writing it
// using the specified log function logFn (which takes a string and writes it
// to a log location).
function logResults(results, logFn) {
    if (results.skipped) {
        logFn(`------- SKIPPED a11y check on URL ${results.location}`);
    }
    else {
        logFn(`+++++++ RAN a11y check on URL ${results.location}`);

        if (results.violations.size > 0) {
            logFn(`\n******* ACCESSIBILITY VIOLATIONS ON ${results.location}`);
            results.violations.forEach((violation) => {
                logFn(`------- ID: ${violation.id}`);
                logFn(`Impact: ${violation.impact}`);
                logFn(`Description: ${violation.description}`);
                logFn('HTML elements causing violation:');
                logFn('* ' + violation.snippets.join('\n* '));
            });
            logFn('\n');
        }
        else {
            logFn(`        NO ACCESSIBILITY VIOLATIONS ON ${results.location}`);
        }
    }
}

module.exports = {
    run: run,
    logResults: logResults
};
