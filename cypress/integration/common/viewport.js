import { Then } from "cypress-cucumber-preprocessor/steps";

const isInViewport = function (window, el) {
    let $el = Cypress.$(el);
    let $window = Cypress.$(window);

    let topOfElement = $el.offset().top;
    let bottomOfElement = topOfElement + $el.outerHeight();
    let topOfWindow = $window.scrollTop();
    let bottomOfWindow = topOfWindow + $window.innerHeight();

    if ((bottomOfWindow > topOfElement) && (topOfWindow < bottomOfElement)){
        return true;
    }
    return false;
};

Then('I have {string} in the viewport', (dataCyReference) => {
    cy.window().then((window) => {
        cy.get('[data-cy="' + dataCyReference + '"]').then((el) => {
            expect(isInViewport(window, el)).to.be.true;
        });
    });
})

Then('I do not have {string} in the viewport', (dataCyReference) => {
    cy.window().then((window) => {
        cy.get('[data-cy="' + dataCyReference + '"]').then((el) => {
            expect(isInViewport(window, el)).not.to.be.true;
        });
    });
})

