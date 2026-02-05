// Import commands.js using ES2015 syntax
import "./commands";

import "cypress-plugin-tab";

// userNumber applies to all feature files run during this session
// which don't perform their own sign-in
var userNumber = Cypress.env("userNumber");

Cypress.env("password", "Cypress" + userNumber);

// Email currently needs to start with caspertests (legacy test approach),
// to get written to the S3 bucket.
// Configuration for this is in email.tf.
Cypress.env("email","caspertests+" + userNumber + "@lpa.opg.service.justice.gov.uk");

Cypress.env("seeded_email", "seeded_test_user@digital.justice.gov.uk");
Cypress.env("second_seeded_email", "seeded_test_user2@digital.justice.gov.uk");
Cypress.env("seeded_password", "Pass1234");
Cypress.env("a11yCheckedPages", new Set());
Cypress.env("clonedLpa", false);

// Configure screenshots to capture full page by default
Cypress.Screenshot.defaults({
  capture: 'fullPage',
  overwrite: false,
  disableTimersAndAnimations: true,
  screenshotOnRunFailure: true,
});
