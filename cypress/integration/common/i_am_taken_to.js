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
})
 
Then(`I am taken to the donor page`, () => {
  cy.url().should('contain','donor').as('myurl');
})

Then(`I get lpaid`, () => {
    cy.get('@myurl').then(($url) => {cy.log("url is " + $url + " yeah")});
})
 
Then(`I am taken to the post logout url`, () => {
  cy.log("I should be on " + Cypress.config().postLogoutUrl );
  cy.url().should('eq',Cypress.config().postLogoutUrl );
})

Then(`I am taken to the type or dashboard page`, () => {
    // use sparingly as this isn't very precise
    // it specifies the 2 acceptable results of logging in
    // we use this where a user may or may not be newly signed up and 
    // for this particular test we don't mind which
    //
    cy.url().then(urlStr => {expect(urlStr).to.be.oneOf([dashboard, lpaType])});
})
