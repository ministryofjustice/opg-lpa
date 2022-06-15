import { Then } from "cypress-cucumber-preprocessor/steps";

// For all script tags that have a src attribute, and all link tags that have a href,
// I can request those assets without error
// By testing this we avoid publishing page with broken/missing js or css files
Then('I see that included assets such as js and css are ok', () => {
    cy.document().then((doc) => {
        doc.querySelectorAll('script[src]').forEach((el) => {
            cy.request(el.src).then((resp) => {
                expect(resp.headers['content-type']).to.eq('application/javascript')
            })
        });
        doc.querySelectorAll('link[href]').forEach((el) => {
            cy.request(el.href);
        });
    });
});
