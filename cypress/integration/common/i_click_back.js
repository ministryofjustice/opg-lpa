import { When } from "cypress-cucumber-preprocessor/steps";

When(`I click back`, () => {
    cy.go('back');
})
