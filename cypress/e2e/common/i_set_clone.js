import { Given } from "@badeball/cypress-cucumber-preprocessor";

Given(`I set cloned to true`, () => {
    Cypress.env("clonedLpa", true);
})
