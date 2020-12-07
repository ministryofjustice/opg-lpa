import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find {string}`, (object) => {
  cy.get(object);
})

Then(`I can find link pointing to {string}`, (linkAddr) => {
    let searchStr = 'a[href*="' + linkAddr + '"]'
    cy.get(searchStr)
})

