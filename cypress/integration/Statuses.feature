@Statuses
Feature: Status display for LPAs

    I want to see the status of my LPA applications

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: I can see accurate statuses for my LPA applications on the dashboard (LPAL-92)
        Given I log in as seeded user

        # The statuses tested here are seeded into the test database; their
        # Sirius statuses are then mocked via the swagger-example.yaml and
        # nginx.conf configuration files in the gateway mock

        When I am taken to the dashboard page

        # no record in Sirius (404 from the gateway)
        Then the LPA with ID "33718377316" should display with status "Waiting"

        # receipt date, Pending status on Sirius, no other date
        And the LPA with ID "91155453023" should display with status "Received"

        # receipt date only, Perfect status on Sirius
        And the LPA with ID "54171193342" should display with status "Checking"

        # receipt date and registration date, Registered status on Sirius, no dispatch date
        And the LPA with ID "68582508781" should display with status "Checking"

        # receipt date and rejected date
        And the LPA with ID "88668805824" should display with status "Processed"

        # receipt, registration and dispatch dates
        And the LPA with ID "32004638272" should display with status "Processed"

        # receipt and invalid dates
        And the LPA with ID "93348314693" should display with status "Processed"

        # receipt and withdrawn dates
        And the LPA with ID "43476377885" should display with status "Processed"

        # receipt date, Payment Pending status on Sirius
        And the LPA with ID "48218451245" should display with status "Received"

        # status date and Return - unpaid status on Sirius
        And the LPA with ID "15527329531" should display with status "Processed"

    @focus
    Scenario: The status message page for an LPA has the title "Status message" (LPAL-432)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "waiting" message link for LPA with ID "33718377316"
        And I am taken to the detail page for LPA with ID "33718377316"
        Then I see "Status message" in the title

    @focus
    Scenario: An LPA which has not been received yet displays as "Waiting" on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "waiting" message link for LPA with ID "33718377316"
        Then I am taken to the detail page for LPA with ID "33718377316"
        And I see "A337 1837 7316" in the page text
        And the LPA status is shown as "Waiting"

    @focus
    Scenario: An LPA which is received and is Perfect displays as "Checking" on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "checking" message link for LPA with ID "54171193342"
        Then I am taken to the detail page for LPA with ID "54171193342"
        And I see "A541 7119 3342" in the page text
        And the LPA status is shown as "Checking"

    @focus
    Scenario: An LPA which is received and is Pending displays as "Received" on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "received" message link for LPA with ID "91155453023"
        Then I am taken to the detail page for LPA with ID "91155453023"
        And I see "A911 5545 3023" in the page text
        And the LPA status is shown as "Received"

    @focus
    Scenario: A registered LPA with no dispatch date displays as "Checking" on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "checking" message link for LPA with ID "68582508781"
        Then I am taken to the detail page for LPA with ID "68582508781"
        And I see "A685 8250 8781" in the page text
        And the LPA status is shown as "Checking"

    @focus
    Scenario: A registered and dispatched LPA displays as "Processed" with dispatch date + 15 working days on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "processed" message link for LPA with ID "32004638272"
        Then I am taken to the detail page for LPA with ID "32004638272"
        And I see "A320 0463 8272" in the page text
        And the LPA status is shown as "Processed"
        And I see "donor and all attorneys on the LPA will get a letter" in the page text

        # dispatch date is 03/05/21
        And the date by which the LPA should be received is shown as "24/05/21"

    @focus
    Scenario: A rejected LPA displays as "Processed" with rejection date + 15 working days on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "processed" message link for LPA with ID "88668805824"
        Then I am taken to the detail page for LPA with ID "88668805824"
        And I see "A886 6880 5824" in the page text
        And the LPA status is shown as "Processed"

        # rejection date is 14/02/2021
        And the date by which the LPA should be received is shown as "05/03/21"

    @focus
    Scenario: A withdrawn LPA displays as "Processed" with withdrawn date + 15 working days on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "processed" message link for LPA with ID "43476377885"
        Then I am taken to the detail page for LPA with ID "43476377885"
        And I see "A434 7637 7885" in the page text
        And the LPA status is shown as "Processed"

        # withdrawn date is 06/05/2020
        And the date by which the LPA should be received is shown as "27/05/20"

    @focus
    Scenario: An invalid LPA displays as "Processed" with invalid date + 15 working days on its status page (LPAL-92)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "processed" message link for LPA with ID "93348314693"
        Then I am taken to the detail page for LPA with ID "93348314693"
        And I see "A933 4831 4693" in the page text
        And the LPA status is shown as "Processed"

        # invalid date is 05/01/2021
        And the date by which the LPA should be received is shown as "26/01/21"

    @focus
    Scenario: An LPA which is received and is Payment Pending displays as "Received" on its status page (LPAL-543)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "received" message link for LPA with ID "48218451245"
        Then I am taken to the detail page for LPA with ID "48218451245"
        And I see "A482 1845 1245" in the page text
        And the LPA status is shown as "Received"

    @focus
    Scenario: An LPA which is received and is Payment Pending displays as "Processed" on its status page (LPAL-549)
        Given I log in as seeded user
        When I am taken to the dashboard page
        And I click on the View "processed" message link for LPA with ID "15527329531"
        Then I am taken to the detail page for LPA with ID "15527329531"
        And I see "A155 2732 9531" in the page text
        And the LPA status is shown as "Processed"
        And I do not see "donor and all attorneys on the LPA will get a letter" in the page text

        # Return unpaid status was set on statusDate which becomes dispatch date of 27/02/20
        And the date by which the LPA should be received is shown as "19/03/20"
