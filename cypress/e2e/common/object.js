import { Then } from '@badeball/cypress-cucumber-preprocessor';

/**
 * Set up expectations that an object stored in the testStore
 * (see cypress/plugins/index.js) has properties with specific values.
 *
 * @param key A string referencing an object which has been previously stored.
 * @param dataTable A dataTable entry which lists |propertyPath|value|
 * expectations; see Ping.feature for an example.
 *
 *   propertyPath: an expression which queries for a nested
 *     property of the object stored at key;
 *     see https://www.chaijs.com/api/bdd/#method_nested
 *
 *   value: the expected value of the property referenced by propertyPath within
 *     the object
 */
Then('the object {string} should have these properties:', (key, dataTable) => {
  // this can only run after setting a key in the testStore
  cy.task('getValue', key).then((obj) => {
    let rows = dataTable.rawTable;

    for (let index in rows) {
      let row = rows[index];
      let name = row[0];
      let value = row[1];

      value = convertIfBool(value);

      let potentialFloat = parseFloat(value);
      if (!isNaN(potentialFloat)) {
        value = potentialFloat;
      }

      expect(obj).to.have.nested.property(name, value);
    }
  });
});

Then('the object {string} should have these values:', (key, dataTable) => {
  // this can only run after setting a key in the testStore
  cy.task('getValue', key).then((obj) => {
    let rows = dataTable.rawTable;

    for (let index in rows) {
      let row = rows[index];
      let name = row[0];
      let value = row[1];

      value = convertIfBool(value);

      expect(obj).to.have.nested.property(name, value);
    }
  });
});

function convertIfBool(value) {
  // we do some basic type conversion here so we can cope with booleans,
  // numbers and strings
  value = value === 'true' ? true : value;
  value = value === 'false' ? false : value;

  return value;
}
