const {
  After,
} = require("cypress-cucumber-preprocessor/steps");

// after loading a page, check that any links with target="_blank" also
// have rel="noreferrer noopener"; this is to prevent reverse tabnapping and
// is recommended by the GOV.UK design system guidelines
// https://design-system.service.gov.uk/styles/typography/#links
After(() => {
    cy.get('a[target="_blank"]').each(($el, index, $list) => {
        let rel = $el.attr("rel");
        expect(rel).not.to.be.undefined;
        expect(rel).to.contain("noreferrer");
        expect(rel).to.contain("noopener");
    });
});
