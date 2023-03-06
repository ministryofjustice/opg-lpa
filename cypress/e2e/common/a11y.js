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
          'span[class="visually-hidden"]',
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
        expect(accessibilityStatement).to.have.class('visually-hidden');
        expect(accessibilityStatement.text()).to.contain('open in new tabs');
      },
    );
  },
);

// Check that focusing on and opening a <details> element on a page
// makes the interior content of the element accessible via tab key navigation;
// note that "tag" is a parameter to enable us to check elements which have
// been polyfilled to emulate a details element; see Polyfills.feature
Then('I can navigate through {string} elements using the tab key', (tag) => {
  cy.get(tag).each((details) => {
    // simulate pressing return on the <summary> element; note that,
    // because the browser prevents synthesis of key presses on elements
    // which aren't inputs, we perform a focus() (to prove the summary
    // can be focused) followed by a click() to open the <details>
    // element instead
    cy.wrap(details)
      .find('summary')
      .click()
      .then(() => {
        // get child focusable elements of the detail element
        let focusableEls = details.find('a,:input').toArray();
        let numFocusableEls = focusableEls.length;

        // press tab once for each focusable element and check that focus
        // touches each element; NB we don't attempt to figure out the tab
        // order, we just want to ensure that each element reached by
        // tabbing is one of the elements inside the <details> and is
        // reached once
        for (let i = 0; i < numFocusableEls; i++) {
          cy.tab()
            .focused()
            .then((els) => {
              // check that the focused element is in the list of
              // focusable elements; if not, we are outside the <details>
              // element
              expect(els[0]).to.be.oneOf(focusableEls);

              // remove the element; this is to ensure that we
              // only visit each focusable once
              focusableEls.splice(focusableEls.indexOf(els[0]), 1);
            });
        }
      });
  });
});

// replace <details> elements on the page with <polyfilleddetails> elements,
// and polyfill them; the purpose of this is to enable testing the polyfill
// on a browser which *does* support the <details> element
Then("my browser doesn't support details elements", () => {
  cy.window().then((window) => {
    cy.get('details').each((details) => {
      // replace with a new <polyfilleddetails> element with the same
      // internal content
      let newElement = Cypress.$(
        '<polyfilleddetails>' + details[0].innerHTML + '</polyfilleddetails>',
      );

      // wrap the new element manually
      window.moj.Modules.DetailsPolyfill.wrap(newElement);

      details.replaceWith(newElement);
    });
  });
});

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
