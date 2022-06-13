import { Given } from "cypress-cucumber-preprocessor/steps";

And(`I ignore application exceptions`, () => {
    Cypress.on('uncaught:exception', (err, runnable) => {
        return false
    })
})
