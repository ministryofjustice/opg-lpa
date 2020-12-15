import { Given } from "cypress-cucumber-preprocessor/steps";
 
Given(`I visit {string}`, (url) => {
  cy.visit(url)
})
