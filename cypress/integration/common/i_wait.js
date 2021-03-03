import { Then } from "cypress-cucumber-preprocessor/steps";

// wait for a number of seconds
Then('I wait for {int} seconds', (seconds) => {
    cy.wait(seconds * 1000);
});
