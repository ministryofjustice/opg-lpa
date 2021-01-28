import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I check {string}`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").check();
})
 
Then(`I check old style id {string}`, (checkable) => {
    cy.get(checkable).check();
})
