import { Then } from "cypress-cucumber-preprocessor/steps";

/* Element testing and handling */

// map from natural language text to number
const MapNumberTextToNumber = {
    'zero': 0,
    'a single': 1,
    'one': 1,
    'two': 2,
		'four': 4,
    'ten': 10
};

// map from natural language text to HTML tag name
const MapElementSpecifierToTag = {
    'level 1 heading': 'H1',
    'level 2 heading': 'H2',
    'list item': 'LI',

    // LPA list item selector for dashboard
    'LPA': 'ul[data-cy=lpa-list] > li.list-item',
};

/**
 * Count the number of elements with a specific tag.
 *
 * numberText: e.g. "one", "two"; alternatively, a number like "10"
 * elementSpecifier: specifier for the element to match; can either be
 *     a key in MapElementSpecifierToTag or a selector
 * contextSelector: selector for the element within which to search for the
 *     expected elements; 'document' makes the document the context
 */
const checkNumberOfElements = (numberText, elementSpecifier, contextSelector) => {
    let tag = MapElementSpecifierToTag[elementSpecifier];
    if (tag === undefined) {
        tag = elementSpecifier;
    }

    let numberExpected = MapNumberTextToNumber[numberText];
    if (numberExpected === undefined) {
        numberExpected = parseInt(numberExpected);
    }

    // select elements matching the specifier and count them
    if (contextSelector === 'document') {
        cy.document().then((doc) => {
            expect(doc.querySelectorAll(tag).length).to.equal(numberExpected);
        });
    }
    else {
        let ctx = Cypress.$(contextSelector);
        expect(ctx[0].querySelectorAll(tag).length).to.equal(numberExpected);
    }
};

/**
 * Check that a specified element has a particular tag
 *
 * dataCyReference: reference to the data-cy attribute value on the element
 * elementSpecifier: type of element dataCyReference is expected to be,
 *     expressed as a key from the MapElementSpecifierToTag array
 */
Then('{string} is a {string} element', (dataCyReference, elementSpecifier) => {
    cy.get("[data-cy=" + dataCyReference + "]").then((els) => {
        expect(els.length).to.equal(1);
        expect(els[0].tagName).to.equal(MapElementSpecifierToTag[elementSpecifier]);
    });
});

/**
 * Check that there is/are a specific number of elements of a particular
 * tag in the document
 *
 * numberText: key from MapNumberTextToNumber, e.g. "a single", "two"
 * elementSpecifier: type of element dataCyReference is expected to be,
 *     expressed as a key from the MapElementSpecifierToTag array; if this
 *     is not a key in MapElementSpecifierToTag, it is used as-is
 */
Then('there is {string} {string} element on the page', (numberText, elementSpecifier) => {
    checkNumberOfElements(numberText, elementSpecifier, 'document');
});

Then('there are {string} {string} elements on the page', (numberText, elementSpecifier) => {
    checkNumberOfElements(numberText, elementSpecifier, 'document');
});

// dataCyReference specifies a data-cy value for the context within which
// the elements should occur
Then('there is {string} {string} element inside {string}', (numberText, elementSpecifier, dataCyReference) => {
    cy.get("[data-cy=" + dataCyReference + "]").then((els) => {
        checkNumberOfElements(numberText, elementSpecifier, els[0]);
    });
});

Then('there are {string} {string} elements inside {string}', (numberText, elementSpecifier, dataCyReference) => {
    cy.get("[data-cy=" + dataCyReference + "]").then((els) => {
        checkNumberOfElements(numberText, elementSpecifier, els[0]);
    });
});

/**
 * Check whether text in an element is overflowing. There is an overflow
 * when the scrollWidth of an element is greater than its clientWidth.
 */
Then('the text in {string} does not overflow', (dataCyReference) => {
    cy.get("[data-cy=" + dataCyReference + "]").then((els) => {
        let el = els[0];
        let clientWidth = el.clientWidth;
        let scrollWidth = el.scrollWidth;
        let overflowHidden = (Cypress.$(el).css('overflow') === 'hidden');

        let msg = "element [data-cy=\"" + dataCyReference +
            "\"]'s clientWidth [" + clientWidth + "] is less than " +
            "its scrollWidth [" + scrollWidth + "], and overflow:hidden is " +
            "not set on it - text may overflow";

        if (scrollWidth > clientWidth) {
            expect(overflowHidden).to.eql(true, msg);
        }
        else {
            expect(clientWidth).to.be.at.least(scrollWidth, msg);
        }
    });
});

/**
 * Specific to pages in the LPA workflow: checks for whether the <details>
 * element containing the LPA service use instructions is present or not
 * and open or not
 */
Then('the instructions expandable element should not be present', () => {
    cy.get("[data-cy=details-instructions]").should('not.exist');
});

Then('the instructions expandable element should be present and closed', () => {
    cy.get("[data-cy=details-instructions]").then((els) => {
        const $els = Cypress.$(els);
        expect($els.attr('open')).to.not.exist;
    });
});
