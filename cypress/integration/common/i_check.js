import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I check {string}`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").check();
})
