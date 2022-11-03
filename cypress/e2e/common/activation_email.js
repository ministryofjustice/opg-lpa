import { Then } from '@badeball/cypress-cucumber-preprocessor';

var link = null;
var activation_email_path = 'cypress/activation_emails/';

Then(`I use activation email to visit the link`, () => {
  openEmailAndVisitLink('activation');
});

Then(`I use password reset email to visit the link`, () => {
  openEmailAndVisitLink('passwordreset');
});

Then(`I use activation email for {string} to visit the link`, (name) => {
  openEmailAndVisitLink('activation', Cypress.env(name + '-identifier'));
});

Then(`I use password reset email for {string} to visit the link`, (name) => {
  openEmailAndVisitLink('passwordreset', Cypress.env(name + '-identifier'));
});

function openEmailAndVisitLink(type, identifier) {
  if (!identifier) {
    identifier = Cypress.env('userNumber');
  }

  var filename = activation_email_path + identifier + '.' + type;
  cy.log('Trying to open: ' + filename);

  cy.readFile(filename, { timeout: 100000 }).then((text) => {
    var content = text;
    cy.log(text);
    cy.log('Orig Content: ' + content);
    var contentStr = content;

    cy.log(type + ' email has arrived!');

    cy.log('Content: ' + contentStr);
    console.log('Content: ');
    console.log(contentStr);
    link = contentStr.substring(contentStr.indexOf(',') + 1);
    cy.log('Opening ' + type + ' link: ' + link);
    cy.visit(link);
  });
}
