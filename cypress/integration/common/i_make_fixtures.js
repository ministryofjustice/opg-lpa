import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I make fixtures`, () => {
    cy.exec("python3 cypress/testFixtures.py");
})
