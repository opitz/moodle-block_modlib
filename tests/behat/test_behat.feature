@block @block_modlib

Feature: Adding and configuring Content Creation blocks
  In order to have a Content Creation block on a page
  As admin
  I need to be able to insert and configure a Content Creation block

  @javascript
  Scenario: Latest course announcements are displayed and can be configured
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname          | shortname | format  | coursedisplay | numsections |
      | Course 1          | C1        | topics  | 0             | 5           |
      | Template Course 1 | TC1       | topics  | 0             | 3           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: When installed the Content Creation (modlib) it can be seen
    When I add the "Content Creation" block
    Then I should see "Content Creation"

