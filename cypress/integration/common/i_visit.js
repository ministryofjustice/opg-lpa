import { Given, Then } from "cypress-cucumber-preprocessor/steps";

Given(`I visit {string}`, (url) => {
    // note that cy.visitWithChecks will follow redirects, and require the status code to be 2xx after that
    cy.visitWithChecks(url);
})

Then(`I visit the login page`, () => {
    // we do not require extra checks on login page
    cy.visit('/login');
})

Then(`I visit the dashboard`, () => {
    cy.visit('/user/dashboard');
})

Then(`I visit view docs page for test lpa {string}`, (lpaId) => {
    // visit view docs page for test lpa specifying id
    cy.visit('/lpa/' + lpaId + '/view-docs')
    cy.wrap(lpaId).as('lpaId');
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

Then (`I visit the admin system-message page`, () => {
    cy.visit(Cypress.env('adminUrl') + '/system-message');
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

Then(`I visit the fee reduction page for the test fixture lpa`, () => {
    visitPageForTestFixture('fee-reduction');
});

Then(`I visit the checkout page for the test fixture lpa`, () => {
    visitPageForTestFixture('checkout');
});

Then(`I visit the complete page for the test fixture lpa`, () => {
    visitPageForTestFixture('complete');
});

function visitPageForTestFixture(urlPart) {
    cy.get('@lpaId').then((lpaId) => {
        cy.visitWithChecks('/lpa/' + lpaId + '/' + urlPart);
    });
}
