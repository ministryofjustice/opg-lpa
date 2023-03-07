import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I complete the payment with card number {string}`, (cardNumber) => {
  const args = { cardNumber: cardNumber };

  cy.origin('http://localhost:4547', { args: args }, ({ cardNumber }) => {
    // we have to define this inside the origin() call, otherwise
    // it's not visible inside this function
    const setValue = (fieldId, value) => {
      cy.get('#' + fieldId).then((field) => {
        Cypress.$(field).attr('value', value);
      });
    };

    // note that country is already selected as United Kingdom by default
    setValue('card-no', cardNumber);

    const now = new Date();

    // month is zero-indexed, hence the "+ 1"
    const thisMonth = '' + (now.getMonth() + 1);
    setValue('expiry-month', thisMonth);

    const nextYear = '' + (now.getFullYear() + 1);
    setValue('expiry-year', nextYear.substr(2));

    setValue('cardholder-name', 'Mrs E Wright');
    setValue('cvc', '111');
    setValue('address-line-1', '999 Made Up Lane');
    setValue('address-city', 'Birmingham');
    setValue('address-postcode', 'BB1 1BB');
    setValue('email', 'mrsewright@notmadeup.really');

    cy.get('#submit-card-details').click();
  });
});

Then(`I confirm the payment`, () => {
  cy.origin('http://localhost:4547', () => {
    cy.get('#confirm').click();
  });
});
