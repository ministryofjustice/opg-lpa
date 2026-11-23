@DashboardDeleteLpa @SignupIncluded
Feature: DashboardDeleteLpa

    I want to be able to delete an LPA on the dashboard

    Background:
        Given I ignore application exceptions

    Scenario: Sign up with new user, create LPA, delete LPA (LPAL-840)
        Given I sign up "DashboardDeleteLpaUser" test user with password "Pass12345678"
        When I use activation email for "DashboardDeleteLpaUser" to visit the link
        Then I see "Account activated" in the title

        Given I log in as "DashboardDeleteLpaUser" test user
        And I force fill out
          | name-title| Mr |
          | name-first| Partytime |
          | name-last| Chellingston |
          | dob-date-day| 3 |
          | dob-date-month| 3 |
          | dob-date-year| 1959 |
          | address-address1| 14 HEREBENENENEFORD CLOSE |
          | address-postcode| PC38 8PA |
        And I click "save"
        And If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page

        # Add an LPA
        Given I choose Property and Finance
        When I click "save"
        Then I am taken to the donor page

        Given I click element marked "Your LPAs"
        Then there is "one" "LPA" element on the page

        When I click "delete-lpa"
        Then I can see popup
        Then I double click "delete" and the second click is prevented

        And I do not see "LPA could not be deleted" in the page text

        And If I am on dashboard I click to create lpa
        Then I am taken to the lpa type page

        Given I click element marked "Your LPAs"
        Then there are "zero" "LPA" elements on the page
