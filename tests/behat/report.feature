@ou @ou_vle @report @report_workflow
Feature: Workflow report
  In order to manage the creation of many courses
  as a manager
  I need see a report of the workflow on many courses.

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And I log in as "admin"
    And I navigate to "Manage editors" node in "Site administration > Plugins > Text editors"
    And I click on "disable" "link" in the "Atto HTML editor" "table_row"

    # Import the sample workflow twice, so we have two workflows.
    And I navigate to "Workflows" node in "Site administration > Plugins > Blocks"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"
    And I follow "Workflows"
    And I follow "Import workflow"
    And I upload "blocks/workflow/tests/fixtures/testworkflow.workflow.xml" file to "File" filemanager
    And I press "Import workflow"

    # Add the workflow to both courses, in one case moving to step 2.
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add the "Workflows" block
    And I set the field "workflow" to "Test course workflow"

    And I am on "Course 2" course homepage
    And I add the "Workflows" block
    And I set the field "workflow" to "Test course workflow1"
    And I press "Finish step"
    And I click on "#id_submitbutton" "css_element"

  @javascript
  Scenario: Run the report
    When I navigate to "Workflows" node in "Site administration > Reports"
    And I set the field "Applies to" to "Course"
    And I set the field "Test course workflow" to "1"
    And I set the field "Test course workflow1" to "1"
    And I press "Generate the report"

    Then I should see "Workflow status report"
    And I should see "Test course workflow" in the "C1" "table_row"
    And I should see "Active" in the "C1" "table_row"
    And I should see "Not started" in the "C1" "table_row"
    And I should see "Test course workflow1" in the "C2" "table_row"
    And I should see "Complete" in the "C2" "table_row"
    And I should see "Active" in the "C2" "table_row"

    When I set the field "Display" to "Brief view"
    And I press "Generate the report"
    Then I should see "Test course workflow" in the "C1" "table_row"
    And I should see "A" in the "C1" "table_row"
    And I should see "Test course workflow1" in the "C2" "table_row"
    And I should see "C" in the "C2" "table_row"
    And I should see "A" in the "C2" "table_row"

    When I set the field "Test course workflow1" to "0"
    And I press "Generate the report"
    Then I should not see "C2"
