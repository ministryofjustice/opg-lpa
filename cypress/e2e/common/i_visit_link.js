import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I visit link named {string}`, (linkName) => {
  cy.get(linkName).click();
  cy.OPGCheckA11y();
});

Then(`I visit link containing {string}`, (linkText) => {
  cy.contains(linkText).click();
  cy.OPGCheckA11y();
});

// Test whether we can visit this link in a new tab. First we ensure there
// is indeed a target _blank, thats enough to say this would open in a new tab
// then remove that before clicking it. Since Cypress doesn't support
// multiple tabs, we then visit this link in the parent window
//  clearly, this is not 100% the same as the user journey, extra testing
//  may need doing by hand on some pages that open in tabs

Then(`I visit link in new tab containing {string}`, (linkText) => {
  cy.contains(linkText)
    .should('have.attr', 'target', '_blank')
    .invoke('removeAttr', 'target')
    .click();
  cy.OPGCheckA11y();
});

Then(`I visit in new tab link named {string}`, (linkName) => {
  cy.get(linkName)
    .should('have.attr', 'target', '_blank')
    .invoke('removeAttr', 'target')
    .click();
  cy.OPGCheckA11y();
});

Then(`I visit link with text {string} in a new tab`, (text) => {
  cy.contains(text)
    .should('have.attr', 'target', '_blank')
    .invoke('removeAttr', 'target')
    .click();
  cy.OPGCheckA11y();
});
