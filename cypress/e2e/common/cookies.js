const { Then } = require("cypress-cucumber-preprocessor/steps");

Then('analytics cookies are set', () => {
  cy.getCookie('_ga').should('not.be.null');
  cy.getCookie('_gid').should('not.be.null');
});

Then('analytics cookies are not set', () => {
  cy.getCookie('_ga').should('be.null');
  cy.getCookie('_gid').should('be.null');
});

// decision: "rejected" or "accepted"
Then('I see a message that I have {string} analytics cookies', (decision) => {
  cy.get('[data-cy="cookie-preferences-save-confirm"]').contains(decision);
});

Then('I can see a hide button to close the cookies banner', () => {
  cy.get('[data-cy="hide-cookies-banner"]').should('be.visible');
});

Then('the cookie banner is not visible', () => {
  cy.get('[data-cy="cookie-message"]').should('not.be.visible');
});
