Feature: Signup
 
    I want to be able to sign up

    Background:
        Given I ignore application exceptions
  
    @focus
    Scenario: Sign up with automatically generated test username and password
        Given I sign up standard test user
        Then I see "Please check your email" in the title
        And I see standard test user in the page text
        And I receive email
        And I see "Account activated" in the title
        Then I log in as standard test user
        And I see "Your details" in the title
        #When I submit About Me details with Blank title, month and wrong postcode in address + long names
        #Then I see "There was a problem" in the page text
        #When I submit About Me details with DOB in the future
        # todo - the system response to this is not sufficiently informative
        #Then I see "There was a problem" in the page text 
        When I submit valid About Me details
        Given I force fill out 
          |  #name-title| Mr|
          |  name-first| Chris|
          |  name-last| Smith|
          |  dob-date-day| 1|
          |  dob-date-month| 12 |
          |  dob-date-year| 1982 |
          |  address-address1| 12 Highway Close |
          |  address-postcode| PL45 9JA |
        Then I click "save"
        Then I see "What type of LPA do you want to make?" in the page text
