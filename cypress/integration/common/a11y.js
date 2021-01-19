const {
  Then, After,
} = require("cypress-cucumber-preprocessor/steps");

// this will get called after each scenario if flag is set
After(() => {
    if (Cypress.env('RUN_A11Y_TESTS'))
    {
        cy.injectAxe();
        cy.checkA11y();
    }
});

Then('I should not encounter links which can open in new tabs without notifying me', () => {
    cy.get('a[target="_blank"]').each(($el, index, $list) => {
        expect($el.text()).to.contain("opens in new tab");
    });
});

Then('I should encounter a visually-hidden statement about links on the page opening in new tabs', () => {
    // if there is a link-accessibility-statement element on the page,
    // we assume that any target="_blank" links on the page are covered by
    // it and don't check them individually
    let $accessibilityStatement =
        cy.get("*[data-role='link-accessibility-statement']").first();

    $accessibilityStatement.each(($el, index, $list) => {
        expect($el).to.have.class("visually-hidden");
        expect($el.text()).to.contain("open in new tabs");
    });

});
