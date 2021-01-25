import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]");
})

Then(`I cannot find {string}`, (object) => {
  cy.get("[data-cy=" + object + "]").should('not.exist');
})

Then(`I can find link pointing to {string}`, (linkAddr) => {
    let searchStr = 'a[href*="' + linkAddr + '"]'
    cy.get(searchStr)
})

