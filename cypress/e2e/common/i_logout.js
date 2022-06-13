import { When } from "cypress-cucumber-preprocessor/steps";

When(`I logout`, () => {
  cy.contains('Sign out').click();
})
