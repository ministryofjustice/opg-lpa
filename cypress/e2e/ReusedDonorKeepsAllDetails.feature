@ReusedDonorKeepsAllDetails @SignupIncluded
Feature: ReusedDonorKeepsAllDetails

    When reusing a donor, all of their details are retained, including whether they can sign the LPA themselves (LPAL-908)

    Background:
        Given I ignore application exceptions

    Scenario: Create LPA with donor who can't sign; on reuse, donor "can't sign" checkbox is ticked
        # Sign up with automatically generated test username and password
        Given I sign up "ReusedDonorKeepsAllDetailsUser" test user with password "Pass12345678"
        When I use activation email for "ReusedDonorKeepsAllDetailsUser" to visit the link
        Then I see "Account activated" in the title

        # Log in
        Given I log in as "ReusedDonorKeepsAllDetailsUser" test user

        # New users have to fill out their details
        When I select "Dr" on "name-title" with data-inited
        And I force fill out
          | name-first| Dax |
          | name-last| Peromptigal |
          | dob-date-day| 2 |
          | dob-date-month| 11 |
          | dob-date-year| 1977 |
          | address-address1| 12 VERNAX AVENUE |
          | address-postcode| PC47 9JB |
        And I click "save"
        Then I am taken to the lpa type page

        # Choose PF type
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page

        # Add donor
        When I click "add-donor"
        Then I can find "form-donor"

        When I select "Dr" on "name-title" with data-inited
        And I force fill out
            | name-first | Herbert |
            | name-last | Gallantrathron |
            | dob-date-day| 22 |
            | dob-date-month| 10 |
            | dob-date-year| 1974 |
            | address-address1| Beezil Beezil Farm House |
            | address-postcode| PO38 1UL |

        # IMPORTANT - we are checking the "cannot sign" checkbox for this donor
        And I check "cannot-sign"
        And I click "form-save"
        Then I see "Dr Herbert Gallantrathron" in the page text

        When I click "save-and-continue"
        Then I am taken to the when lpa starts page

        # Select when LPA can be used
        When I check "when-no-capacity"
        And I click "save"
        Then I am taken to the primary attorney page

        # Primary attorney
        When I click "add-attorney"
        Then I can find "form-attorney"

        When I select "Mr" on "name-title" with data-inited
        And I force fill out
            | name-first | Jeffort |
            | name-last | Splodeicon |
            | dob-date-day| 12 |
            | dob-date-month| 7 |
            | dob-date-year| 1965 |
            | address-address1| Sovathon Cottage |
            | address-postcode| ST14 8NX |
        And I click "form-save"
        Then I see "Mr Jeffort Splodeicon" in the page text

        When I click "save"
        Then I am taken to the replacement attorney page

        # Skip replacement attorney, certificate provider, and preferences and instructions pages
        When I click "save"
        Then I am taken to the certificate provider page

        When I click "skip-certificate-provider"
        Then I am taken to the people to notify page

        When I click "save"
        Then I am taken to the instructions page

        When I visit the dashboard
        Then I can see a "Reuse LPA details" link for the test fixture lpa

        # Reuse the LPA
        When I click the "Reuse LPA details" link for the test fixture lpa
        Then I am taken to the type page for cloned lpa

        # Choose PF type
        When I choose Property and Finance
        And I click "save"
        Then I am taken to the donor page

        # Add a donor, choosing the same donor as we used on the previous LPA
        When I click "add-donor"
        Then I can find "form-reuse-details"

        # Confirm that the checkbox for "cannot sign" is checked, same as for the previous LPA
        When I click the option labelled with "Herbert Gallantrathron (was the donor)" in the reuse popup
        And I click "continue"
        Then I can find "form-donor"

        # IMPORTANT - as we're reusing a donor who can't sign, the can't sign checkbox should
        # be checked here as well
        And "cannot-sign" is checked
