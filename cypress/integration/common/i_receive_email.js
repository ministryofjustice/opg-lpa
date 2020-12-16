import { When } from "cypress-cucumber-preprocessor/steps";

var fs = require('fs');
var link = null;
var waitTime = 5000;
var checkCount = 0;
var activation_email_path = 'cypress/activation_emails/';

Then(`I receive email`, () => {
    var filename = activation_email_path + Cypress.env("userNumber") + '.activation';
    cy.log('Trying to open: ' + filename);
   
    cy.readFile(filename, { timeout: 100000 }).then(text => {
        var content = text;
        cy.log(text); 
        cy.log('Orig Content: ' + content);
        var contentStr = content;

        cy.log('Activation email has arrived!');

        cy.log('Content: ' + contentStr);
        console.log('Content: ');
        console.log(contentStr);
        link = contentStr.substring(contentStr.indexOf(",")+1);
        cy.log('Opening activation link: ' + link);
        cy.visit(link);
    })
})

