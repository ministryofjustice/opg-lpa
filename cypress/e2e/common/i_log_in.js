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

When(`I log in as second seeded user`, () => {
  logInAsSecondSeededUser();
});

When(`I log in to admin`, () => {
  logIn(
    Cypress.env('seeded_email'),
    Cypress.env('seeded_password'),
    Cypress.env('adminUrl') + '/sign-in',
  );
});

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
