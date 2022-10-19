import { Then } from '@badeball/cypress-cucumber-preprocessor';

// wait for a number of seconds
Then('I wait for {int} seconds', (seconds) => {
  cy.wait(seconds * 1000);
});
