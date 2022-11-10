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
