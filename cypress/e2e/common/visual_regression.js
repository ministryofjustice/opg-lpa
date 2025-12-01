import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`the page matches the {string} baseline image`, (pageName) => {
  if (Cypress.env('updateBaseline') === 1) {
    cy.log(`Updating baseline for ${pageName}`);
    cy.updateBaseline(pageName);
  }

  cy.visualSnapshot(pageName);
});
