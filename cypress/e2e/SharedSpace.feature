@SharedSpace
Feature: Shared Space

  @RequiresOneLogin
  Scenario: Professionals can create a shared space from user dashboard
    Given I visit "/login"
    And Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "other people as part of my work"
    And I say "No"
    Then I am asked for my details
    When I click element marked "Search for UK postcode"
    And I type "B1 1TF" into "postcode-lookup" working around cypress bug
    And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
    And I click element marked "Find UK address"
    And I select option "THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM" of "address-search-result"
    Then I see "address-postcode" prepopulated with "B1 1TF"
    When I enter my name and date of birth and save
    Then I am taken to "/user/dashboard?account=created"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And I see "Shared space" in the title
    When I click element marked "Create a shared space"
    Then I should be on "/shared-space/make"
    When I type "Example Organisation" into "space-name"
    When I click element marked "Save"
    Then I should be on "/shared-space/dashboard?created=success"
    And I see a success notification with content "Example Organisation has been created"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And I see "Manage Shared Space" in the title
    And element "table" contains "Brand New"

  @RequiresOneLogin
  Scenario: Lay users cannot create a shared space from user dashboard
    Given I visit "/login"
    And Ordnance Survey postcode lookup responses are stubbed out for good postcode B1 1TF
    When I click "onelogin-signin-button"
    And I continue through mock One Login
    And I say I make LPAs for "myself, family or friends"
    And I say "No"
    Then I am asked for my details
    When I click element marked "Search for UK postcode"
    And I type "B1 1TF" into "postcode-lookup" working around cypress bug
    And I see "postcode-lookup" prepopulated within timeout with "B1 1TF"
    And I click element marked "Find UK address"
    And I select option "THE OFFICE OF THE PUBLIC GUARDIAN, THE AXIS, 10 HOLLIDAY STREET, BIRMINGHAM" of "address-search-result"
    Then I see "address-postcode" prepopulated with "B1 1TF"
    When I enter my name and date of birth and save
    Then I am taken to "/user/dashboard?account=created"
    Then element "nav" does not contain "Shared space"

  Scenario: Can manage admin permissions of other members
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "admin"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I uncheck checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "member"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I check checkbox labelled "Admin"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" permissions should be set to "admin"

  Scenario: Can invite a member to a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite sent"

  Scenario: Can not invite a member to a shared space when already invited or already a member
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"
    And I log in as the newly created fixture user
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "Member" into field labelled "First names"
    And I type "One" into field labelled "Last name"
    And I type the email of the added member into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space/invite"
    And I see "Email address belongs to an active member of this shared space -– enter a different email address" in the page text
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite sent"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space/invite"
    And I see "Email address is for a pending member of this shared space – enter a different one or cancel the previous invite" in the page text

  Scenario: Can revoke a members invite
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Invite member"
    Then I should be on "/shared-space/invite"
    When I type "John" into field labelled "First names"
    And I type "Smith" into field labelled "Last name"
    And I type "john.smith@example.com" into field labelled "Email"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite sent"
    When I click element marked "Revoke invite"
    Then I should be on "/shared-space/revoke-invite/"
    Then I submit the form
    Then I should be on "/shared-space"
    And I see a success notification with content "Invite revoked"

  Scenario: Can suspend a member from a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin"

    When I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Suspend access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" status should be "suspended"
    And I log out

    When I try to log in as the member added to the shared space
    Then I should be on "/shared-space/dashboard"
    And I see a notification with title "Access suspended"

    When I try to log in as "fixtureUser"
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I select radio labelled "Allow access to this shared space"
    And I click element marked "Save"
    Then I should be on "/shared-space"
    And "Member 1" status should be "active"

  @RequiresOneLogin
  Scenario: Lay users can join a shared space
    Given I have been invited to a shared space called "Example Organisation" with 1 LPA
    When I log in through Onelogin as the newly created fixture user
    And I say I make LPAs for "myself, family or friends"
    And I create a new Make account
    Then I should be on "/user/dashboard"
    And I click element marked "Shared space"
    And I should be on "/shared-space/join"
    And I type "Example Organisation" into field labelled "Shared space name"
    And I type the access code into field labelled "Joining code"
    And I click element marked "Join shared space"
    And I should be on "/shared-space/dashboard"
    And I see a success notification with content "You’ve joined the shared space."
    And I click element marked "Shared space"
    And I cannot see any invites

  @RequiresOneLogin
  Scenario: Cannot join a shared space when invite email does not match one login email
    Given I have been invited to a shared space called "Example Organisation" with 1 LPA
    When I log in through Onelogin as a random user
    And I say I make LPAs for "myself, family or friends"
    And I should be on "/existing-account"
    And I create a new Make account
    Then element "nav" does not contain "Shared space"

  Scenario: Can delete a member from a shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is an "admin" with 1 LPA
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    And there are "two" "LPA" elements on the page
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    When I click element marked "Member 1"
    Then I should be on "/shared-space/members/"
    When I click link "Delete user"
    Then I should be on page matching "/shared-space/members/.+/delete"
    Then I click button "Delete"
    Then I should be on "/shared-space"
    And I see a success notification with content "Member deleted"
    When I click link "Shared LPAs"
    Then I should be on "/shared-space/dashboard"
    And there are "two" "LPA" elements on the page
    And I log out
    When I try to log in as the member added to the shared space
    Then I should be on "/login"
    Then I should not be logged in

  Scenario: Non-admin members cannot manage shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is a "member"
    And I log in as the member added to the shared space
    Then I should be on "/shared-space/dashboard"
    When I click element marked "Shared space"
    Then I should be on "/shared-space"
    And I see "View Shared Space" in the page text
    And I cannot see any links to manage members

  Scenario: Deleting the last member of a shared space deletes the shared space
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And I log in as the newly created fixture user
    Then I should be on "/shared-space/dashboard"
    When I click link "Your details"
    Then I should be on "/user/about-you"
    When I go to delete my account
    Then I should be on "/user/delete"
    And I am told what deleting my account as the last shared space member does
    When I confirm deleting my account
    Then I should be on "/deleted"

  @RequiresOneLogin
  Scenario: Deleting your account explains that the shared space keeps its LPAs
    Given I create a new user with 1 LPA that belongs to a shared space called "Example Organisation"
    And the shared space has a member called "Member 1" who is a "member"
    And I log in as the newly created fixture user
    When I click link "Your details"
    And I click link "Deleting your account - find out more"
    Then I should be on "/user/delete"
    And I see "The LPAs belong to the shared space Example Organisation." in the page text
    When I click link "Return to your details"
    Then I should be on "/user/about-you"
