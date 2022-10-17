import { Given } from "@badeball/cypress-cucumber-preprocessor";

Given(`I ignore application exceptions`, () => {
    Cypress.on('uncaught:exception', () => {
        return false
    })
})
