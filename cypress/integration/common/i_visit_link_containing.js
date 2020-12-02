import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I visit link containing {string}`, (hrefText) => {
  let searchStr = 'a[href*="' + hrefText + '"]' 
  cy.get(searchStr).click()
})
