import { Then } from '@badeball/cypress-cucumber-preprocessor';
import { USER_TYPES, sayIMakeLpasFor } from '../../support/onboarding';

const WHICH_BEST_DESCRIBES_YOU = '/which-best-describes-you';

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
  cy.get('[data-cy="error-summary"]')
    .should('be.visible')
    .and('contain', 'Select which best describes you');
  cy.get('[data-cy="form-error"]').should('contain', 'Select which best describes you');
  cy.OPGCheckA11y();
});

Then(`I go back to which best describes me`, () => {
  cy.visit(WHICH_BEST_DESCRIBES_YOU);
});

Then(`my answer that I make LPAs for {string} is still selected`, (description) => {
  cy.get(`[data-cy="userType-${USER_TYPES[description]}"]`).should('be.checked');
});

Then(`I see the One Login header but not the Make navigation`, () => {
  cy.get('[data-cy="one-login-header"]').should('be.visible');
  cy.get('[data-cy="service-nav-list"]').should('not.exist');
  cy.get('[data-cy="last-signed-in"]').should('not.exist');
});
