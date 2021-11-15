@SignUp
Feature: Signup

    I want to be able to sign up

    # Ensure that this file contains no scenarios which generate fixtures
    # (typically marked with @CleanupFixtures)

    # NB these tests are order-sensitive, as some rely on the signup state
    # being established before they run
    Background:
        Given I ignore application exceptions

    @focus
    Scenario: Go to the create account page
        Given I visit "/signup"
        Then I should not find links in the page which open in new tabs without notifying me

    @focus
    Scenario: Sign up with automatically generated test username and password
        Given I sign up standard test user
        Then I see "Please check your email" in the title
        And I see standard test user in the page text
        And I use activation email to visit the link
        And I see "Account activated" in the title
        And I visit link containing "sign in"
        Then I am taken to the login page

    @focus
    Scenario: Cancel button is not shown on "Your details" the first time the user logs in (LPAL-210)
        Given I log in as standard test user
        When I am taken to the your details page for a new user
        Then I do not see "Cancel" in the page text

    @focus
    Scenario: About Me Details have Blank title, month and wrong postcode in address + long names, followed by DOB in future
        Given I log in as standard test user
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
        When I select "Mr" on "name-title"
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

    @focus
    Scenario: Valid About Me details
        Given I log in as standard test user
        Then I see "Make a lasting power of attorney" in the page text
        And I see "Your details" in the title
        When I select "Mr" on "name-title"
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
        And I see "What type of LPA do you want to make?" in the page text
        # logout test done here for consistency with original Casper tests
        # cypress seems t choke on being redirected off our site though
        # When I logout
        #Then I am taken to the post logout url

    @focus
    # this scenario must run after the user has saved their details for the first time
    Scenario: Cancel buttons are present on "your details" pages after user has saved their details (LPAL-210)
        Given I log in as standard test user

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
