import { Then } from "@badeball/cypress-cucumber-preprocessor";

Then('I hack the session to have {int} seconds remaining', (seconds) => {
    let requestOptions = {
        url: '/session-set-expiry',
        method: 'POST',
        body: { 'expireInSeconds': seconds },
        headers: { 'Content-Type': 'application/json' }
    };

    cy.request(requestOptions).then((response) => {
        expect(response.body.remainingSeconds).to.be.at.most(seconds);
    });
});

Then('I verify that the session has at most {int} seconds remaining', (seconds) => {
    cy.request('/session-state').then((response) => {
        expect(response.body.remainingSeconds).to.be.at.most(seconds);
    });
});
