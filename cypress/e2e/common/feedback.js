const path = require('path');
import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I can find feedback buttons`, () => {
  cy.get('[data-cy=rating-very-satisfied]');
  cy.get('[data-cy=rating-satisfied]');
  cy.get('[data-cy=rating-neither-satisfied-or-dissatisfied]');
  cy.get('[data-cy=rating-dissatisfied]');
  cy.get('[data-cy=rating-very-dissatisfied]');

  cy.get('[data-cy=feedback-textarea]');
  cy.get('[data-cy=feedback-email]');
  cy.get('[data-cy=feedback-phone]');
});

Then(`I select rating {string}`, (rating) => {
  cy.get('[data-cy=rating-' + rating + ']').click();
});

Then(`I can see that rating {string} is selected`, (rating) => {
  cy.get('[data-cy=rating-' + rating + ']').should('be.checked');
});

Then(`I set feedback content as {string}`, (email) => {
  cy.get('[data-cy=feedback-textarea]').type(email);
});

Then(`I set feedback email as {string}`, (email) => {
  cy.get('[data-cy=feedback-email]').type(email);
});

Then(`I submit the feedback`, () => {
  cy.get('[data-cy=feedback-submit-button]').click();
  cy.OPGCheckA11y();
});

Then(
  `I expect submitted feedback form to contain a rating of {string}`,
  (rating) => {
    cy.intercept('POST', '/send-feedback', (req) => {
      expect(req.body).to.include('rating=' + rating);
      req.continue();
    });
  },
);

Then(`I can export feedback and download it as a CSV file`, () => {
  cy.intercept('POST', '/feedback?export=true', (req) => {
    req.continue((res) => {
      const contentDisposition = res.headers['content-disposition'];
      const downloadedFile = contentDisposition.split('filename=')[1];
      const downloadsFolder = Cypress.config('downloadsFolder');
      cy.readFile(path.join(downloadsFolder, downloadedFile)).should('exist');
    });
  });

  // work-around for cypress bug when downloading files from a link;
  // see https://github.com/cypress-io/cypress/issues/7083#issuecomment-858489694
  cy.document().then((doc) => {
    doc.addEventListener('click', () => {
      setTimeout(function () {
        doc.location.reload();
      }, 1000);
    });
  });

  cy.contains('Export').click();
});
