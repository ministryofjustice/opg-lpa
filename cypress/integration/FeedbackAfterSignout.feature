Feature: Survey upon signout
 
  I want to be able to fill out a satisfaction survey when signing out from the Make an LPA Service
  
  @focus
  Scenario: Get to feedback page after signout
        When I log in as seeded user
        Then I am taken to the dashboard page
        When I visit link containing "Sign out"
        Then I am taken to the completed feedback page
