import { Then } from "cypress-cucumber-preprocessor/steps";

Then(`I choose Property and Finance`, () => {
    Cypress.$("[data-cy=type-property-and-financial]").click();
});

Then(`I choose Health and Welfare`, () => {
    Cypress.$("[data-cy=type-health-and-welfare]").click();
});
