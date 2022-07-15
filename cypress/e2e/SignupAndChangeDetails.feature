@SignUpAndChangeDetails
Feature: SignupAndChangeDetails

    I want to be able to sign up and immediately change my password and email address

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Sign up with automatically generated test username and password
        Given I sign up "SignupAndChangeDetailsUser" test user with password "Pass1234"
        When I use activation email for "SignupAndChangeDetailsUser" to visit the link
        Then I see "Account activated" in the title

    @focus
    Scenario: Enter valid "About You" details
        Given I log in as "SignupAndChangeDetailsUser" test user
        When I select "Mr" on "name-title"
        And I force fill out
          | name-first| Hammer |
          | name-last| Vortigax |
          | dob-date-day| 1 |
          | dob-date-month| 12 |
          | dob-date-year| 1978 |
          | address-address1| 12 PARANOIA CLOSE |
          | address-postcode| PC45 9JA |
        And I click "save"
        Then I am taken to the lpa type page

    @focus
    Scenario: Mismatched passwords in password change screen result in error message instead of raw 500 page (LPAL-651)
        Given I log in as "SignupAndChangeDetailsUser" test user
        And I visit the your details page
        And I visit link containing "Your details"
        And I visit link containing "Change Password"
        When I try to change password for "SignupAndChangeDetailsUser" with a mismatch
        Then I see "Enter matching passwords" in the page text

    @focus
    Scenario: Mismatched email addresses in email change screen result in error message instead of raw 500 page (LPAL-651)
        Given I log in as "SignupAndChangeDetailsUser" test user
        And I visit the your details page
        And I visit link containing "Your details"
        And I visit link containing "Change Email Address"
        When I try to change email address for "SignupAndChangeDetailsUser" with a mismatch
        Then I see "Enter matching email addresses" in the page text
