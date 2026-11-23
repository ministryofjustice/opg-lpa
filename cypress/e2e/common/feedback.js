import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I select rating {string}`, (rating) => {
  cy.get('[data-cy=rating-' + rating + ']').click();
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
