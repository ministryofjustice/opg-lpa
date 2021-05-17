@Statuses
Feature: Statuses

    I want to see the status of my LPA applications

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: I can see accurate statuses for my LPA applications (LPAL-92)
        Given I log in as appropriate test user

        # The statuses tested here are seeded into the test database; their
        # Sirius statuses are then mocked via the swagger-example.yaml and
        # nginx.conf configuration files for the gateway mock

        When I am taken to the dashboard page

        # no record in Sirius (404 from the gateway)
        And the LPA with ID "33718377316" should display with status "Waiting"

        # receipt date, Pending status on Sirius, no other date
        Then the LPA with ID "91155453023" should display with status "Received"

        # receipt date only, Perfect status on Sirius
        And the LPA with ID "54171193342" should display with status "Checking"

        # receipt date and registration date, Registered status on Sirius, no dispatch date
        And the LPA with ID "68582508781" should display with status "Checking"

        # receipt date and rejected date
        And the LPA with ID "88668805824" should display with status "Returned"

        # receipt, registration and dispatch dates
        And the LPA with ID "32004638272" should display with status "Returned"

        # receipt and invalid dates
        And the LPA with ID "93348314693" should display with status "Returned"

        # receipt and withdrawn dates
        And the LPA with ID "43476377885" should display with status "Returned"

