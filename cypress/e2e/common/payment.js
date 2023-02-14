import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I complete the payment with card number {string}`, (cardNumber) => {
  const args = { cardNumber: cardNumber };

  cy.origin('http://localhost:4547', { args: args }, ({ cardNumber }) => {
    cy.get('#card-no').type(cardNumber);
  });
});
