import { Then } from "cypress-cucumber-preprocessor/steps";

// simulate keypress on tab
Then('I press tab', () => {
    cy.focused().tab();
});

// simulate shift+tab press
Then('I press shift+tab', () => {
    cy.focused().tab({shift: true});
});
