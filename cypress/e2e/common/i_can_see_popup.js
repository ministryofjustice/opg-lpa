import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I can see popup`, () => {
  // in ideal world we should look for data-cy=popup but this is not simple to implement
  cy.get('[data-cy=popup]');
});

Then(`I cannot see popup`, () => {
  cy.get('[data-cy=popup]').should('not.exist');
});
