import { When } from '@badeball/cypress-cucumber-preprocessor';

When(`I log in as {string} test user`, (name) => {
  let user = Cypress.env(name + '-user');
  let password = Cypress.env(name + '-password');
  logIn(user, password);
});

When(`I log in with user {string} password {string}`, (user, password) => {
  logIn(user, password);
});

When(`I log in as seeded user`, () => {
  logInAsSeededUser();
});

When(`I log in as seeded user on the current page`, () => {
  cy.title().then((title) => {
    expect(title.toLowerCase()).to.include('sign in');
  });

  cy.get('[data-cy=login-email]').clear().type(Cypress.env('seeded_email'));
  cy.get('[data-cy=login-password]').clear().type(Cypress.env('seeded_password'));
  cy.get('[data-cy=login-submit-button]').click();
});

When(`I log in as second seeded user`, () => {
  logInAsSecondSeededUser();
});

When(`I log in to admin using SSO`, () => {
  if (Cypress.env('CI')) {
    // Running against deployed infra: the ALB redirects unauthenticated requests to
    // Cognito's hosted UI, which is a different origin, so real credentials must be
    // entered there before being redirected back to the admin app.
    logInToAdminViaCognitoHostedUi();
  } else {
    // Local dev: AlbSimulatorMiddleware injects a mock ALB token automatically, so
    // there's no real Cognito hosted UI to authenticate against — visiting /sign-in
    // lands the user straight on an authenticated page.
    cy.visitWithChecks(Cypress.env('adminUrl') + '/sign-in');
  }
});

function logInToAdminViaCognitoHostedUi() {
  const username = Cypress.env('admin_cognito_username');
  const password = Cypress.env('admin_cognito_password');
  const adminUrl = Cypress.env('adminUrl');

  cy.visit(adminUrl + '/sign-in');

  cy.url().then((url) => {
    if (url.startsWith(adminUrl)) {
      // Already authenticated: the ALB's own session cookie from an earlier login
      // within this test is still valid, so there's no OAuth flow to complete.
      // Forcing a redundant Cognito authorize here (by clearing cookies and
      // re-authenticating) causes an infinite ALB<->Cognito redirect loop
      return;
    }

    // Not yet authenticated (redirected to Cognito). Clear any cookies left over from
    // a different scenario's OAuth transaction (e.g. a stale Cognito state/CSRF
    // cookie) before authenticating, then reload to start a clean attempt.
    cy.clearCookies();
    cy.visit(adminUrl + '/sign-in');

    // The Cognito hosted-UI page renders both a mobile and a desktop form (toggled
    // via Bootstrap's visible-xs/visible-md classes) — only one is actually visible
    // at the configured viewport size, so select on visibility rather than DOM order.
    cy.get('input[name="username"]:visible').type(username);
    cy.get('input[name="password"]:visible').type(password);
    cy.get('input[name="signInSubmitButton"]:visible').click();

    // Cognito redirects back to the ALB, which sets its auth cookie and forwards the
    // request on to the originally requested admin page.
    cy.url().should('include', adminUrl);
  });
}

When(`I log in as standard test user`, () => {
  logInAsStandardUser();
});

When(`I log in as appropriate test user`, () => {
  // if we're running under CI , use the newly signed up user, otherwise, use the seeded user
  if (Cypress.env('CI')) {
    logInAsStandardUser();
  } else {
    logInAsSeededUser();
  }
});

function logInAsStandardUser() {
  // log in using the standard user that gets created by running Signup.feature
  logIn(Cypress.env('email'), Cypress.env('password'));
}

function logInAsSeededUser() {
  // log in using seeded_test_user
  logIn(Cypress.env('seeded_email'), Cypress.env('seeded_password'));
}

function logInAsSecondSeededUser() {
  // log in using seeded_test_user
  logIn(Cypress.env('second_seeded_email'), Cypress.env('seeded_password'));
}

function logIn(user, password, url) {
  if (url === undefined) {
    url = '/login';
  }
  cy.visitWithChecks(url);

  cy.title().then((title) => {
    expect(title.toLowerCase()).to.include('sign in');
  });

  cy.get('[data-cy=login-email]').clear().type(user);
  cy.get('[data-cy=login-password]').clear().type(password);
  cy.get('[data-cy=login-submit-button]').click();
}
