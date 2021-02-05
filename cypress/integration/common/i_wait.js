import { Then } from "cypress-cucumber-preprocessor/steps";

// simulate keypress on tab
Then('I wait for {int} seconds', (seconds) => {
    cy.wait(seconds * 1000);
});
