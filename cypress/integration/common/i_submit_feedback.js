import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I submit the feedback`, () => {
    cy.get('input[type="submit"][name="send"][class="button"]').click();
})
