import { Then } from "cypress-cucumber-preprocessor/steps";

Then('I disable stylesheets', () => {
    // remove all stylesheets from a page to enable it to be tested
    // from a screen-reader-like perspective;
    // NB I tried to do this with jQuery but it didn't work, so resorted
    // to raw DOM methods
    cy.document().then((doc) => {
        doc.querySelectorAll('link[rel="stylesheet"]')
           .forEach((node) => { node.parentNode.remove(node) });
    });
})
