Feature: Check signature dates for a PF LPA

	Background:
		Given I ignore application exceptions
		And I log in as seeded user

  Scenario: View the Check Signature Dates tool
		Given I visit the dashboard
		When I click "check-signing-dates" for LPA ID 91155453023
		Then I am taken to "/lpa/91155453023/date-check"
		And I see "Check signature dates" in the title
		And there are "four" ".date-check-person" elements on the page
		And I can see fields for the PF donor, certificate provider, attorney, applicant

	Scenario: An LPA with trust corporation as attorney [continuation sheet 4]
    Given I visit the dashboard
    When I click "check-signing-dates" for LPA ID 33005588224
    Then I am taken to "/lpa/33005588224/date-check"
    And I can see a reminder on signing continuation sheet 4
