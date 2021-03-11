import { Then } from "cypress-cucumber-preprocessor/steps";

// This is horrible, but due to CSRF bug causing failres we have to do this in some places
Then(`If I see Oops due to Csrf button I hit save again`, () => {
    cy.document().then(docStr => {
        if (docStr.documentElement.innerHTML.includes('Oops')) {
            cy.get("[data-cy=save]").click();
            cy.OPGCheckA11y();
        }
    });
})

