import { Given, Then } from "cypress-cucumber-preprocessor/steps";

Given(`I visit {string}`, (url) => {
    // note that cy.visitWithChecks will follow redirects, and require the status code to be 2xx after that
    cy.visitWithChecks(url);
})

Then(`I visit the login page`, () => {
    cy.visitWithChecks('/login');
})

Then(`I visit the type page`, () => {
    cy.visitWithChecks('/lpa/type');
})

// The reason the step below exists is that a newly Signed-up user gets taken 
// automatically to type page on first logon, but existing test users get taken
// to dashboard, and we wish to cater for both
Then(`If I am on dashboard I visit the type page`, () => {
    cy.url().then(urlStr => {
        if (urlStr.includes('dashboard')) {
            cy.visitWithChecks('/lpa/type');
        }
    });
})

Then(`I visit the admin sign-in page`, () => {
    cy.visitWithChecks(Cypress.env('adminUrl') + '/sign-in');
})

Then(`I visit the donor page for the test fixture lpa`, () => {
        cy.get('@lpaId').then((lpaId) => {
            cy.visitWithChecks('/lpa/' + lpaId + '/donor');
        });
})

Then(`I visit the primary attorney page for the test fixture lpa`, () => {
        cy.get('@lpaId').then((lpaId) => {
            cy.visitWithChecks('/lpa/' + lpaId + '/primary-attorney');
        });
})
