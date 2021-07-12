import { Given } from "cypress-cucumber-preprocessor/steps";
 
And(`I set cloned to true`, () => {
    Cypress.env("clonedLpa", true);
})
