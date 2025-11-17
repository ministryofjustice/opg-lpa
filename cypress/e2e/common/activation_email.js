import { Then } from '@badeball/cypress-cucumber-preprocessor';
import jsSHA from 'jssha';

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

async function openEmailAndVisitLink(type, identifier) {
  const sha1Obj = new jsSHA('SHA-1', 'TEXT', { encoding: 'UTF8' });
  sha1Obj.update(identifier);

  const activationToken = sha1Obj.getHash('HEX');

  if (type === 'passwordreset') {
    cy.visit(`/forgot-password/reset/${activationToken}`);
  }

  if (type === 'activation') {
    cy.visit(`/signup/confirm/${activationToken}`);
  }
}
