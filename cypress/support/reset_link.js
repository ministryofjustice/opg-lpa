import jsSHA from 'jssha';

export function tokenFor(email) {
  const sha1Obj = new jsSHA('SHA-1', 'TEXT', { encoding: 'UTF8' });
  sha1Obj.update(email);
  return sha1Obj.getHash('HEX');
}

export function openEmailAndVisitLink(type, identifier) {
  const token = tokenFor(identifier);

  if (type === 'passwordreset') {
    cy.visit(`/forgot-password/reset/${token}`);
  }

  if (type === 'sharedspacepasswordreset') {
    cy.visit(`/forgot-password/reset/sharedspace${token}`);
  }

  if (type === 'activation') {
    cy.visit(`/signup/confirm/${token}`);
  }
}
