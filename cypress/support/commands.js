const { PDFDocument } = require("pdf-lib");
const axeWrapper = require("./axe_wrapper");
const { dimensionsMismatchError, pixelsMismatchError } = require('./constants');

Cypress.Commands.add("runPythonApiCommand", (pythonCommand) => {
    cy.exec('python3 tests/python-api-client/' + pythonCommand, {failOnNonZeroExit: false}).then(result => {
        if (result.code !== 0) {
            throw new Error(
                'Call to API failed' +
                    '\ncommand: ' + pythonCommand +
                    '\ncode: ' + result.code +
                    '\nstdout: ' + (result.stdout || '<EMPTY>') +
                    '\nstderr: ' + (result.stderr || '<EMPTY>')
            )
        }

        return cy.wrap(result)
    })
});

Cypress.Commands.add("visitWithChecks", (url, options) => {
    options = options || {};
    cy.visit(url, options);
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
});

// window: DOM window instance
// options: passed directly to axe
// url: URL to potentially check
// stopOnError: boolean, default=false; if true, if any violations are
//     found, an exception is thrown, stopping the test
Cypress.Commands.add("runAxe", (window, options, url, stopOnError) => {
    stopOnError = !!stopOnError;

    // wrap runAxe so that cypress understands the promise it returns
    cy.wrap(axeWrapper.run(window, options, url)).then((results) => {
        // wrap this so that all the cy.task('log', ...) calls complete before
        // throwing the error; without this, the error is thrown before
        // the logging is completed
        cy.wrap(axeWrapper.logResults(results, (msg) => {
            cy.task('log', msg);
        }))
        .then(() => {
            // throw an error to stop the test if configured to;
            // otherwise we just see log messages and the test continues
            if (stopOnError && results.violations.size > 0) {
                throw new Error('accessibility violations caused test to fail');
            }
        });
    });
});

/**
 * axeOptions: passed direct to cy.runAxe
 * stopOnError: set to true if any accessibility violation found should
 * result in a test failure
 * pageState, if set, is appended to the URL passed to runAxe after replacing
 * spaces with hyphens; this allows us to test the same URL multiple times if a
 * page has multiple states, e.g. with/without open popup
 */
Cypress.Commands.add("OPGCheckA11y", (axeOptions, stopOnError, pageState) => {
    axeOptions = axeOptions || {};
    stopOnError = !!stopOnError;

    cy.url().then((url) => {
        if (pageState !== undefined) {
            url += ':' + pageState.replace(' ', '-');
        }

        cy.window({log: false}).then((window) => {
            cy.runAxe(window, axeOptions, url, stopOnError);
        });
    });
});

Cypress.Commands.add("OPGCheckA11yWithUrl", (url) => {
    if (!Cypress.env("a11yCheckedPages").has(url)) {
        cy.OPGCheckA11y();
        Cypress.env("a11yCheckedPages").add(url);
    }
});

Cypress.Commands.add("checkPdf", (candidateString) => {
    let arrBuf = new TextEncoder().encode(candidateString);

    return PDFDocument.load(arrBuf, {ignoreEncryption: true}).then(
        // resolved
        (doc) => {
            return doc.getPages().length > 0;
        },

        // rejected
        () => {
            return false;
        }
    );
});

/**
 * Custom command for visual regression testing
 * Usage: cy.visualSnapshot('login-page')
 */
Cypress.Commands.add('visualSnapshot', (pageName, options = {}) => {
    const {
        threshold = 0.1,
        failOnMismatch = true
    } = options;

    const {snapshotPath, baselinePath, diffPath} = takeScreenshot(pageName);

    // Wait for screenshot to complete
    cy.wait(100);

    cy.task('compareScreenshots', {
        baselinePath,
        snapshotPath,
        diffPath,
        threshold
    }).then((result) => {
        if (result.error && failOnMismatch) {
            const suffix = result.error === pixelsMismatchError ? 'pixels diff' : 'dimensions diff';
            const source = result.error === pixelsMismatchError ? diffPath : snapshotPath;

            return cy.task('moveFile', {
                source,
                destination: `${snapshotPath.split('.png')[0]} (${suffix}).png`
            }).then(() => {
                throw new Error(result.message);
            });
        }

        if (!result.error) {
            return cy.task('deleteFile', snapshotPath);
        }
    });
});


/**
 * Update baseline image for a specific test
 */
Cypress.Commands.add('updateBaseline', (pageName) => {
    const {snapshotPath, baselinePath} = takeScreenshot(pageName);

    // Wait for screenshot to complete, then update baseline
    cy.wait(100).then(() => {
        return cy.task('updateBaselineScreenshot', { snapshotPath, baselinePath });
    }).then(() => {
        cy.log(`Baseline updated for: ${baselinePath}`);
    });
});

function takeScreenshot(pageName) {
    const screenshotsDir = Cypress.config('screenshotsFolder');
    const regressionsDir = screenshotsDir.split('/screenshots')[0] + '/regressions';
    const specName = Cypress.spec.name.replace('.cy.js', '');
    let specNameAndTest = `${pageName} - ${Cypress.currentTest.title}`

    cy.screenshot(specNameAndTest, {
        overwrite: true,
        capture: 'fullPage'
    });

    return {
        snapshotPath: `${screenshotsDir}/${specName}/${specNameAndTest}.png`,
        baselinePath: `${regressionsDir}/baseline/${specName}/${specNameAndTest}.png`,
        diffPath: `${regressionsDir}/diff/${specName}/${specNameAndTest}.png`,
    }
}
