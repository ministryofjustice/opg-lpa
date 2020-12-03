import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I visit link containing {string}`, (linkText) => {
  cy.contains(linkText).click();
})
