const { After } = require('@badeball/cypress-cucumber-preprocessor');

// after loading a page, check that any links with target="_blank" also
// have rel="noreferrer noopener"; this is to prevent reverse tabnapping and
// is recommended by the GOV.UK design system guidelines
// https://design-system.service.gov.uk/styles/typography/#links
After(() => {
  cy.document().then((doc) => {
    doc.querySelectorAll('a[target="_blank"]').forEach((el) => {
      let rel = el.getAttribute('rel');
      expect(rel).not.to.be.undefined;
      expect(rel).to.contain('noreferrer');
      expect(rel).to.contain('noopener');
    });
  });
});
