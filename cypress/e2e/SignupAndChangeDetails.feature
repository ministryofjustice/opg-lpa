@SignupAndChangeDetails @SignupIncluded
Feature: SignupAndChangeDetails

    I want to be able to sign up and immediately change my password and email address

    Background:
        Given I ignore application exceptions

    Scenario: Sign up with automatically generated test username and password
        Given I sign up "SignupAndChangeDetailsUser" test user with password "Pass12345678"
        When I use activation email for "SignupAndChangeDetailsUser" to visit the link
        Then I see "Account activated" in the title

    # must run before user has filled in "Your details" page for the first time
    Scenario: Cancel button is not shown on "Your details" the first time the user logs in (LPAL-210)
        Given I log in as "SignupAndChangeDetailsUser" test user
        When I am taken to the your details page for a new user
        Then I do not see "Cancel" in the page text

    Scenario: About Me Details have Blank title, month and wrong postcode in address + long names, followed by DOB in future
        Given I log in as "SignupAndChangeDetailsUser" test user
        Then I see "Make a lasting power of attorney" in the page text
        And I see "Your details" in the title
        When I force fill out
          | name-first | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
          | name-last  | qo06zCs3DEtroWJF8U7eqo7LWeO47Cc5NVbCLPOfL7TROMO5S7JCCZkNulCD7tpVi0x9kB |
          | dob-date-day | 1 |
          | dob-date-year | 1982 |
          | address-address1| 12 Highway Close |
          | address-postcode| wrongpostcode |
        And I click "save"
        Then I see "There is a problem" in the page text
        And I see "Error" in the title
        When I select "Mr" on "name-title" with data-inited
        And I force fill out
          | name-first| Chris |
          | name-last| Smith |
          | dob-date-day| 1 |
          | dob-date-month| 1 |
          | dob-date-year| 5500 |
          | address-address1| 12 Highway Close |
          | address-postcode| PL45 9JA |
        And I click "save"
        Then I see "There is a problem" in the page text
        And I see "Error" in the title

    Scenario: Enter valid "About You" details
        Given I log in as "SignupAndChangeDetailsUser" test user
        Then I see "Make a lasting power of attorney" in the page text
        And I see "Your details" in the title
        When I select "Mr" on "name-title" with data-inited
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
        And I see "What type of LPA do you want to make?" in the page text

    # this scenario must run after the user has saved their details for the first time
    Scenario: Cancel buttons are present on "your details" pages after user has saved their details (LPAL-210)
        Given I log in as "SignupAndChangeDetailsUser" test user

        When I visit link containing "Your details"
        Then I see "Your details" in the page text
        And I see "Cancel" in the page text

        When I click element marked "Change Password"
        Then I see "Change your password" in the page text
        And I see "Cancel" in the page text

        When I visit link containing "Cancel"
        Then I am taken to "/user/about-you"

        When I click element marked "Change Email Address"
        Then I see "Change your sign-in email address" in the page text
        Then I see "Cancel" in the page text

        When I visit link containing "Cancel"
        Then I am taken to "/user/about-you"

        When I visit link containing "Cancel"
        Then I am taken to the lpa type page

    Scenario: Mismatched passwords in password change screen result in error message instead of raw 500 page (LPAL-651)
        Given I log in as "SignupAndChangeDetailsUser" test user
        And I visit the your details page
        And I visit link containing "Your details"
        And I visit link containing "Change Password"
        When I try to change password for "SignupAndChangeDetailsUser" with a mismatch
        Then I see "Enter matching passwords" in the page text

    Scenario: Mismatched email addresses in email change screen result in error message instead of raw 500 page (LPAL-651)
        Given I log in as "SignupAndChangeDetailsUser" test user
        And I visit the your details page
        And I visit link containing "Your details"
        And I visit link containing "Change Email Address"
        When I try to change email address for "SignupAndChangeDetailsUser" with a mismatch
        Then I see "Enter matching email addresses" in the page text
