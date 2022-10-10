import { Then } from "@badeball/cypress-cucumber-preprocessor";

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
});

Then('I do not have {string} in the viewport', (dataCyReference) => {
    cy.window().then((window) => {
        cy.get('[data-cy="' + dataCyReference + '"]').then((el) => {
            expect(isInViewport(window, el)).not.to.be.true;
        });
    });
});

Then('I am using a viewport greater than {int} pixels wide', (viewportWidth) => {
    cy.viewport(viewportWidth + 1, Cypress.config('viewportHeight'));
});

Then('I am using a viewport which is {int} pixels wide', (viewportWidth) => {
    cy.viewport(viewportWidth, Cypress.config('viewportHeight'));
});

/**
 * Scroll a selected data-cy element into view.
 */
Then('I scroll to {string}', (dataCyReference) => {
    cy.get('[data-cy="' + dataCyReference + '"]').scrollIntoView();
});
