import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then(`I select option {string} of {string}`, (option, object) => {
    cy.get("[data-cy=" + object + "]").select(option);
})
