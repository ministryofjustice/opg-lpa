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

// Check links on the page which are inside the main content area and
// which will open a new tab.
// On pages where we have many links which open in new tabs, and where we
// provide a visually-hidden notice which explains this, we use a hidden span
// in the link text instead of always showing the "opens in new tab" text.
// This test therefore checks for both.
Then('I should not find links in the page which open in new tabs without notifying me', () => {
    cy.get('a[target="_blank"]').each(($el, index, $list) => {
        let $visuallyHiddenSpan = $el.find('span[class="visually-hidden"]');
        if ($visuallyHiddenSpan.length > 0) {
            $el = $visuallyHiddenSpan;
        }
        expect($el.text()).to.contain("opens in new tab");
    });
});

// A data-role="link-accessibility-statement" element is put onto pages where
// there are many links which open in new tabs, where the text "opens in new tab"
// becomes repetitive and obtrusive; this mostly includes the /terms and
// /privacy-notice pages. On these pages, we hide the "opens in new tab" text
// and provide a blanket notice at the top of the page.
// See guidance at https://design-system.service.gov.uk/styles/typography/#links
// under "If you’re displaying lots of links together".
Then('I should encounter a visually-hidden statement about links on the page opening in new tabs', () => {
    // if there is a link-accessibility-statement element on the page,
    // we assume that any target="_blank" links on the page are covered by it
    let $accessibilityStatement =
        cy.get("*[data-role='link-accessibility-statement']").first();

    $accessibilityStatement.each(($el, index, $list) => {
        expect($el).to.have.class("visually-hidden");
        expect($el.text()).to.contain("open in new tabs");
    });

});
