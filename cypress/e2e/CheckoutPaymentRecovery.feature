Feature: Recovering a card payment that GOV.UK Pay took but never confirmed back to us

  Some users pay successfully and then never return to the service, could be that they close
  the tab as soon as the payment completes - so the redirect that records the payment is
  never made. The payment is taken, but the LPA is left unlocked with nothing on it but a
  gateway reference, and the user is stuck.

  Returning to the LPA should repair it without the user having to do anything, and
  without ever showing them a "pay" button they have every reason to refuse to press.

  Background:
    Given I create a new user with an LPA that is ready to pay
    And I log in as the newly created fixture user
    And I visit the checkout page for the fixture LPA
    And I pay by card but never return to the service

  @CheckoutPaymentGateway
  Scenario: The stranded payment is recovered when the user opens the checkout page
    When I visit the checkout page for the fixture LPA
    Then I am taken to the complete page
    And I see "Payment received" in the page text

  @CheckoutPaymentGateway
  Scenario: The stranded payment is recovered when the user clicks Continue on the dashboard
    When I visit the dashboard
    When I click continue on the dashboard for the test fixture lpa
    Then I am taken to the complete page
    And I see "Payment received" in the page text
    And I can find link pointing to "/lp1"
