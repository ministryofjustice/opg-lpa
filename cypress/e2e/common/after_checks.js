const { After } = require('@badeball/cypress-cucumber-preprocessor');

// NOTE: fixtures.js contains code for creating and cleaning fixtures,
// which uses Before()/After() steps

// After loading a page, check that any links with target="_blank" also
// have rel="noreferrer noopener"; this is to prevent reverse tabnapping and
// is recommended by the GOV.UK design system guidelines
// https://design-system.service.gov.uk/styles/typography/#links
After({ tags: '@LinkCheckAfter' }, () => {
  cy.log('Checking link targets, noreferrer and noopener');

  cy.document().then((doc) => {
    doc.querySelectorAll('a[target="_blank"]').forEach((el) => {
      let rel = el.getAttribute('rel');
      expect(rel).not.to.be.undefined;
      expect(rel).to.contain('noreferrer');
      expect(rel).to.contain('noopener');
    });
  });
});

// Find all elements which are error summary headings.
// For each, ensure that it is an h2.
After({ tags: '@ErrorSummaryCheckAfter' }, () => {
  cy.log('Checking error summary headings are at the correct level');

  cy.document().then((doc) => {
    doc.querySelectorAll('.error-summary-heading').forEach((node) => {
      assert(node.tagName == 'H2', 'error summary headings should be <h2>');
    });
  });
});

// Test encoding of page after loading it
After({ tags: '@EncodingCheckAfter' }, () => {
  cy.log('Checking encoding of page');

  cy.document().then((doc) => {
    expect(doc.characterSet).to.eql('UTF-8');
    expect(doc.contentType).to.eql('text/html');

    // nginx 403 error page is not valid HTML5
    if (doc.doctype !== null) {
      expect(doc.doctype.name).to.eql('html');
    }
  });
});
