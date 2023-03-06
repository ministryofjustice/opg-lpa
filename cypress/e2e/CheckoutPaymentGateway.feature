Feature: Checkout through payment gateway for a Property and Finance LPA

    Background:
        Given I ignore application exceptions
        And I create PF LPA test fixture with donor, attorneys, replacement attorneys, cert provider, people to notify, instructions, preferences, applicant, correspondent, who are you, repeat application, fee reduction

    @CheckoutPaymentGateway @CleanupFixtures
    Scenario: Checkout through payment gateway
        Given I log in as appropriate test user
        And I visit the checkout page for the test fixture lpa
        Then I am taken to the checkout page

        When I click "confirm-and-pay-by-card"
        And I complete the payment with card number "9999-9999-9999-9999"
        And I confirm the payment
        Then I see "Thank you for your payment of £20.50" in the page text
