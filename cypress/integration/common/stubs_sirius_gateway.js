/*
 * Response stubs for Sirius Gateway
 */

const {
  Then,
} = require("cypress-cucumber-preprocessor/steps");

// Sirius Gateway responses for seeded LPA applications
Then(`Sirius Gateway status responses are stubbed out`, () => {
    cy.intercept('GET', '/user/dashboard/statuses/**', {
        statusCode: 200,
        body: {
            "91155453023": {
                "found": true,
                "status": "Received",
                "returnUnpaid": null
            },
            "47629358836": {
                "found": false
            },
            "88668805824": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": null
            },
            "68582508781": {
                "found": true,
                "status": "Checking",
                "returnUnpaid": null
            },
            "93348314693": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": null
            },
            "43476377885": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": null
            },
            "54171193342": {
                "found": true,
                "status": "Checking",
                "returnUnpaid": null
            },
            "32004638272": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": null
            },
            "48218451245": {
                "found": true,
                "status": "Received",
                "returnUnpaid": null
            },
            "97998888883": {
                "found": true,
                "status": "Waiting",
                "returnUnpaid": null
            },
            "15527329531": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": true
            },
            "13316443118": {
                "found": true,
                "status": "Processed",
                "returnUnpaid": true
            },
            "26997335988": {
                "found": false
            },
            "33718377316": {
                "found": false
            }
        }
    });
});
