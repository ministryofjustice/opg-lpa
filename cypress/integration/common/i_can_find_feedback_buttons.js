import { Given } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can find feedback buttons`, () => {
    cy.get('input[type="radio"][name="rating"][value="very-satisfied"]');
    cy.get('input[type="radio"][name="rating"][value="satisfied"]');
    cy.get('input[type="radio"][name="rating"][value="neither-satisfied-or-dissatisfied"]');
    cy.get('input[type="radio"][name="rating"][value="dissatisfied"]');
    cy.get('input[type="radio"][name="rating"][value="very-dissatisfied"]');

    cy.get('textarea[name="details"][id="details"]');
    cy.get('input[type="email"][name="email"][id="email"]');
    cy.get('input[type="submit"][name="send"][class="button"]');
})
