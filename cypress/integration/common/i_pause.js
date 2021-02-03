import { Then } from "cypress-cucumber-preprocessor/steps";

// Pause.  This is not recommended best practice but due to Cypress being too quick at doing certain things
// we do this in (minimal possible) places until we have fixed underlying issues
Then('I pause', () => {
    cy.wait(100);
});
