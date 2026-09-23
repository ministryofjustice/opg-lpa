const { Then } = require('@badeball/cypress-cucumber-preprocessor');

// Check links on the page which will open a new tab.
// On pages where we have many links which open in new tabs, and where we
// provide a visually-hidden notice which explains this, we use a hidden span
// in the link text instead of always showing the "opens in new tab" text.
// This test therefore checks for both a "bare" link containing the expected
// text, or a link whose text contains a <span> with the expected text.
Then(
  'I should not find links in the page which open in new tabs without notifying me',
  () => {
    cy.document().then((doc) => {
      doc.querySelectorAll('a[target="_blank"]').forEach((el) => {
        let visuallyHiddenSpan = el.querySelector(
          'span[class="govuk-visually-hidden"]',
        );
        if (visuallyHiddenSpan !== null) {
          el = visuallyHiddenSpan;
        }
        expect(el.innerText).to.contain('opens in new tab');
      });
    });
  },
);

// A data-role="link-accessibility-statement" element is put onto pages where
// there are many links which open in new tabs, where the text "opens in new tab"
// becomes repetitive and obtrusive; this mostly includes the /terms and
// /privacy-notice pages. On these pages, we hide the "opens in new tab" text
// and provide a blanket notice at the top of the page.
// See guidance at https://design-system.service.gov.uk/styles/typography/#links
// under "If you’re displaying lots of links together".
Then(
  'I should encounter a visually-hidden statement about links on the page opening in new tabs',
  () => {
    cy.get("*[data-role='link-accessibility-statement']").each(
      (accessibilityStatement) => {
        expect(accessibilityStatement).to.have.class('govuk-visually-hidden');
        expect(accessibilityStatement.text()).to.contain('open in new tabs');
      },
    );
  },
);

/**
 * The configuration shown runs only axe rules tagged with "cat.color",
 * which includes the contrast checks.
 *
 * Typically you would visit a page, focus on an element which is visually
 * highlighted (e.g. button, link), then check contrast across the whole page.
 *
 * This will fail the test if the contrast is insufficient, possibly not
 * just on the highlighted element but on any element which requires contrast
 * (e.g. text with a background colour).
 */
Then('elements on the page should have sufficient contrast', () => {
  const axeOptions = {
    runOnly: {
      type: 'tag',
      values: ['cat.color'],
    },
  };

  const stopOnError = true;

  cy.OPGCheckA11y(axeOptions, stopOnError);
});

/**
 * pageState is an identifier tacked onto the end of the URL to identify this
 * call to OPGCheckA11y(); this is to allow us to run this command on the same
 * page in different UI states, e.g. with/without a popup open
 */
Then('accessibility checks should pass for {string}', (pageState) => {
  cy.OPGCheckA11y({}, false, pageState);
});
