import { Then } from "cypress-cucumber-preprocessor/steps";

var dashboard = Cypress.config().baseUrl + '/user/dashboard';
var lpaType = Cypress.config().baseUrl + '/lpa/type';
var lpaid;

Then(`I am taken to {string}`, (url) => {
    cy.url().should('eq',Cypress.config().baseUrl + url);
})

Then(`I am taken to the login page`, () => {
    cy.url().should('eq',Cypress.config().baseUrl + '/login');
})

Then(`I am taken to the dashboard page`, () => {
    cy.url().should('eq',dashboard);
})

Then(`I am taken to the your details page for a new user`, () => {
    cy.url().should('eq',Cypress.config().baseUrl + '/user/about-you/new');
})

Then(`I am taken to the lpa type page`, () => {
    cy.url().should('eq',lpaType);
    checkAccordionHeaderContains("What type of LPA do you want to make?");
})

Then(`I am taken to the type page for cloned lpa`, () => {
    cy.url().should('contain','type');
    checkAccordionHeaderContains("What type of LPA do you want to make?");
})

Then(`I am taken to the when lpa starts page`, () => {
    checkOnPageWithPath('when-lpa-starts');
    checkAccordionHeaderContains("When can the LPA be used?")
});

Then(`I am taken to the replacement attorney page`, () => {
    checkOnPageWithPath('replacement-attorney');
    checkAccordionHeaderContains("Does the donor want any replacement attorneys?");
});

Then(`I am taken to the primary attorney page`, () => {
    checkOnPageWithPath('primary-attorney');
    checkAccordionHeaderContains("Who are the attorneys?")
});

Then(`I am taken to the primary attorney decisions page`, () => {
    checkOnPageWithPath('how-primary-attorneys-make-decision');
    checkAccordionHeaderContains("How should the attorneys make decisions?")
});

Then(`I am taken to the certificate provider page`, () => {
    checkOnPageWithPath('certificate-provider');
    checkAccordionHeaderContains("Who is the certificate provider?")
});

Then(`I am taken to the people to notify page`, () => {
    checkOnPageWithPath('people-to-notify');
    checkAccordionHeaderContains("Who should be notified about the LPA?")
});

Then(`I am taken to the when replacement attorneys step in page`, () => {
    checkOnPageWithPath('when-replacement-attorney-step-in');
    checkAccordionHeaderContains("How should the replacement attorneys step in?")
});

Then(`I am taken to the how replacement attorneys make decision page`, () => {
    checkOnPageWithPath('how-replacement-attorneys-make-decision');
    checkAccordionHeaderContains("How should the replacement attorneys make decisions?")
});

Then(`I am taken to the instructions page`, () => {
    checkOnPageWithPath('instructions');
    checkAccordionHeaderContains("Preferences and instructions")
});

Then(`I am taken to the applicant page`, () => {
    checkOnPageWithPath('applicant');
    checkAccordionHeaderContains("Who’s applying to register the LPA?")
});

Then(`I am taken to the correspondent page`, () => {
    checkOnPageWithPath('correspondent');
    checkAccordionHeaderContains("Where should we send the registered LPA and any correspondence?")
});

Then(`I am taken to the summary page`, () => {
    checkOnPageContainingPath('summary');
    cy.contains("Review your details");
});

Then(`I am taken to the life sustaining page`, () => {
    checkOnPageWithPath('life-sustaining');
    checkAccordionHeaderContains('Who does the donor want to make decisions about life-sustaining treatment?');
});

Then(`I am taken to the who are you page`, () => {
    checkOnPageWithPath('who-are-you');
    checkAccordionHeaderContains('Who was using the LPA service?');
});

Then(`I am taken to the repeat application page`, () => {
    checkOnPageWithPath('repeat-application');
    checkAccordionHeaderContains('Is the donor making a repeat application to register their LPA?');
});

Then(`I am taken to the fee reduction page`, () => {
    checkOnPageWithPath('fee-reduction');
    checkAccordionHeaderContains('Does the donor want to apply to pay a reduced fee?');
});

Then(`I am taken to the checkout page`, () => {
    checkOnPageWithPath('checkout');
    cy.contains('Final check: LPA details');
});

Then(`I am taken to the complete page`, () => {
    checkOnPageWithPath('complete');
    cy.contains('Last steps');
});

Then(`I am taken to the certificate provider page for the test fixture lpa`, () => {
    checkOnPageWithPath('certificate-provider');
})

Then(`I am taken to the donor page`, () => {
    // We arrive at the donor page when we've just created an lpa through the web, so we store the lpaId for future use at this point
    cy.url().should('contain','donor').then((donorPageUrl) => {
        var lpaId = donorPageUrl.match(/\/(\d+)\//)[1];
        cy.wrap(lpaId).as('lpaId');
    });
    checkAccordionHeaderContains('Who is the donor for this LPA?');
})

Then(`I am taken to the post logout url`, () => {
    cy.log('I should be on ' + Cypress.config().postLogoutUrl );
    cy.url().should('eq',Cypress.config().postLogoutUrl );
})

function checkOnPageWithPath(urlPart) {
    comparePageToPath(urlPart,'eq');
}

// this second function is almost the same as checkOnPageWithPath, but handles non-exact matches
// e:g where there's a query string
function checkOnPageContainingPath(urlPart) {
    comparePageToPath(urlPart,'contains');
}

function comparePageToPath(urlPart, comparator) {
    // get the current lpaId, put this in the path regex, make sure that's the url we're now on
    var pathRegex = '/lpa/\\d+/' + urlPart;
    cy.get('@lpaId').then((lpaId) => {
        var pathWithLpaId = pathRegex.replace('\\d+', lpaId);
        cy.url().should(comparator, Cypress.config().baseUrl + pathWithLpaId);
    });
}

function checkAccordionHeaderContains(text) {
    cy.get("[data-cy=section-current]").should('contain', text);
}
