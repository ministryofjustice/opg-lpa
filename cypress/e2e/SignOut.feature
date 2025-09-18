@SignOut
Feature: SignOut

    I want to be able to sign out

    Background:
        Given I ignore application exceptions

    @focus
    Scenario: On sign out, user is redirected to feedback page
        Given I sign up "SignupAndSignoutTestUser" test user with password "Pass12345678"
        And I use activation email for "SignupAndSignoutTestUser" to visit the link
        When I log in as "SignupAndSignoutTestUser" test user
        Then a simulated click on the "sign-out" link causes a 302 redirect to "https://www.gov.uk/done/lasting-power-of-attorney"
