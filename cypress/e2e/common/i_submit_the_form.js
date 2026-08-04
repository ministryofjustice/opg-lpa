import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then(`I submit the form`, () => {
    cy.get('button[type="submit"]:visible, input[type="submit"]:visible')
        .should('not.be.disabled')
        .click();
});
