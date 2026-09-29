import { Given } from '@badeball/cypress-cucumber-preprocessor';

Given(`I sign up standard test user`, () => {
  signUp(Cypress.env('email'), Cypress.env('password'));
});

Given(
  `I sign up with email {string} and password {string}`,
  (email, password) => {
    signUp(email, password);
  },
);

Given(
  `I sign up {string} test user with password {string}`,
  (name, password) => {
    // Create unique identifier based on the name
    let identifier =
      '' + new Date().getTime() + Math.floor(Math.random() * 999999999);

    // Create unique email/username based on the identifier
    let user = 'caspertests+' + identifier + '@lpa.opg.service.justice.gov.uk';

    // Store the identifier, username and password in the cypress session;
    // they can be retrieved using the name+suffix in subsequent tests
    Cypress.env(name + '-identifier', identifier);
    Cypress.env(name + '-user', user);
    Cypress.env(name + '-password', password);

    signUp(user, password);
  },
);

function signUp(user, password) {
  cy.visit('/signup').title().should('include', 'Create an account');
  cy.OPGCheckA11y();
  cy.get('[data-cy=signup-email]').clear().type(user);
  cy.get('[data-cy=signup-email-confirm]').clear().type(user);
  cy.get('[data-cy=signup-password]').clear().type(password);
  cy.get('[data-cy=signup-password-confirm]').clear().type(password);
  cy.get('[data-cy=signup-terms]').check();

  logCsrfState();

  // Wait for the POST to complete before returning.
  cy.intercept('POST', '**/signup').as('signupRequest');
  cy.get('[data-cy=signup-submit-button]').click();
  cy.wait('@signupRequest')
    .then(logSignupPost)
    .its('response.statusCode')
    .should('eq', 200);
}

// TEMPORARY
const prefix = (value) => (value ? String(value).slice(0, 8) : 'none');

const lpa3From = (cookieHeader) =>
  (/(?:^|;\s*)lpa3=([^;]+)/.exec(cookieHeader || '') || [])[1];

function logCsrfState() {
  cy.getCookie('lpa3').then((cookie) =>
    cy.task(
      'log',
      `[csrf-debug] lpa3 in browser after GET: ${prefix(cookie && cookie.value)}`,
    ),
  );
  cy.get('input[name="__csrf"]')
    .invoke('val')
    .then((token) =>
      cy.task('log', `[csrf-debug] __csrf in form: ${prefix(token)}`),
    );
}

function logSignupPost(interception) {
  const { request, response } = interception;
  const postedToken = new URLSearchParams(request.body).get('__csrf');

  return cy
    .task(
      'log',
      `[csrf-debug] POST /signup sent lpa3: ${prefix(lpa3From(request.headers.cookie))}, ` +
        `__csrf: ${prefix(postedToken)}, got ${response.statusCode} ` +
        `location: ${response.headers.location || 'none'}, set-cookie: ${response.headers['set-cookie'] ? 'yes' : 'no'}`,
    )
    .then(() => interception);
}
