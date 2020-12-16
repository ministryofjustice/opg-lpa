import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I submit About Me details`, () => {
    cy.visit({
        //url: cy.url(),
        url: "/user/about-you/new",
        method: "POST",
        body: {
            "name-title": "Mr",
            "name-first": "Chris",
            "name-last": "Smith",
            "dob-date[day]": "1",
            "dob-date[month]": "2",
            "dob-date[year]": "5500",
            "address-address1": "12 Highway Close",
            "address-postcode": "PL45 9JA",
        }
    });
})
