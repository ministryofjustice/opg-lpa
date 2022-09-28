import { Then } from "@badeball/cypress-cucumber-preprocessor"

const MS_PER_DAY = 24*60*60*1000

const fillSignatureDateInputs = (elt, signatureDate) => {
    elt.find('[data-cy=date-check-date-day]').attr('value', signatureDate.getDate())
    elt.find('[data-cy=date-check-date-month]').attr('value', signatureDate.getMonth() + 1)
    elt.find('[data-cy=date-check-date-year]').attr('value', signatureDate.getFullYear())
}

Then('I cannot see continuation sheet reminders', () => {
    cy.get('[data-cy=continuation-sheet-info]').should('not.exist');
})

Then(
    'I can see fields for the donor {string}, certificate provider {string}, attorney {string}, and applicant {string}',
    (donorName, certificateProviderName, attorneyName, applicantName) => {
        const expectedNames = [
            donorName + ' (donor)',
            certificateProviderName + ' (certificate provider)',
            attorneyName + ' (attorney)',
            applicantName + ' (applicant)'
        ]

        cy.get('[data-cy=person-name]').then(($name) => {
            const names = $name.map(function() { return this.innerText }).toArray()
            expect(names).to.deep.eq(expectedNames)
        }
)
})

Then('I can see a reminder to sign continuation sheet 1, 2 and 3', () => {
    const text = 'Continuation sheets 1 and 2 must have been signed and dated before or on the same day as they signed continuation sheet 3.'
    cy.get('[data-cy=continuation-sheet-info]').should('contain.text', text)
})

Then('I can see a reminder to sign continuation sheet 4', () => {
    const text = 'They must have signed continuation sheet 4 after the \'certificate provider\' has signed section 10 of the LPA form.'
    cy.get('[data-cy=primary-attorney]').find('[data-cy=continuation-sheet-info]').should('contain.text', text)
})

Then('I can see that the donor {string} cannot sign', (name) => {
    const donorName = 'The person signing on behalf of ' + name + ' (donor)'
    const donorText = 'This person signed continuation sheet 3 on behalf of the donor, followed by two witnesses, on'
    cy.get('[data-cy=date-check-donor]').find('h3').should('contain.text', donorName)
    cy.get('[data-cy=date-check-donor]').find('p').should('contain.text', donorText)

    const applicantName = 'The person signing on behalf of ' + name + ' (applicant)'
    const applicantText = 'This person signed section 15 of the LPA on behalf of the applicant on'
    cy.get('[data-cy=date-check-applicant]').find('h3').should('contain.text', applicantName)
    cy.get('[data-cy=date-check-applicant]').find('p').should('contain.text', applicantText)
})

Then('I can see that the donor {string} can sign', (name) => {
    const donorName = name + ' (donor)'
    const donorText = 'This person signed section 9 of the LPA on'
    cy.get('[data-cy=date-check-donor]').find('h3').should('contain.text', donorName)
    cy.get('[data-cy=date-check-donor]').find('p').should('contain.text', donorText)

    const applicantName = name + ' (applicant)'
    const applicantText = 'This person signed section 15'
    cy.get('[data-cy=date-check-applicant]').find('h3').should('contain.text', applicantName)
    cy.get('[data-cy=date-check-applicant]').find('p').should('contain.text', applicantText)
})

Then('I can see validation errors do not refer to the donor', () => {
    const donorText = 'Enter the signature date of the person signing on behalf of the donor'
    const applicantText = 'Enter the signature date of the person signing on behalf of the applicant'
    cy.get('[data-cy=date-check-donor]').find('.error-message').should('contain.text', donorText)
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', applicantText)
})

Then('I can see validation errors refer to the donor and applicant', () => {
    const donorText = 'Enter the donor\'s signature date'
    cy.get('[data-cy=date-check-donor]').find('.error-message').should('contain.text', donorText)

    const applicantText = 'Enter the applicant\'s signature date'
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', applicantText )
})

Then('I can see that a person is signing on behalf of the applicant {string}', (name) => {
    cy.get('[data-cy=date-check-applicant]')
        .find('[data-cy=person-name]')
        .should('contain.text', 'The person signing on behalf of ' + name + ' (applicant)')
})

Then('I fill in all signature dates on the check dates form', () => {
    // 40 days ago - ensures everything was signed in the past
    let signatureDate = new Date(Date.now() - (MS_PER_DAY * 40))

    // fill in all date fields
    cy.get('[data-cy=date-check-date]').each(elt => {
        fillSignatureDateInputs(elt, signatureDate)

        // move to next day
        signatureDate = new Date(signatureDate.getTime() + MS_PER_DAY)
    })
})

Then('I fill in the applicant signature date as a date in the future', () => {
    const tomorrow = new Date(Date.now() + MS_PER_DAY)
    cy.get('#sign-date-applicant-0').each(elt => {
        fillSignatureDateInputs(elt, tomorrow)
    })
})

Then('I can see applicant validation errors about person signing on behalf of the applicant not signing in the future', () => {
    const errorText = 'Check your dates. The signature date of the person signing on behalf ' +
        'of the applicant cannot be in the future'
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', errorText)
})

Then('I fill in the applicant signature date as a date before the attorney signature dates', () => {
    // find a date in the past
    const yesterday = new Date(Date.now() - MS_PER_DAY)
    const dayBeforeYesterday = new Date(Date.now() - (2 * MS_PER_DAY))

    // set attorney signature dates to it
    cy.get('[data-cy=primary-attorney]').each(elt => {
        fillSignatureDateInputs(elt, yesterday)
    })

    // set applicant signature date to a date one day before the attorney signature date
    cy.get('#sign-date-applicant-0').each(elt => {
        fillSignatureDateInputs(elt, dayBeforeYesterday)
    })
})

Then('I can see applicant validation errors about person signing on behalf of applicant not signing before attorneys', () => {
    const errorText = 'The person signing on behalf of the applicant must sign on the same day or after ' +
        'all section 11s have been signed. You need to print and re-sign section 15'
    cy.get('[data-cy=date-check-applicant]').find('.error-message').should('contain.text', errorText)
})
