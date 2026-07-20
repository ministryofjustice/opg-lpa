import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I am on the mock One Login page`, () => {
  cy.url().should('include', 'localhost:4549');
  cy.contains('Continue').should('be.visible');
});
