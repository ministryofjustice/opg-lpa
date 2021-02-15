import { Then } from "cypress-cucumber-preprocessor/steps";

// wait for a number of seconds
Then('I wait for {int} seconds', (seconds) => {
    cy.wait(seconds * 1000);
});

// Load the current page, but manually set the seconds remaining in
// the user's session; couple with "I wait for ... seconds", this enables
// testing the session timeout without having to reduce the whole stack timeout.
Then('I manually set the session remaining seconds to {int}', (seconds) => {
    // Set the number of seconds by appending
    // SessionTimeoutDialog.remainingSeconds=<seconds> to the current URL
    // and reloading the page
    let qstring = 'SessionTimeoutDialog.remainingSeconds=' + seconds;

    cy.location().then((loc) => {
        console.log(qstring);

        let newUrl = loc.href;
        if (newUrl.indexOf('?') === -1) {
            newUrl += '?';
        }
        newUrl += qstring;

        cy.window().then((win) => {
            win.location.href = newUrl;
        });
    });
});
