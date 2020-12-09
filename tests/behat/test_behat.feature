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
    And the following "courses" exist:
      | fullname          | shortname | format  | coursedisplay | numsections |
      | Course 1          | C1        | topics  | 0             | 5           |
      | Template Course 1 | TC1       | topics  | 0             | 3           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 1       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role            |
      | teacher1 | C1     | editingteacher  |
      | student1 | C1     | student         |
    And the following "block_modlib > plugin configurations" exist:
      | plugin       | name             | value |
      | block_modlib | templatecategory | 1     |
      | block_modlib | defaulttemplate  | 0     |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: When installed the Content Creation (modlib) it can be seen
    When I add the "Content Creation" block
    Then I should see "Content Creation"
    And I should see "No Template selected! Please click on the cogwheel to configure the block."

