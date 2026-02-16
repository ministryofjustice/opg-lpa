import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`the page matches the {string} baseline image`, (pageName) => {
  if (Cypress.env('visualRegressionEnabled') === 1) {
    if (Cypress.env('updateBaseline') === 1) {
      cy.log(`Updating baseline for ${pageName}`);
      cy.updateBaseline(pageName);
    }

    cy.visualSnapshot(pageName);
  } else {
    cy.log(
      `Skipped visual regression testing for ${pageName}. Set the visualRegressionEnabled environment variable to 1 to enable it.`,
    );
  }
});
