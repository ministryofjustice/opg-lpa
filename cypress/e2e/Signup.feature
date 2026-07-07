@Signup
Feature: Signup

    I want to be able to sign up

    # Ensure that this file contains no scenarios which generate fixtures
    # (typically marked with @CleanupFixtures)

    # NB these tests are order-sensitive, as some rely on the signup state
    # being established before they run
    Background:
        Given I ignore application exceptions

    Scenario: Go to the create account page
        Given I visit "/signup"
        Then I should not find links in the page which open in new tabs without notifying me

    Scenario: Sign up with automatically generated test username and password
        Given I sign up standard test user
        Then I see "Please check your email" in the title
        And I see standard test user in the page text
        And I use activation email to visit the link
        And I see "Account activated" in the title
        And I visit link containing "sign in"
        Then I am taken to the login page

    Scenario: Valid About Me details
        Given I log in as standard test user
        When I select "Mr" on "name-title" with data-inited
        And I force fill out
          | name-first| Chris |
          | name-last| Smith |
          | dob-date-day| 1 |
          | dob-date-month| 12 |
          | dob-date-year| 1982 |
          | address-address1| 12 Highway Close |
          | address-postcode| PL45 9JA |
        And I click "save"
        Then I am taken to the lpa type page
