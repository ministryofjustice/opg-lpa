[![Build Status](https://travis-ci.org/alphagov/stageprompt.png?branch=master)](https://travis-ci.org/alphagov/stageprompt?branch=master)

# Stageprompt

`Stageprompt` is a javascript library for tracking a user journey in an
analytics service. Data attributes are added to page elements, these are then
detected by stageprompt and cause custom events to be sent to the configured
analytics product.

## Download

Latest version: [stageprompt.2.0.1.js](https://github.com/alphagov/stageprompt/releases/2.0.1/2460/stageprompt.2.0.1.js)

## Dependencies

- An analytics product, such as Google Analytics or Piwik
- jQuery (currently mirroring the version from https://github.com/alphagov/static/tree/master/app/assets/javascripts/libs/jquery)

## Running tests

You can run the tests using [Rake](http://rake.rubyforge.org/) and [Jasmine](http://pivotal.github.io/jasmine/). 

Setup your environment:
 
* Install Ruby 1.9.3
* in the project folder, run: `bundle install`

To run the tests in a browser:

* run: `bundle exec rake jasmine`
* open in your browser: [http://localhost:8888](http://localhost:8888)

To run the tests from the command line:

* run: `bundle exec rake jasmine:ci`

## Setup

Include `stageprompt.js` in your HTML, and set it up by providing a function
to be called at each stage.

The function should be added to the DOM onLoad event handler.

Either use the "out of the box" setup for Google Analytics:

    GOVUK.performance.stageprompt.setupForGoogleAnalytics();

Or configure the callback yourself. In the example below an event is sent to
Google Analytics for each stage:

    $(function () {
      GOVUK.performance.stageprompt.setup(function (category, event, label) {
        _gaq.push(['_trackEvent', category, event, label, undefined, true]);
      });
    });

If you are using another analytics product, such as Piwik, you can configure the callback accordingly:

    $(function () {
      GOVUK.performance.stageprompt.setup(function (category, event, label) {
        // Code to send to your Piwik account
      });
    });

## Sending events

The sending of events to your analytics product is controlled by data attributes
added to HTML elements in your pages. The value of the data attribute should
be a colon (:) separeted identifier for the event that will be fired. The
template for these identifiers is as follows.

```
{transaction identifier}:{event type}:{event identifier}
```

For example, an event to describe a user reaching the confirm stage of your
'buy a badger' transaction might have the following identifier.

```
buy-a-badger:stage:confirm
```

### Page entry events

Page entry events are fired when a user reaches a given page. Add a
`data-journey` attribute to the HTML of each page in your journey.

For example:

On `/pay-register-birth-abroad/start`:

    <div id="wrapper" class="transaction" data-journey="pay-register-birth-abroad:stage:start">
        [...]
    </div>

The user clicks "Calculate total" and is sent to `/pay-register-birth-abroad/confirm` which has the HTML:

    <div id="wrapper" class="transaction" data-journey="pay-register-birth-abroad:stage:confirm">
        [...]
    </div>

After clicking "Pay" and entering their details at the provider's site, the
user is redirected back to GOV.UK at `/pay-register-birth-abroad/done`:

    <div id="wrapper" class="transaction" data-journey="pay-register-birth-abroad:stage:done">
        [...]
    </div>

### User click events

User click events are fired when the annotated element is clicked. Add a 
`data-journey-click` attribute to the element clicked by the user.

For example:

    <a class="help-button" href="#" data-journey-click="stage:help:info">See more info...</a>

Note that if the link causes the user to go to another page the results may not
be as expected. For example, with Google Analytics the event will be fired from
the subsequent page if that page is using the same GA account, or not at all
if it the link is off site.

## Google Analytics itegration

The event identifier is split up and stored in the following fields.

- the transaction identifier is stored in the category field
- the event type is stored in the event field
- the event identifier is stored in the label field

## Backdrop integration

When sent to backdrop through the backdrop-ga-collector the event identifier
is further broken down by colon allowing you to create finer grained namespaces
for your event identifiers.
