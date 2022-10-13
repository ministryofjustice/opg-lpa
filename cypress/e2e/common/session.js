import { Then } from '@badeball/cypress-cucumber-preprocessor';

Then('I hack the session to have {int} seconds remaining', (seconds) => {
  seconds = parseInt(seconds);

  let requestOptions = {
    url: '/session-set-expiry',
    method: 'POST',
    body: { expireInSeconds: seconds },
    headers: { 'Content-Type': 'application/json' }
  };

  cy.request(requestOptions).then((response) => {
    expect(response.body.remainingSeconds).to.be.at.least(seconds - 5);
    expect(response.body.remainingSeconds).to.be.at.most(seconds + 5);
  });
});

Then(
  'I verify that the session has at most {int} seconds remaining',
  (seconds) => {
    cy.request('/session-state').then((response) => {
      expect(response.body.remainingSeconds).to.be.at.most(seconds);
    });
  },
);
