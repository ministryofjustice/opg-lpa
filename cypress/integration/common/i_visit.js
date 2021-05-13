import { Given, Then } from "cypress-cucumber-preprocessor/steps";

Given(`I visit {string}`, (url) => {
    // note that cy.visitWithChecks will follow redirects, and require the status code to be 2xx after that
    cy.visitWithChecks(url);
})

Then(`I visit the login page`, () => {
    // we do not require extra checks on login page
    cy.visit('/login');
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

Then(`I visit the donor page for the test fixture lpa`, () => {
    visitPageForTestFixture('donor');
})

Then(`I visit the primary attorney page for the test fixture lpa`, () => {
    visitPageForTestFixture('primary-attorney');
})

Then(`I visit the replacement attorney page for the test fixture lpa`, () => {
    visitPageForTestFixture('replacement-attorney');
})

Then(`I visit the certificate provider page for the test fixture lpa`, () => {
    visitPageForTestFixture('certificate-provider');
})

Then(`I visit the people to notify page for the test fixture lpa`, () => {
    visitPageForTestFixture('people-to-notify');
})

Then(`I visit the instructions page for the test fixture lpa`, () => {
    visitPageForTestFixture('instructions');
});

Then(`I visit the applicant page for the test fixture lpa`, () => {
    visitPageForTestFixture('applicant');
});

Then(`I visit the correspondent page for the test fixture lpa`, () => {
    visitPageForTestFixture('correspondent');
});

Then(`I visit the summary page for the test fixture lpa`, () => {
    visitPageForTestFixture('summary');
});

Then(`I visit the who are you page for the test fixture lpa`, () => {
    visitPageForTestFixture('who-are-you');
});

Then(`I visit the repeat application page for the test fixture lpa`, () => {
    visitPageForTestFixture('repeat-application');
});

function visitPageForTestFixture(urlPart) {
    cy.get('@lpaId').then((lpaId) => {
        cy.visitWithChecks('/lpa/' + lpaId + '/' + urlPart);
    });
}
