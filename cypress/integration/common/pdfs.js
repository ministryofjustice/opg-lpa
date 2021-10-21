import { Then } from "cypress-cucumber-preprocessor/steps";

const MAX_TRIES = 200;

const requestUntilRefreshUrl = (href, tries) => {
    // keep requesting until the response contains url in meta http-equiv
    tries = tries || 0;

    return cy.request(href).then((response) => {
        tries += 1;

        console.log(response.body);

        const content = /meta http-equiv="refresh" content="([^"]+)"/.exec(response.body)[1];

        if (content.includes('url')) {
            const refreshUrl = content.substring(7);

            return new Cypress.Promise((resolve, reject) => {
                resolve(refreshUrl);
            });
        }
        else if (tries > MAX_TRIES) {
            return new Cypress.Promise((resolve, reject) => {
                reject('made over ' + MAX_TRIES + ' requests without success');
            });
        }

        return cy.wait(parseInt(content) * 1000).then(() => {
            return requestUntilRefreshUrl(href, tries)
        });
    });
};

Then(`I can get pdf from link containing {string}`, (linkText) => {
    // keep trying refresh url until it contains a link to a pdf, then request that
    cy.contains(linkText).should('have.attr', 'href').then((href) => {
        requestUntilRefreshUrl(href).then((refreshUrl) => {
            cy.request(refreshUrl).then((response) => {
                expect(response.headers['content-type']).to.contain('application/pdf');
                expect(response.body).to.have.length.gt(500);
            });
        });
    });
});
