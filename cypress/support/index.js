// ***********************************************************
// This example support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

// Alternatively you can use CommonJS syntax:
// require('./commands')

// pull in cypress-axe
import 'cypress-axe'

var random = Math.floor(Math.random() * 999999999);

var date = new Date();
var userNumber = date.getTime() + "" + random;
Cypress.env("userNumber", userNumber)
Cypress.env("email","cypresstests+" + userNumber + "@lpa.opg.service.justice.gov.uk")
Cypress.env("password", "Cypress" + userNumber)
Cypress.env("seeded_email","seeded_test_user@digital.justice.gov.uk");
Cypress.env("seeded_password","Pass1234");
