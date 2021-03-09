@SignUp
Feature: Signup

    I want to be able to sign up

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

    #    @focus
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

        # todo - note we should be selecting Mr like we do in the Valid scenario, not typing it in here, but due to system bug, after a previous error we get a text box
        # instead of a dropdown. The line below will therefore need to change to When I select Mr on name-title, once this bug is fixed
        When I type "Mr" into "name-title"
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
