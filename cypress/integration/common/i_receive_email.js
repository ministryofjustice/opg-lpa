import { When } from "cypress-cucumber-preprocessor/steps";

var fs = require('fs');
var link = null;
var waitTime = 5000;
var checkCount = 0;

Then(`I receive email`, () => {
    getActivationLink();
   // this.wait(waitTime, getActivationLink);
    // Wait for the email to arrive...

    if( link == null ){
        //test.fail('Failed to receive Activation Link.');
        cy.log('Failed to receive Activation Link.');
    }

    cy.log('Opening activation link: ' + link);
    
    //this.open(link, {
    //    method: 'get'
    //});
})

function getActivationLink(){

    var filename = '/mnt/test/functional/activation_emails/' + Cypress.env("userNumber") + '.activation';
    
    if( cy.readFile(filename, { timeout: 200000 }) ){

        cy.log('Activation email has arrived!');

        //var content = fs.read( filename );
        //link = content.substring(content.indexOf(",")+1);

        //cy.log('Content: ' + content);
        //cy.log('Link: ' + link);

    } else {

        if( checkCount <= 40 ){

            cy.log('Activation email has not arrived yet. Waiting...');
            //this.wait(waitTime, getActivationLink);
            getActivationLink();

            checkCount++;

        }

    } // if

}; // function
