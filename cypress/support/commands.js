const { PDFDocument } = require("pdf-lib");
const axeWrapper = require("./axe_wrapper");
const { dimensionsMismatchError, pixelsMismatchError } = require('./constants');

Cypress.Commands.add("runPythonApiCommand", (pythonCommand) => {
    cy.exec('python3 tests/python-api-client/' + pythonCommand, {failOnNonZeroExit: false}).then(result => {
        if (result.exitCode !== 0) {
            throw new Error(
                'Call to API failed' +
                    '\ncommand: ' + pythonCommand +
                    '\ncode: ' + result.exitCode +
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

    // SMART PAGE LOAD MONITORING (Enabled in CI)
    // Polls every 2 seconds to check if resources are still loading
    // Exits early once all resources complete (no forced waits!)
    // Logs details if page takes >10s or resources are stalled
    const monitorPageLoad = () => {
        cy.window({ log: false }).then((win) => {
            const startTime = Date.now();
            const maxMonitorTime = 55000; // Monitor for max 55 seconds
            const pollInterval = 2000; // Check every 2 seconds
            let hasLoggedWarning = false;

            const checkResources = () => {
                const elapsed = Date.now() - startTime;

                if (elapsed > maxMonitorTime) {
                    // Stop monitoring after 55 seconds
                    return;
                }

                if (win.performance && win.performance.getEntriesByType) {
                    try {
                        const resources = win.performance.getEntriesByType('resource');
                        const pending = resources.filter(r => r.responseEnd === 0);
                        const slow = resources.filter(r => r.responseEnd > 0 && r.duration > 5000);

                        // If all resources loaded quickly, stop monitoring immediately
                        if (pending.length === 0 && slow.length === 0 && elapsed < 5000) {
                            return; // Early exit - page loaded fast!
                        }

                        // If all resources completed (even if slow), stop monitoring
                        if (pending.length === 0 && elapsed > 10000) {
                            if (!hasLoggedWarning && slow.length > 0) {
                                // Log once if there were slow resources
                                let messages = [`\n========== PAGE LOAD COMPLETED (${Math.round(elapsed/1000)}s) ==========`];
                                messages.push(`URL: ${win.location.href}`);
                                messages.push(`Slow resources detected: ${slow.length}`);
                                slow.forEach(r => {
                                    messages.push(`  [${Math.round(r.duration)}ms] ${r.initiatorType}: ${r.name}`);
                                });
                                cy.task('log', messages.join('\n'), { log: false });
                            }
                            return; // Early exit - all done!
                        }

                        // Log warning at 10 seconds if there are still issues
                        if (elapsed > 10000 && !hasLoggedWarning && (pending.length > 0 || slow.length > 0)) {
                            hasLoggedWarning = true;
                            let messages = [`\n========== PAGE LOAD WARNING (${Math.round(elapsed/1000)}s) ==========`];
                            messages.push(`URL: ${win.location.href}`);
                            messages.push(`Total: ${resources.length}, Pending: ${pending.length}, Slow: ${slow.length}`);

                            if (pending.length > 0) {
                                messages.push(`\nPENDING RESOURCES:`);
                                pending.forEach(r => {
                                    messages.push(`  [STALLED] ${r.initiatorType}: ${r.name}`);
                                });
                            }

                            if (slow.length > 0) {
                                messages.push(`\nSLOW RESOURCES (>5s):`);
                                slow.forEach(r => {
                                    messages.push(`  [${Math.round(r.duration)}ms] ${r.initiatorType}: ${r.name}`);
                                });
                            }

                            cy.task('log', messages.join('\n'), { log: false });
                        }

                        // Log detailed report at 50s if still having issues
                        if (elapsed > 50000 && pending.length > 0) {
                            let messages = [`\n========== PAGE LOAD ERROR (${Math.round(elapsed/1000)}s) ==========`];
                            messages.push(`RESOURCES STILL PENDING: ${pending.length}`);
                            pending.forEach(r => {
                                messages.push(`  [STALLED 50s+] ${r.initiatorType}: ${r.name}`);
                            });

                            messages.push(`\nALL RESOURCES (first 30):`);
                            resources.slice(0, 30).forEach(r => {
                                const status = r.responseEnd === 0 ? 'PENDING' : `${Math.round(r.duration)}ms`;
                                messages.push(`  [${status}] ${r.name.substring(0, 80)}`);
                            });

                            cy.task('log', messages.join('\n'), { log: false });
                            return; // Stop after 50s report
                        }

                        // Continue monitoring if resources are still pending
                        if (pending.length > 0 && elapsed < maxMonitorTime) {
                            cy.wait(pollInterval, { log: false }).then(checkResources);
                        }
                    } catch (e) {
                        // Silently ignore errors
                    }
                }
            };

            // Start monitoring with a small initial delay
            cy.wait(pollInterval, { log: false }).then(checkResources);
        });
    };

    // Run monitoring
    monitorPageLoad();
});

/**
 * Custom command to simulate slow network by delaying specific resources
 * Usage: cy.simulateSlowNetwork({ js: 15000, css: 3000 })
 */
Cypress.Commands.add("simulateSlowNetwork", (delays = {}) => {
    const defaultDelays = {
        js: 15000,      // JavaScript files
        css: 5000,      // CSS files
        img: 2000,      // Images
        font: 3000,     // Fonts
        other: 1000     // Other resources
    };

    const settings = { ...defaultDelays, ...delays };

    // Intercept JavaScript files
    if (settings.js > 0) {
        cy.intercept('GET', '**/*.js', (req) => {
            req.on('response', (res) => {
                res.setDelay(settings.js);
            });
        }).as('slowJs');
    }

    // Intercept CSS files
    if (settings.css > 0) {
        cy.intercept('GET', '**/*.css', (req) => {
            req.on('response', (res) => {
                res.setDelay(settings.css);
            });
        }).as('slowCss');
    }

    // Intercept images
    if (settings.img > 0) {
        cy.intercept('GET', /\.(png|jpg|jpeg|gif|svg|webp|ico)$/i, (req) => {
            req.on('response', (res) => {
                res.setDelay(settings.img);
            });
        }).as('slowImages');
    }

    // Intercept fonts
    if (settings.font > 0) {
        cy.intercept('GET', /\.(woff|woff2|ttf|eot)$/i, (req) => {
            req.on('response', (res) => {
                res.setDelay(settings.font);
            });
        }).as('slowFonts');
    }

    cy.log('Slow network simulation enabled', settings);
});

/**
 * Custom command to simulate a resource that never completes
 */
Cypress.Commands.add("simulateStalledResource", (urlPattern) => {
    cy.intercept('GET', urlPattern, (req) => {
        // Never respond - this will cause the request to hang indefinitely
        req.on('response', (res) => {
            res.setDelay(120000); // 2 minute delay (longer than page load timeout)
        });
    }).as('stalledResource');

    cy.log(`Stalled resource simulation enabled for: ${urlPattern}`);
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

/**
 * Custom command to monitor page load performance and log stalled resources
 * This should be called AFTER a page navigation to monitor its load progress
 */
Cypress.Commands.add("monitorPageLoad", () => {
  cy.window({ log: false }).then((win) => {
    // Get initial performance data
    cy.task('log', `\n========== MONITORING PAGE LOAD: ${win.location.href} ==========`, { log: false });

    // Check after 10 seconds
    cy.wait(10000, { log: false }).then(() => {
      let messages = [`\n--- 10 SECOND CHECK ---`];
      messages.push(`readyState: ${win.document.readyState}`);

      if (win.performance && win.performance.getEntriesByType) {
        try {
          const resources = win.performance.getEntriesByType('resource');
          const pending = resources.filter(r => r.responseEnd === 0);
          const slow = resources.filter(r => r.responseEnd > 0 && r.duration > 5000);

          messages.push(`Total resources: ${resources.length}`);
          messages.push(`Pending: ${pending.length}, Slow (>5s): ${slow.length}`);

          if (pending.length > 0) {
            messages.push(`\nPENDING RESOURCES:`);
            pending.forEach(r => {
              messages.push(`  [STALLED] ${r.initiatorType}: ${r.name}`);
            });
          }

          if (slow.length > 0) {
            messages.push(`\nSLOW RESOURCES:`);
            slow.forEach(r => {
              messages.push(`  [${Math.round(r.duration)}ms] ${r.initiatorType}: ${r.name}`);
            });
          }
        } catch (e) {
          messages.push(`Error: ${e.message}`);
        }
      }

      cy.task('log', messages.join('\n'), { log: false });
    });

    // Check after 50 seconds total
    cy.wait(40000, { log: false }).then(() => {
      let messages = [`\n--- 50 SECOND CHECK ---`];
      messages.push(`readyState: ${win.document.readyState}`);

      if (win.performance && win.performance.getEntriesByType) {
        try {
          const resources = win.performance.getEntriesByType('resource');
          const pending = resources.filter(r => r.responseEnd === 0);

          messages.push(`\nRESOURCES STILL PENDING AFTER 50s: ${pending.length}`);
          pending.forEach(r => {
            messages.push(`  [STALLED 50s+] ${r.initiatorType}: ${r.name}`);
          });

          // Show all resources for complete picture
          messages.push(`\nALL RESOURCES (first 30):`);
          resources.slice(0, 30).forEach(r => {
            const status = r.responseEnd === 0 ? 'PENDING' : `${Math.round(r.duration)}ms`;
            messages.push(`  [${status}] ${r.initiatorType}: ${r.name.substring(0, 80)}`);
          });
          if (resources.length > 30) {
            messages.push(`  ... and ${resources.length - 30} more`);
          }
        } catch (e) {
          messages.push(`Error: ${e.message}`);
        }
      }

      cy.task('log', messages.join('\n'), { log: false });
    });
  });
});
