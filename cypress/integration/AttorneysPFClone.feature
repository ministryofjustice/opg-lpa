@CreateLpa
Feature: Add attorneys to a Property and Finance LPA

    I want to add attorneys to a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with a donor

    @focus, @CleanupFixtures
    Scenario: Add Attorneys
        When I log in as appropriate test user
        And I visit the primary attorney page for the test fixture lpa
        
        # ** CUT Above Here ** This comment line needed for stitching feature files. Please do not remove
        When I click "add-attorney"
        And I opt not to re-use details if lpa is a clone
        Then I can find "form-attorney"
        And I can find "form-cancel"
        And I can find "postcode-lookup"
        And I can find "name-title" with 8 options
        And I can find use-my-details if lpa is new
        And I click "use-trust-corporation"
        When I force fill out
            | name | Standard Trust |
            | number | 678437685 |
            | email-address| opglpademo+trustcorp@gmail.com |
            | address-address1 | 1 Laburnum Place |
            | address-address2 | Sketty |
            | address-address3 | Swansea, Abertawe |
            | address-postcode | SA2 8HT |
        And I click "form-save"
        Then I see "Standard Trust" in the page text
        When I click "save"
        Then I am taken to the replacement attorney page
