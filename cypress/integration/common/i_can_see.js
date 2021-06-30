import { Then } from "cypress-cucumber-preprocessor/steps";

Then('I can see a "Reuse LPA details" link for the test fixture lpa', (linkText) => {
	cy.get('@lpaId').then((lpaId) => { 
        const selector = 'a[href*="/user/dashboard/create/' + lpaId + '"]';
        cy.get(selector);
    });
});