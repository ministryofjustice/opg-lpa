import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I see {string} in the title`, (title) => {
  cy.title().should('include', title);
});
