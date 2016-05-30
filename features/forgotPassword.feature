Feature: I need to be able to reset my password

    @debug
    Scenario: I can reset my password
        When I reset my password
        Then I should receive an email

    Scenario: I can't reset my password if I'm authenticated
        Given I am authenticated
        When I reset my password
        Then I should be forbidden

    Scenario: I can't reset my password with an invalid email address
        When I reset my password using invalid email address
        Then the request should be invalid

    Scenario: I can't update my password if I'm authenticated
        Given I am authenticated
        When I update my password
        Then I should be forbidden

    Scenario: I can update my password using a valid token
        When I update my password
        Then I can log in
        And I should see my profile

    Scenario: I can't update my password using an invalid token
        When I update my password using an invalid token
        Then the page should not be found

    Scenario: I can't update my password using an expired token
        When I update my password using an expired token
        Then the page should not be found
