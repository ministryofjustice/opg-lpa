Feature: Recovering a card payment that GOV.UK Pay took but never confirmed back to us

  Some users pay successfully and then never return to the service, could be that they close
  the tab as soon as the payment completes - so the redirect that records the payment is
  never made. The payment is taken, but the LPA is left unlocked with nothing on it but a
  gateway reference, and the user is stuck.

  Returning to the LPA should repair it without the user having to do anything, and
  without ever showing them a "pay" button they have every reason to refuse to press.

  Tagged @CheckoutPaymentGateway because it needs the mock-pay container to answer the
  GOV.UK Pay lookup; like the other gateway test it is excluded from the CI runs that
  execute against deployed environments.

  Background:
    Given I ignore application exceptions
    And I create PF LPA test fixture with a card payment that was never recorded

  @CheckoutPaymentGateway @CleanupFixtures
  Scenario: The stranded payment is recovered when the user opens the checkout page
    Given I log in as appropriate test user
    And I visit the checkout page for the test fixture lpa
    Then I am taken to the complete page
    And I see "Payment received" in the page text

  @CheckoutPaymentGateway @CleanupFixtures
  Scenario: The stranded payment is recovered when the user clicks Continue on the dashboard
    Given I log in as appropriate test user
    And I visit the dashboard
    When I click continue on the dashboard for the test fixture lpa
    Then I am taken to the complete page
    And I see "Payment received" in the page text
    And I can find link pointing to "/lp1"
