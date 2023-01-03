const { After } = require('@badeball/cypress-cucumber-preprocessor');

// Test encoding of every page after loading it during a test
After(() => {
  cy.document().then((doc) => {
    expect(doc.characterSet).to.eql('UTF-8');
    expect(doc.contentType).to.eql('text/html');

    // nginx 403 error page is not valid HTML5
    if (doc.doctype !== null) {
      expect(doc.doctype.name).to.eql('html');
    }
  });
});
