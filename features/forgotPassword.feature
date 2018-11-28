Feature: I need to be able to reset my password

    Scenario Outline: I can reset my password
        When I reset my password with my <property> "<value>"
        Then I should receive an email
        Examples:
            | property  | value                 |
            | username  | JohnDoe               |
            | email     | john.doe@example.com  |

    Scenario: I can't reset my password with an authorized field
        When I reset my password with my id "1"
        Then the request should be invalid with message 'The parameter "id" is not authorized in your configuration.'

    Scenario: I can reset my password even if I have already requested a token and this token has not expired yet
        Given I have a valid token
        When I reset my password
        Then I should receive an email

    Scenario: I can reset my password if I already request a token but it has expired
        Given I have an expired token
        When I reset my password
        Then I should receive an email

    Scenario: I can reset my password with an invalid email address, no error is thrown
        When I reset my password using invalid email address
        Then the response should be empty

    Scenario: I can't reset my password with no parameter specified
        When I reset my password using no parameter
        Then the request should be invalid with message 'No parameter sent.'

    Scenario: I can't update my password using an invalid token
        When I update my password using an invalid token
        Then the page should not be found

    Scenario: I can't update my password using an expired token
        When I update my password using an expired token
        Then the page should not be found

    Scenario: I can't update my password with no password specified
        When I update my password using no password
        Then the request should be invalid with message 'No parameter sent.'

    Scenario: I can get a password token
        When I get a password token
        Then I should get a password token

    Scenario: I can't get an expired password token
        When I get a password token using an expired token
        Then the page should not be found
