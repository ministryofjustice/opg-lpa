import { Then } from '@badeball/cypress-cucumber-preprocessor';

const TOGGLE = '[data-cy="service-nav-toggle"]';
const LIST = '[data-cy="service-nav-list"]';

// should('not.be.disabled') is here because it looks like cypress may choke trying to click a button that has
// been temporarily disabled while the page is loading. This may need ultimately to be done for more or even all steps here

Then(`I click {string}`, (clickable) => {
  const selector = `[data-cy="${clickable}"]`;
  openServiceNavIfHidden(selector);
  cy.get(selector).should('not.be.disabled').click();
});

Then(`I double click {string}`, (clickable) => {
  cy.get('[data-cy=' + clickable + ']')
    .should('not.be.disabled')
    .dblclick();
});

Then(`I click occurrence {int} of {string}`, (number, clickable) => {
  cy.get('[data-cy=' + clickable + ']')
    .eq(number)
    .click();
});

Then(`I click the last occurrence of {string}`, (clickable) => {
  cy.get('[data-cy=' + clickable + ']')
    .eq(-1)
    .click();
});

Then(`I force click {string}`, (clickable) => {
  cy.get('[data-cy=' + clickable + ']').click({ force: true });
});

Then(`I click element marked {string}`, (text) => {
  cy.contains(text).click();
});

Then(`I click {string} for LPA ID {int}`, (clickable, LpaId) => {
  cy.get('[data-cy=lpa-' + LpaId + ']')
    .find('[data-cy=' + clickable + ']')
    .click();
  cy.OPGCheckA11y();
});

// this step exists because newly signed-up user goes straight to type page whereas existing user may get taken to dashboard
Then(`If I am on dashboard I click to create lpa`, () => {
  cy.url().then((urlStr) => {
    if (urlStr.includes('dashboard')) {
      cy.get('[data-cy=createnewlpa]').click();
      cy.OPGCheckA11y();
    }
  });
});

Then('I click the "Reuse LPA details" link for the test fixture lpa', () => {
  cy.get('@lpaId').then((lpaId) => {
    const selector = 'a[href*="/user/dashboard/create/' + lpaId + '"]';
    cy.get(selector).click();
  });
});

// Simulate a click on a link by performing a background request, and check
// that the response redirects to the expected URL.
// linkIdentifier: the data-cy attribute value of the link to simulate a click on
// redirectUrl: URL we expect to get back as the Location header in the response
Then(
  'a simulated click on the {string} link causes a 302 redirect to {string}',
  (linkIdentifier, redirectUrl) => {
    // Get the href from the link matching the selector
    cy.get('[data-cy=' + linkIdentifier + ']')
      .invoke('attr', 'href')
      .then((href) => {
        // Request the URL
        cy.request(href).then((response) => {
          // Check the response is a 302 to the expected location
          const redirects = response.redirects;
          expect(redirects.length).to.equal(1);
          expect(redirects[0]).to.equal('302: ' + redirectUrl);
        });
      });
  },
);

function openServiceNavIfHidden(targetSelector) {
  cy.get('body').then(($body) => {
    const $target = $body.find(targetSelector);
    if (!$target.length) {
      return;
    }
    const $list = $target.closest(LIST);
    if (
      $list.length &&
      ($list.is(':hidden') || $list.attr(':hidden') !== undefined)
    ) {
      cy.get(TOGGLE).click().should('have.attr', 'aria-expanded', 'true');
      cy.get(LIST).should('exist').and('not.have.attr', 'hidden');
    }
  });
}
