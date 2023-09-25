@block @block_modlib

Feature: Adding and configuring Content Creation blocks
  In order to have a Content Creation block on a page
  As admin
  I need to be able to insert and configure a Content Creation block

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname  | email                 |
      | teacher1 | Teacher    | 1         | teacher1@example.com  |
      | student1 | Student    | One       | student1@example.com  |
    And the following "categories" exist:
      | name                   | idnumber  |
      | Templates              | templates |
    And the following "courses" exist:
      | fullname          | category  | shortname | idnumber | format  | coursedisplay | numsections |
      | Course 1          |           | C1        | C1       | topics  | 0             | 5           |
      | Template Course 1 | templates | TC1       | TC1      | topics  | 0             | 3           |
    And the following "activities" exist:
      | activity   | name                | intro                         | course | idnumber    | section |
      | assign     | Test assignment 1   | Test assignment description   | TC1    | assign1     | 1       |
      | book       | Test book 1         | Test book description         | TC1    | book1       | 2       |
      | chat       | Test chat 1         | Test chat description         | TC1    | chat1       | 3       |
      | choice     | Test choice 1       | Test choice description       | TC1    | choice1     | 4       |
    And the following "course enrolments" exist:
      | user     | course | role            |
      | teacher1 | C1     | editingteacher  |
      | student1 | C1     | student         |
    And the following config values are set as admin:
      | config           | value       | plugin       |
      | templatecategory | 191000   | block_modlib |
      | defaulttemplate  | 190001   | block_modlib |

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: When installed the Content Creation (modlib) it can be seen
    When I add the "Module Library" block
    Then I should see "Module Library"
    And I should not see "No Template selected! Please click on the cogwheel to configure the block."
    And I should see "Select one or more templates or a whole category"
    And I should see "Book: Test book 1"
    And I should see "Chat: Test chat 1"
