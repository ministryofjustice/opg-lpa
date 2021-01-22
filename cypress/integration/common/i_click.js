import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I click {string}`, (clickable) => {
    cy.get("[data-cy=" + clickable + "]").click();
    cy.OPGCheckA11y();
})
