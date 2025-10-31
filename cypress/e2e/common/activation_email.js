import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I use activation email to visit the link`, () => {
  cy.visit('/signup/confirm/mywonderfultoken');
});

Then(`I use password reset email to visit the link`, () => {
  cy.visit('/signup/confirm/mywonderfultoken');
});

Then(`I use activation email for {string} to visit the link`, () => {
  cy.visit('/signup/confirm/mywonderfultoken');
});

Then(`I use password reset email for {string} to visit the link`, () => {
  cy.visit('/signup/confirm/mywonderfultoken');
});
