import { createHash } from 'crypto';
import { Then } from '@badeball/cypress-cucumber-preprocessor';

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

function openEmailAndVisitLink(type, identifier) {
  const hash = createHash('sha1');
  hash.update(identifier);
  const activationToken = hash.digest('hex');

  if (type === 'passwordreset') {
    cy.visit(`/forgot-password/reset/${activationToken}`);
  }

  if (type === 'activation') {
    cy.visit(`/signup/confirm/${activationToken}`);
  }
}
