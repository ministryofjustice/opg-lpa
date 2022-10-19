import { When } from '@badeball/cypress-cucumber-preprocessor';

When(`I click back`, () => {
  cy.go('back');
});
