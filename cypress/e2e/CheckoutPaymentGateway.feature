@CheckoutPaymentGateway
Feature: Checkout through payment gateway for a Property and Finance LPA

    Background:
        Given I create a new user with an LPA that is ready to pay
        And I log in as the newly created fixture user
        And I visit the checkout page for the fixture LPA
        Then I am on the checkout page for the fixture LPA

    Scenario: Checkout through payment gateway
        When I click "confirm-and-pay-by-card"
        And I complete the payment with card number "4444333322221111"
        And I confirm the payment
        Then I see "Thank you for your payment of £92" in the page text

    Scenario: Double clicking confirm and pay by card only submits once
        Then I double click "confirm-and-pay-by-card" and the second click is prevented

    Scenario: Double clicking confirm and pay by cheque only submits once
        Then I double click "confirm-and-pay-by-cheque" and the second click is prevented

    Scenario: Double clicking retry online payment after a cancelled payment only submits once
        When I click "confirm-and-pay-by-card"
        And I cancel the payment
        And I confirm the payment
        Then I double click "retry-online-payment" and the second click is prevented

    Scenario: Double clicking pay by cheque after a cancelled payment only submits once
        When I click "confirm-and-pay-by-card"
        And I cancel the payment
        And I confirm the payment
        Then I double click "pay-by-cheque" and the second click is prevented

    Scenario: Double clicking retry online payment after a failed payment only submits once
        When I click "confirm-and-pay-by-card"
        And I fail the payment
        And I confirm the payment
        Then I double click "retry-online-payment" and the second click is prevented

    Scenario: Double clicking pay by cheque after a failed payment only submits once
        When I click "confirm-and-pay-by-card"
        And I fail the payment
        And I confirm the payment
        Then I double click "pay-by-cheque" and the second click is prevented
