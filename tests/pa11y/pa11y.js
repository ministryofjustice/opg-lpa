const pa11y = require('pa11y');
const cli = require('pa11y-reporter-cli');
const puppeteer = require('puppeteer');

/*

Pa11y checks for common accessibility issues in your pages' code. More documentation at https://github.com/pa11y/pa11y-ci

It is not a replacement for understanding what accessibility is and how to build for it.

Inclusive design patterns is a good starting point if you need to undertand that: https://www.amazon.co.uk/Inclusive-Design-Patterns-Heydon-Pickering-ebook/dp/B01MAXK8XR

It checks code quality, so won't pick up content issues such as a picture of an apple with a text alternative of "orange" or a form label that it not correct for the input in question.

*/

(async function () {

    try{
        const browser = await puppeteer.launch({
            executablePath: '/usr/bin/chromium-browser',
            args: ['--disable-dev-shm-usage','--no-sandbox']
          });

        const config = {
            hideElements: 'svg', //svg fallback images currently cause issues due to fallback images beneath a role=presentation
            browser: browser //use the specified browser config
        };    

        const auth_config = {...config}; 

        //actions API is new so may change
        auth_config.actions=[
            'set field #email to test@test.com', //check email
            'set field #password to password1', //check pwd
            'click element button[type="submit"]',
            'wait for path to not be /login',
        ]

        //set of page tests to run, array of pa11y calls returning promises
        const full_results = await Promise.all([
            
            //actor side local homepage
            //pa11y('http://actor-web', config),

            //initial page post login actions
            pa11y('http://localhost:7002/login', auth_config),

            //you can add additional page tests here
        ]);

        full_results.map(function(result){
            const cliResults = cli.results(result);
            console.log(cliResults);
        });
        browser.close();

    }
    catch(error){
        console.log("Pa11y analysis failed\n", error)

        if(browser){
            browser.close();
        }
    }

})();
