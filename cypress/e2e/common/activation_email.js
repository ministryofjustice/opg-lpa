import { Then } from '@badeball/cypress-cucumber-preprocessor';
import { openEmailAndVisitLink } from '../../support/reset_link';

Then(`I use activation email to visit the link`, () => {
  openEmailAndVisitLink('activation', Cypress.env('email'));
});

Then(`I use password reset email to visit the link`, () => {
  openEmailAndVisitLink('passwordreset', Cypress.env('email'));
});

Then(`I use activation email for {string} to visit the link`, (name) => {
  openEmailAndVisitLink('activation', Cypress.env(name + '-user'));
});

Then(`I use password reset email for {string} to visit the link`, (name) => {
  openEmailAndVisitLink('passwordreset', Cypress.env(name + '-user'));
});
