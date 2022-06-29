import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I select option {string} of {string}`, (option, object) => {
    cy.get("[data-cy=" + object + "]").select(option);
})
