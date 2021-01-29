import { Then } from "cypress-cucumber-preprocessor/steps";

// simulate keypress on tab
Then('I press tab', () => {
    cy.focused().tab();
});
