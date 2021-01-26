const {
  Then, After,
} = require("cypress-cucumber-preprocessor/steps");

// Check links on the page which will open a new tab.
// On pages where we have many links which open in new tabs, and where we
// provide a visually-hidden notice which explains this, we use a hidden span
// in the link text instead of always showing the "opens in new tab" text.
// This test therefore checks for both a "bare" link containing the expected
// text, or a link whose text contains a <span> with the expected text.
Then('I should not find links in the page which open in new tabs without notifying me', () => {
    cy.document().then((doc) => {
        doc.querySelectorAll('a[target="_blank"]').forEach((el) => {
            let visuallyHiddenSpan = el.querySelector('span[class="visually-hidden"]');
            if (visuallyHiddenSpan !== null) {
                el = visuallyHiddenSpan;
            }
            expect(el.innerText).to.contain("opens in new tab");
        });
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
    let $accessibilityStatement =
        cy.get("*[data-role='link-accessibility-statement']").first();

    $accessibilityStatement.each(($el, index, $list) => {
        expect($el).to.have.class("visually-hidden");
        expect($el.text()).to.contain("open in new tabs");
    });

});
