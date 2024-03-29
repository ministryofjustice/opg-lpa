import { When } from '@badeball/cypress-cucumber-preprocessor';

When('I try to change email address with a mismatch', () => {
  cy.get('[data-cy=password_current]')
    .clear()
    .type(Cypress.env('seeded_password'));
  cy.get('[data-cy=email]').clear().type('anewemail@digital.justice.gov.uk');
  cy.get('[data-cy=email_confirm]')
    .clear()
    .type('mismatched@digital.justice.gov.uk');
  cy.get('[data-cy=save-new-email]').click();
});

When('I try to change email address for {string} with a mismatch', (name) => {
  let password = Cypress.env(name + '-password');
  cy.get('[data-cy=password_current]').clear().type(password);
  cy.get('[data-cy=email]').clear().type('anewemail@digital.justice.gov.uk');
  cy.get('[data-cy=email_confirm]')
    .clear()
    .type('mismatched@digital.justice.gov.uk');
  cy.get('[data-cy=save-new-email]').click();
});

When('I try to change to invalid email address', () => {
  cy.get('[data-cy=password_current]')
    .clear()
    .type(Cypress.env('seeded_password'));
  cy.get('[data-cy=email]').clear().type('notavalidaddress');
  cy.get('[data-cy=email_confirm]').clear().type('notavalidaddress');
  cy.get('[data-cy=save-new-email]').click();
});

When('I try to change email address correctly', () => {
  cy.get('[data-cy=password_current]')
    .clear()
    .type(Cypress.env('seeded_password'));
  cy.get('[data-cy=email]').clear().type('anewemail@digital.justice.gov.uk');
  cy.get('[data-cy=email_confirm]')
    .clear()
    .type('anewemail@digital.justice.gov.uk');
  cy.get('[data-cy=save-new-email]').click();
});
