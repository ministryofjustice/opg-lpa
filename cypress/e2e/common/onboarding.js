import { Then } from '@badeball/cypress-cucumber-preprocessor';
import {
  checkAnswerIsSelected,
  checkSignedIn,
  MY_DETAILS,
  checkFormError,
  enterMyAddressManually,
  enterMyDetails,
  saveMyDetails,
  sayIMakeLpasFor,
  sayWhetherIHaveAnAccount,
  sayWhetherIKeepMyLpas,
} from '../../support/onboarding';

const WHICH_BEST_DESCRIBES_YOU = '/which-best-describes-you';
const EXISTING_ACCOUNT = '/existing-account';
const YOUR_DETAILS = '/your-details';
const KEEP_YOUR_LPAS = '/keep-your-lpas';
const LINK_ACCOUNT_RESET_PASSWORD = '/link-account/reset-password';

Then(`I am asked which best describes me`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + WHICH_BEST_DESCRIBES_YOU);
  cy.contains('h1', 'Which best describes you?').should('be.visible');
  cy.OPGCheckA11y();
});

Then(`I say I make LPAs for {string}`, sayIMakeLpasFor);

Then(`I continue without saying which best describes me`, () => {
  cy.get('[data-cy="which-best-describes-you-continue"]').click();
});

Then(`I am told to select which best describes me`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + WHICH_BEST_DESCRIBES_YOU);
  checkFormError('Select which best describes you');
});

Then(`I go back to which best describes me`, () => {
  cy.visit(WHICH_BEST_DESCRIBES_YOU);
});

Then(`my answer that I make LPAs for {string} is still selected`, checkAnswerIsSelected);

Then(`I see the One Login header but not the Make navigation`, () => {
  cy.get('[data-cy="one-login-header"]').should('be.visible');
  cy.get('[data-cy="service-nav-list"]').should('not.exist');
  cy.get('[data-cy="last-signed-in"]').should('not.exist');
});

Then(`I am asked whether I have an existing Make account`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + EXISTING_ACCOUNT);
  cy.contains('h1', 'Do you have an existing Make a lasting power of attorney account?').should('be.visible');
  cy.OPGCheckA11y();
});

Then(`I say {string}`, sayWhetherIHaveAnAccount);

Then(`I continue without saying whether I have an existing Make account`, () => {
  cy.get('[data-cy="existing-account-continue"]').click();
});

Then(`I am told to select whether I have an existing Make account`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + EXISTING_ACCOUNT);
  checkFormError('Select whether you have an existing Make a lasting power of attorney account');
});

Then(`my answer {string} is still selected`, checkAnswerIsSelected);

Then(`I go back`, () => {
  cy.get('[data-cy="back-link"]').click();
});

Then(`I am asked for my details`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + YOUR_DETAILS);
  cy.contains('h1', 'Your details').should('be.visible');
  cy.get('[data-cy="save"]').should('have.text', 'Save');
  cy.get('a[href="/user/delete"]').should('not.exist');
  // The postcode lookup script adds this link, so wait for it before checking what the script shows
  cy.contains('a', 'Search for UK postcode').should('be.visible');
  cy.contains('a', 'Enter address manually').should('not.be.visible');
  cy.get('[data-cy="address-address1"]').should('be.visible');
  cy.get('[data-cy="address-postcode"]').should('be.visible');
  cy.OPGCheckA11y();
});

Then(`I enter my name and date of birth and save`, () => {
  enterMyDetails();
  saveMyDetails();
});

Then(`I save my details without my name`, () => {
  enterMyDetails({ ...MY_DETAILS, 'name-first': ' ', 'name-last': ' ' });
  enterMyAddressManually();
  saveMyDetails();
});

Then(`I am told to enter my name`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + YOUR_DETAILS);
  cy.get('[data-cy="error-summary"]')
    .should('be.visible')
    .and('contain', 'Enter your first names')
    .and('contain', 'Enter your last name');
  cy.OPGCheckA11y();
});

Then(`my new Make account has my details`, () => {
  checkSignedIn();
  cy.visit('/user/about-you');
  cy.get('[data-cy="name-first"]').should('have.value', MY_DETAILS['name-first']);
  cy.get('[data-cy="name-last"]').should('have.value', MY_DETAILS['name-last']);
});

Then(`I can look up my address while onboarding`, () => {
  cy.request({ url: '/address-lookup?postcode=', followRedirect: false }).then((response) => {
    expect(response.status).to.eq(200);
    expect(response.body).to.have.property('success');
  });
});

Then(`I am asked whether I want to keep my existing LPAs`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + KEEP_YOUR_LPAS);
  cy.contains('h1', 'Keeping your lasting powers of attorney (LPAs) and account information').should('be.visible');
  cy.get('.govuk-warning-text').should('contain', 'you will lose access to that information permanently');
  cy.OPGCheckA11y();
});

Then(`I say {string} to keeping my existing LPAs`, sayWhetherIKeepMyLpas);

Then(`I continue without saying whether I want to keep my existing LPAs`, () => {
  cy.get('[data-cy="keep-your-lpas-continue"]').click();
});

Then(`I am told to select whether I want to keep my existing LPAs`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + KEEP_YOUR_LPAS);
  checkFormError('Select whether you want to keep your existing LPAs and account information');
});

Then(`my answer {string} to keeping my existing LPAs is still selected`, checkAnswerIsSelected);

Then(`I am asked to sign in to my existing Make account`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + '/link-account');
  cy.contains('h1', 'Enter the sign-in details for your existing Make a lasting power of attorney account').should(
    'be.visible',
  );
  cy.get('[data-cy="link-account-submit"]').should('have.text', 'Keep LPAs and account information');
  cy.OPGCheckA11y();
});

Then(`I am asked for the email address of my existing Make account`, () => {
  cy.url().should('eq', Cypress.config().baseUrl + LINK_ACCOUNT_RESET_PASSWORD);
  cy.contains('h1', 'Reset the password for your existing Make a lasting power of attorney account').should(
    'be.visible',
  );
  cy.get('[data-cy="email-me-the-link"]').should('have.text', 'Email me the link');
  cy.OPGCheckA11y();
});

Then(`I ask for a password reset link for {string} confirmed as {string}`, (email, confirmation) => {
  if (email) {
    cy.get('[data-cy=email]').clear().type(email);
  }
  if (confirmation) {
    cy.get('[data-cy=email_confirm]').clear().type(confirmation);
  }
  cy.get('[data-cy=email-me-the-link]').click();
});

Then(`I try to sign in to my existing Make account as {string} with no password`, (email) => {
  if (email) {
    cy.get('[data-cy=login-email]').clear().type(email);
  }
  cy.get('[data-cy=link-account-submit]').click();
});

Then(`I see the error {string}`, (message) => {
  checkFormError(message);
});
