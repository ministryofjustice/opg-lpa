import { When } from "cypress-cucumber-preprocessor/steps";

var link = null;
var activation_email_path = 'cypress/activation_emails/';

Then(`I use activation email to visit the link`, () => {
    openEmailAndVisitLink('activation');
})

Then(`I use password reset email to visit the link`, () => {
    openEmailAndVisitLink('passwordreset');
})

function openEmailAndVisitLink(type){
    var filename = activation_email_path + Cypress.env("userNumber") + '.' + type;
    cy.log('Trying to open: ' + filename);
   
    cy.readFile(filename, { timeout: 100000 }).then(text => {
        var content = text;
        cy.log(text); 
        cy.log('Orig Content: ' + content);
        var contentStr = content;

        cy.log(type + ' email has arrived!');

        cy.log('Content: ' + contentStr);
        console.log('Content: ');
        console.log(contentStr);
        link = contentStr.substring(contentStr.indexOf(",")+1);
        cy.log('Opening ' + type + ' link: ' + link);
        cy.visit(link);
    })
}
