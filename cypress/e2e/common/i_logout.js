import { When } from '@badeball/cypress-cucumber-preprocessor';

When(`I logout`, () => {
  cy.contains('Sign out').click();
});
