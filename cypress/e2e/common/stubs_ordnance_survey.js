/*
 * Response stubs for Ordnance Survey
 */

const { Then } = require('@badeball/cypress-cucumber-preprocessor');

// Ordance Survey lookup with real postcodes
Then(
  `Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF`,
  () => {
    cy.intercept('GET', /\/address-lookup\?postcode=B1(%20|\+)1TF/, {
      statusCode: 200,
      body: {
        isPostcodeValid: true,
        success: true,
        addresses: [
          {
            line1: 'THE OFFICE OF THE PUBLIC GUARDIAN',
            line2: 'THE AXIS',
            line3: '10 HOLLIDAY STREET, BIRMINGHAM',
            postcode: 'B1 1TF',
            description:
              'THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM',
          },
          {
            line1: 'OPG2',
            line2: 'THE AXIS',
            line3: '10 HOLLIDAY STREET, BIRMINGHAM',
            postcode: 'B1 1TF',
            description: 'OPG2, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM',
          },
          {
            line1: 'OPG3',
            line2: 'THE AXIS',
            line3: '10 HOLLIDAY STREET, BIRMINGHAM',
            postcode: 'B1 1TF',
            description: 'OPG3, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM',
          },
          {
            line1: 'OPG4',
            line2: 'THE AXIS',
            line3: '10 HOLLIDAY STREET, BIRMINGHAM',
            postcode: 'B1 1TF',
            description: 'OPG4, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM',
          },
          {
            line1: 'OPG5',
            line2: 'THE AXIS',
            line3: '10 HOLLIDAY STREET, BIRMINGHAM',
            postcode: 'B1 1TF',
            description: 'OPG5, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM',
          },
        ],
      },
    });
  },
);

Then(
  `Ordnance Survey postcode lookup responses are stubbed out for good postcode NG2 1AR`,
  () => {
    cy.intercept('GET', /\/address-lookup\?postcode=NG2(%20|\+)1AR/, {
      statusCode: 200,
      body: {
        isPostcodeValid: true,
        success: true,
        addresses: [
          {
            line1: 'THE PUBLIC GUARDIAN',
            line2: 'EMBANKMENT HOUSE',
            line3: 'ELECTRIC AVENUE, NOTTINGHAM',
            postcode: 'NG2 1AR',
            description:
              'THE PUBLIC GUARDIAN, EMBANKMENT HOUSE, ELECTRIC AVENUE, NOTTINGHAM',
          },
        ],
      },
    });
  },
);

// Ordnance Survey lookup with bad postcode
Then(
  `Ordnance Survey postcode lookup responses are stubbed out for bad postcode blah`,
  () => {
    cy.intercept('GET', '/address-lookup?postcode=blah', {
      statusCode: 200,
      body: {
        isPostcodeValid: true,
        success: false,
        addresses: [],
      },
    });
  },
);
