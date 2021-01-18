Feature: Authentication
 
  I want to log on to Make an LPA Service
  # todo : this is a copy of Casper's 03-Authentication test. Its here to provide continuity from Casper but may ultimately be removed as duplicate, because other tests
  # also reply on authentication, for example Signup.feature does a similar login and check that it ends up on the right page
  
  @focus
  Scenario: Logging into Make an LPA with existing seeded user that has some LPAs
        When I log in as seeded user
        Then I am taken to the dashboard page
        Then I see "Your LPAs" in the title
