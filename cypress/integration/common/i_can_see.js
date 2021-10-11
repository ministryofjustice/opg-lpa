import { Then } from "cypress-cucumber-preprocessor/steps";

Then('I can see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(true);
});

Then('I cannot see a "Reuse LPA details" link for the test fixture lpa', () => {
    seeReuseDetailsLink(false);
});

const seeReuseDetailsLink = (shouldExist) => {
    cy.get('@lpaId').then((lpaId) => {
        const selector = 'a[href*="/user/dashboard/create/' + lpaId + '"]';
        const condition = (shouldExist ? 'exist' : 'not.exist');
        cy.get(selector).should(condition);
    });
}
