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

Then('I can see fields for the PF donor, certificate provider, attorney, applicant', () => {
		const expectedNames = ["Mr Dead Pool (the donor)", "Mr Cee Pee (certificate provider)",
													 "Mr A Att (attorney)", "Mr Dead Pool (applicant)"]
		cy.get("[data-cy=person-name]").then(($name) => {
				const names = $name.map(function() {return this.innerText}).toArray();
				expect(names).to.deep.eq(expectedNames);
		})
})

Then('I can see fields for the HW donor, certificate provider, attorney, applicant', () => {
		const expectedNames = ["Miss Rapunzel Tower (the donor)", "Mr Cee Pee (certificate provider)",
													 "Mr A Att (attorney)", "Miss Rapunzel Tower (applicant)"]
		cy.get("[data-cy=person-name]").then(($name) => {
				const names = $name.map(function() {return this.innerText}).toArray();
				expect(names).to.deep.eq(expectedNames);
		})
})

Then('I can see a reminder to sign continuation sheet 4', () => {
    const text = "They must have signed continuation sheet 4 after the ‘certificate provider’ has signed section 10 of the LPA form."
    cy.get("[data-cy=continuation-sheet-info]").should("have.text", text);
})
