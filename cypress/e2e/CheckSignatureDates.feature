Feature: Check signature dates for an LPA

	Background:
		Given I ignore application exceptions
		And I log in as seeded user

	Scenario: Viewing the Check Signature Dates tool
		Given I visit the dashboard
		When I click "check-signing-dates" for LPA ID 91155453023
		Then I am taken to "/lpa/91155453023/date-check"
		And I see "Check signature dates" in the title
		And there are "four" ".date-check-person" elements on the page
		And I can see fields for the donor, certificate provider, attorney, applicant

	# Scenario: Viewing the Check Signature Dates tool
	# 	Given I visit the dashboard
	# 	When I click "check-signing-dates" for LPA ID 33005588224
	# 	Then I am taken to "/33005588224/date-check"
	# 	And I see "Check signature dates" in the title
