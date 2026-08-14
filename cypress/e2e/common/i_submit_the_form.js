import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I submit the form`, () => {
  cy.get('main [type="submit"]:visible')
    .should('not.be.disabled')
    .click();
});
