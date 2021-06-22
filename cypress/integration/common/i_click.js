import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I click {string}`, (clickable) => {
    cy.get("[data-cy=" + clickable + "]").click();
    cy.OPGCheckA11y();
})

Then(`I click occurrence {int} of {string}`, (number, clickable) => {
    cy.get("[data-cy=" + clickable + "]").eq(number).click();
    cy.OPGCheckA11y();
})

Then(`I force click {string}`, (clickable) => {
    cy.get("[data-cy=" + clickable + "]").click({ force: true });
    cy.OPGCheckA11y();
})

Then(`I click element marked {string}`, (text) => {
    cy.contains(text).click();
    cy.OPGCheckA11y();
})

// this step exists because newly signed-up user goes straight to type page whereas existing user may get taken to dashboard
Then(`If I am on dashboard I click to create lpa`, () => {
    cy.url().then(urlStr => {
        if (urlStr.includes('dashboard')) {
            cy.get("[data-cy=createnewlpa]").click();
            cy.OPGCheckA11y();
        }
    });
})

