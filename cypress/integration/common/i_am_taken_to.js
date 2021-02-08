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
 
Then(`I am taken to the lpa type page`, () => {
    cy.url().should('eq',lpaType);
    checkAccordionHeaderContains("What type of LPA do you want to make?");
})

Then(`I am taken to the when lpa starts page`, () => {
    checkOnPageWithPath('/lpa/\\d+/when-lpa-starts');
    checkAccordionHeaderContains("When can the LPA be used?") 
});

Then(`I am taken to the replacement attorney page`, () => {
    checkOnPageWithPath('/lpa/\\d+/replacement-attorney');
    checkAccordionHeaderContains("Does the donor want any replacement attorneys?");
});

Then(`I am taken to the primary attorney page`, () => {
    checkOnPageWithPath('/lpa/\\d+/primary-attorney');
    checkAccordionHeaderContains("Who are the attorneys?") 
});
 
Then(`I am taken to the primary attorney decisions page`, () => {
    checkOnPageWithPath('/lpa/\\d+/how-primary-attorneys-make-decision');
    checkAccordionHeaderContains("How should the attorneys make decisions?") 
});
 
Then(`I am taken to the certificate provider page`, () => {
    checkOnPageWithPath('/lpa/\\d+/certificate-provider');
    checkAccordionHeaderContains("Who is the certificate provider?") 
});

Then(`I am taken to the when replacement attorneys step in page`, () => {
    checkOnPageWithPath('/lpa/\\d+/when-replacement-attorney-step-in');
    checkAccordionHeaderContains("How should the replacement attorneys step in?") 
});
 
Then(`I am taken to the life sustaining page`, () => {
    checkOnPageWithPath('/lpa/\\d+/life-sustaining');
    checkAccordionHeaderContains('Who does the donor want to make decisions about life-sustaining treatment?');
});
 
Then(`I am taken to the donor page`, () => {
    cy.url().should('contain','donor').as('donorPageUrl');
    checkAccordionHeaderContains('Who is the donor for this LPA?');
})

Then(`I am taken to the post logout url`, () => {
    cy.log('I should be on ' + Cypress.config().postLogoutUrl );
    cy.url().should('eq',Cypress.config().postLogoutUrl );
})

function checkOnPageWithPath(pathRegex) {
    // get the current lpaId, put this in the path regex, make sure that's the url we're now on
    cy.getLpaId().then((lpaId) => { 
        var pathWithLpaId = pathRegex.replace('\\d+', lpaId);
        cy.url().should('eq',Cypress.config().baseUrl + pathWithLpaId);
    });
}

function checkAccordionHeaderContains(text) {
    cy.get("[data-cy=section-current]").should('contain', text);
}
