import { Then } from "cypress-cucumber-preprocessor/steps";

const MAX_TRIES = 100;

const requestUntilRefreshUrl = (href, tries) => {
    tries = tries || 0;

    return cy.request(href).then((response) => {
        tries += 1;

        console.log(response.body);

        const content = /meta http-equiv="refresh" content="([^"]+)"/.exec(response.body)[1];

        if (content.includes('url')) {
            // TODO extract the refresh URL
            const refreshUrl = 'found refresh URL for PDF';

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

Then(`I can get {string} from link containing {string}`, (fileName, linkText) => {
    cy.contains(linkText).should('have.attr', 'href').then((href) => {
        requestUntilRefreshUrl(href).then((refreshUrl) => {
            // TODO fetch the refresh URL here with cy.request()
            console.log(refreshUrl);
        });
    });
});
