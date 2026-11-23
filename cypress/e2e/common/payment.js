import { Given, Then } from '@badeball/cypress-cucumber-preprocessor';
import { repairA11yCheckedPagesAfterOrigin } from '../../support/a11y_checked_pages';

const payUrl = () => Cypress.env('govukPayUrl');

const DECLINED_CARD_NUMBER = '4000000000000002';

const completePayment = (cardNumber) => {
  const args = { cardNumber: cardNumber };

  cy.origin(payUrl(), { args: args }, ({ cardNumber }) => {
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
    setValue('expiry-year', nextYear.substring(2));

    setValue('cardholder-name', 'Mrs E Wright');
    setValue('cvc', '111');
    setValue('address-line-1', '999 Made Up Lane');
    setValue('address-city', 'Birmingham');
    setValue('address-postcode', 'BB1 1BB');
    setValue('email', 'simulate-delivered@notifications.service.gov.uk');

    cy.get('#submit-card-details').click();
  });
};

Then(`I complete the payment with card number {string}`, completePayment);

const confirmPayment = () => {
  cy.origin(payUrl(), () => {
    // #return-url is real Pay's "Continue" after a cancelled payment
    cy.get('#confirm, #return-url').click();
  });
};

Then(`I confirm the payment`, confirmPayment);

Given(`I pay by card but never return to the service`, () => {
  cy.intercept(
    { url: /\/checkout\/pay\/response/ },
    { statusCode: 200, body: '' },
  ).as('payResponse');

  cy.get('[data-cy="confirm-and-pay-by-card"]').click();
  completePayment('4444333322221111');
  confirmPayment();
  cy.wait('@payResponse');

  repairA11yCheckedPagesAfterOrigin();
});

const unsuccessfulPayBy = (action) => {
  cy.origin(payUrl(), () => {
    cy.get('body').then(
      ($body) => $body.find('button[name="action"]').length > 0,
    );
  }).then((isMock) => {
    if (isMock) {
      cy.origin(payUrl(), { args: { action } }, ({ action }) => {
        cy.get(`button[name="action"][value="${action}"]`).click();
      });
    } else if (action === 'fail') {
      completePayment(DECLINED_CARD_NUMBER);
    } else {
      cy.origin(payUrl(), () => {
        cy.get('#cancel-payment').click();
      });
    }
  });

  repairA11yCheckedPagesAfterOrigin();
};

Then(`I cancel the payment`, () => unsuccessfulPayBy('cancel'));

Then(`I fail the payment`, () => unsuccessfulPayBy('fail'));
