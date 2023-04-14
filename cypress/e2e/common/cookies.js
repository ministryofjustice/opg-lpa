const { Then } = require('@badeball/cypress-cucumber-preprocessor');

// couldn't get cypress to do what I wanted, so I made my own
// function for checking whether _ga and _gid cookies are present;
// unlike cypress, which isn't able to retry and sometimes can't
// get a cookie because the browser hasn't set it yet, we can
// add a loop to retry until we get what we want, or until we fail
const checkCookies = function (shouldBeSet, tries) {
  if (tries === undefined) {
    tries = 0;
  }

  if (tries > 2) {
    cy.log('TOO MANY TRIES');
    return cy.wrap(false).should('be.true');
  }

  return cy.getCookies().then((cookies) => {
    cy.log(JSON.stringify(cookies));

    const gaCookie = cookies.filter((cookie) => cookie['name'] === '_ga');
    const gaIdCookie = cookies.filter(
      (cookie) => cookie['name'] === '_ga_1DVC295G9L',
    );
    const cookiesSet = gaCookie.length > 0 && gaIdCookie.length > 0;

    cy.log('ARE COOKIES SET? ' + cookiesSet);
    cy.log('SHOULD THEY BE? ' + shouldBeSet);

    if (cookiesSet === shouldBeSet) {
      return cy.wrap(true).should('be.true');
    }

    // try again
    return cy.wait(500).then(() => {
      return checkCookies(shouldBeSet, tries + 1);
    });
  });
};

Then('analytics cookies are set', () => {
  return checkCookies(true);
});

Then('analytics cookies are not set', () => {
  return checkCookies(false);
});

// decision: "rejected" or "accepted"
Then('I see a message that I have {string} analytics cookies', (decision) => {
  cy.get('[data-cy="cookie-preferences-save-confirm"]').contains(decision);
});

Then('I can see a hide button to close the cookies banner', () => {
  cy.get('[data-cy="hide-cookies-banner"]').should('be.visible');
});

Then('the cookie banner is not visible', () => {
  cy.get('[data-cy="cookie-message"]').should('not.be.visible');
});
