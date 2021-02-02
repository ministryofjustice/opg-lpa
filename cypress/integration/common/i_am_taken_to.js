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

Then(`I am taken to the primary attorney page`, () => {
    checkOnPageWithPath('/lpa/\\d+/primary-attorney');
    checkAccordionHeaderContains("Who are the attorneys?") 
});
 
Then(`I am taken to the life sustaining page`, () => {
    checkOnPageWithPath('/lpa/\\d+/life-sustaining');
    checkAccordionHeaderContains('Who does the donor want to make decisions about life-sustaining treatment?');
});
 
Then(`I am taken to the donor page for health and welfare`, () => {
    cy.url().should('contain','donor').as('donorPageUrl');
    checkAccordionHeaderContains('Who is the donor for this LPA?');
})

Then(`I am taken to the donor page for property and finance`, () => {
    cy.url().should('contain','donor').as('donorPageUrl');
    checkAccordionHeaderContains('Who is the donor for this LPA?');
})

Then(`I am taken to the post logout url`, () => {
    cy.log('I should be on ' + Cypress.config().postLogoutUrl );
    cy.url().should('eq',Cypress.config().postLogoutUrl );
})

function checkOnPageWithPath(pathRegex) {
    cy.get('@donorPageUrl').then(($url) => { checkPath(pathRegex, $url); });
}

function checkPath(path, donorPageUrl) {
    // Given a path, insert the lpaId by extracting that from donorPageUrl, then check we're on the resulting url
    var lpaId = donorPageUrl.match(/\/(\d+)\//)[1];
    var pathWithLpaId = path.replace('\\d+', lpaId);
    cy.url().should('eq',Cypress.config().baseUrl + pathWithLpaId);
}
 
function checkAccordionHeaderContains(text) {
    cy.get("[data-cy=section-current]").should('contain', text);
}
