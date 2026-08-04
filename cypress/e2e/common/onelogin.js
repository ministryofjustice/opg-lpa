import { Before, Then } from '@badeball/cypress-cucumber-preprocessor';

let oneLoginEnabled = null;

function detectOneLoginEnabled() {
  if (oneLoginEnabled !== null) {
    return cy.wrap(oneLoginEnabled, { log: false });
  }

  return cy.request({ url: '/login', log: false }).then((response) => {
    oneLoginEnabled = response.body.includes(
      'data-cy="onelogin-signin-button"',
    );
    return oneLoginEnabled;
  });
}

Before({ tags: '@RequiresOneLogin' }, function () {
  detectOneLoginEnabled().then((enabled) => {
    if (!enabled) {
      cy.log('GOV.UK One Login is not enabled in this environment, skipping');
      this.skip();
    }
  });
});

Before({ tags: '@RequiresMockOneLogin' }, function () {
  const baseUrl = Cypress.config('baseUrl') || '';
  if (!baseUrl.includes('localhost')) {
    cy.log('Mock One Login only exists locally, skipping');
    this.skip();
  }
});

Then(`I am on the mock One Login page`, () => {
  cy.url().should('include', 'localhost:4549');
  cy.contains('Continue').should('be.visible');
});

Then(`I continue through mock One Login`, () => {
  cy.origin('http://localhost:4549', () => {
    cy.contains('Continue').click();
  });

  cy.then(() => {
    if (!(Cypress.env('a11yCheckedPages') instanceof Set)) {
      Cypress.env('a11yCheckedPages', new Set());
    }
  });
});

Then(
  `the One Login callback shows the problem page for {string}`,
  (queryString) => {
    cy.visit('/auth/redirect' + queryString, { failOnStatusCode: false });
    cy.contains('There is a problem signing you in').should('be.visible');
    cy.contains('a', 'Return to sign in').should('have.attr', 'href', '/login');
  },
);

Then(`I choose to link an existing Make account`, () => {
  cy.get('input[name="choice"][value="link"]').check();
});

Then(`I choose to create a new Make account`, () => {
  cy.get('input[name="choice"][value="create"]').check();
});

Then(`I link my seeded Make account`, () => {
  cy.get('[data-cy=login-email]').clear().type(Cypress.env('seeded_email'));
  cy.get('[data-cy=login-password]').clear().type(Cypress.env('seeded_password'));
  cy.get('[data-cy=link-account-submit]').click();
});

Then(`I attempt to link my Make account with an incorrect password`, () => {
  cy.get('[data-cy=login-email]').clear().type(Cypress.env('seeded_email'));
  cy.get('[data-cy=login-password]').clear().type('this-is-the-wrong-password');
  cy.get('[data-cy=link-account-submit]').click();
});

Then(`I am advised my Make account credentials were not recognised`, () => {
  cy.get('[data-cy=link-account-error]').should(
    'contain',
    'Email address and password combination not recognised',
  );
});

Then(`I choose to reset my Make account password`, () => {
  cy.get('[data-cy=link-account-forgot-password]').click();
});
