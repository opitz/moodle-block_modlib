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
    And I log in as "admin"
    And I create a course with:
      | Course full name | Template Course 1 |
      | Course short name | TC1 |
      | Number of announcements | 3 |
    And I am on "Template Course 1" course homepage with editing mode on
    And I add a "Database" to section "1" and I fill the form with:
      | Name | Template Database |
    Then I should see "Template Database"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
      | Number of announcements | 5 |
    And I enrol "Teacher 1" user as "Teacher"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Content Creation" block
    Then I should see "Content Creation"
    And I configure the "Content Creation" block
    And I set the following fields to these values:
      | id_config_template_course | Template Course 1 |
    And I press "Save changes"
    Then I should see "Template Database" in the "Content Creation" "block"
    And I select "Data: Template Database" from "template_selection"
    And I select "Topic 1" from the "target_topic_btn" singleselect
    And I turn editing mode off
    Then I should not see "Content Creation"
    And I should see "Template Database"
