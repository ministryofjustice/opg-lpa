# Cypress visual regression testing

The cypress visual regression tests are run as part of the stitched cypress tests. They compare screenshots taken during the test runs with baseline screenshots to check for any unintended visual differences and are run in CI on PR workflows only.

During a visual regression check there are some standardisation steps that alter the DOM prior to checks to ensure that the screenshots are consistent and comparable. These are defined in `cypress/support/command.js::normalizeViewport()` and include:
- Accepting cookies to remove cookie banners if visible
- Setting last signed in and last saved to a set date
- Setting LPA id numbers to static deterministic values

To add an additional visual regression test, ensure the feature file it is added to includes the tag `@PartOfStitchedRun` and add the following with a unique page name (refer to `cypress/regressions/baseline` for existing page names):

```cypress
Then the page matches the "<UNIQUE PAGE NAME>" baseline image
```

If you need to update the baseline screenshots for the visual regression tests following template or styling updates, run `make cypress-update-all-baselines`.

If you want to run the full visual regression tests suite, run `make cypress-run-stitched-suites`
