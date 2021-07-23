import { Then } from "cypress-cucumber-preprocessor/steps";

// should('not.be.disabled') is here because it looks like cypress may choke trying to click a button that has 
// been temporarily disabled while the page is loading. This may need ultimately to be done for more or even all steps here
 
Then(`I click {string}`, (clickable) => {
    // If this results in an Oops, then retry the click.
    // We currently believe this to be caused by the CSRF problem, which we intend to fix fully in future
    // and this retry step will be able to be removed from the tests.
    // Also note that CSRF errors can also occur without "Oops" showing on the page. Examples
    // are when the CSRF token is incorrect when clicking "Save and continue" on the replacement
    // attorneys page - the title says "Error" but there is no indication of what the error is
    // in the page itself.
    cy.get("[data-cy=" + clickable + "]").should('not.be.disabled').click();

    /*.document().then(doc => {
        if (doc.documentElement.innerHTML.includes('Oops! Something went wrong with the information you entered.')) {
            cy.log("Received the Oops! Something went wrong with the information you entered message, so retrying the click");
            cy.get("[data-cy=" + clickable + "]").click();
        }
        else if (doc.title.startsWith('Error')) {
            cy.log("Saw 'Error' in page title, so retrying the click");
            cy.get("[data-cy=" + clickable + "]").click();
        }
    });*/

    cy.OPGCheckA11y();
})

Then(`I click occurrence {int} of {string}`, (number, clickable) => {
    cy.get("[data-cy=" + clickable + "]").eq(number).click();
    cy.OPGCheckA11y();
})

Then(`I click the last occurrence of {string}`, (clickable) => {
    cy.get("[data-cy=" + clickable + "]").eq(-1).click();
    cy.OPGCheckA11y();
})

Then(`I force click {string}`, (clickable) => {
    cy.get("[data-cy=" + clickable + "]").click({ force: true });
    cy.OPGCheckA11y();
})

Then(`I click element marked {string}`, (text) => {
    cy.contains(text).click();
    cy.OPGCheckA11y();
})

// this step exists because newly signed-up user goes straight to type page whereas existing user may get taken to dashboard
Then(`If I am on dashboard I click to create lpa`, () => {
    cy.url().then(urlStr => {
        if (urlStr.includes('dashboard')) {
            cy.get("[data-cy=createnewlpa]").click();
            cy.OPGCheckA11y();
        }
    });
})