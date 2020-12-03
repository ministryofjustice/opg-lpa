import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find form button {string}`, (object) => {
    cy.get('form').within(() => {
        cy.get(object).type("yeah")
    })
})
