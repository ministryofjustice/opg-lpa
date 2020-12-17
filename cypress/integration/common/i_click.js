import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I click {string}`, (clickable) => {
    cy.get(clickable).click();
})
