import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`the page matches the {string} baseline image`, (pageName) => {
  if (Cypress.expose('visualRegressionEnabled')) {
    if (Cypress.expose('updateBaseline')) {
      cy.log(`Updating baseline for ${pageName}`);
      cy.updateBaseline(pageName);
    }

    cy.visualSnapshot(pageName);
  } else {
    cy.log(
      `Skipped visual regression testing for ${pageName}. Pass --expose visualRegressionEnabled="1" to enable it.`,
    );
  }
});
