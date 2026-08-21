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

Then(`I am returned to the appropriate page shown after a password reset`, () => {
  detectOneLoginEnabled().then((enabled) => {
    const expected = enabled ? '/home' : '/login';
    cy.url().should('eq', Cypress.config().baseUrl + expected);
    cy.OPGCheckA11y();
  });
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

Then(`I am signed in with my new Make account`, () => {
  cy.get('[data-cy=sign-out]').should('be.visible');
});

const ONELOGIN_LINK_ACCOUNTS = {
  link: 'onelogin_link_email',
  retry: 'onelogin_retry_email',
  forgot: 'onelogin_forgot_email',
};

function oneLoginLinkEmail(account) {
  const envKey = ONELOGIN_LINK_ACCOUNTS[account];
  if (!envKey) {
    throw new Error(`Unknown One Login link account: ${account}`);
  }
  return Cypress.env(envKey);
}

Then(`I link the {string} Make account`, (account) => {
  cy.get('[data-cy=login-email]').clear().type(oneLoginLinkEmail(account));
  cy.get('[data-cy=login-password]').clear().type(Cypress.env('seeded_password'));
  cy.get('[data-cy=link-account-submit]').click();
});

Then(
  `I attempt to link the {string} Make account with an incorrect password`,
  (account) => {
    cy.get('[data-cy=login-email]').clear().type(oneLoginLinkEmail(account));
    cy.get('[data-cy=login-password]').clear().type('this-is-the-wrong-password');
    cy.get('[data-cy=link-account-submit]').click();
  },
);

Then(`I am advised my Make account credentials were not recognised`, () => {
  cy.get('[data-cy=link-account-error]').should(
    'contain',
    'Email address and password combination not recognised',
  );
});

Then(`I choose to reset my Make account password`, () => {
  cy.get('[data-cy=link-account-forgot-password]').click();
});

Then(
  `I attempt to link a Make account already linked to another One Login`,
  () => {
    cy.get('[data-cy=login-email]')
      .clear()
      .type(Cypress.env('already_linked_email'));
    cy.get('[data-cy=login-password]').clear().type('any-password-here-123');
    cy.get('[data-cy=link-account-submit]').click();
  },
);

Then(`I am advised my account could not be linked`, () => {
  cy.get('[data-cy=cannot-link-heading]').should(
    'contain',
    'We cannot link this account',
  );
});

Then(`I choose to try again`, () => {
  cy.get('[data-cy=cannot-link-try-again]').click();
});
