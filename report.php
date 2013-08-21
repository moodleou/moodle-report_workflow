<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Workflow Report
 *
 * @package   report_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/generate_form.php');
require_once(dirname(__FILE__) . '/lib.php');

// What does the report apply to?
$appliesto  = required_param('appliesto', PARAM_ALPHA);

// Is the user trying to download this report?
$download   = optional_param('download', '', PARAM_ALPHA);

// Require that the user be logged in and have valid permissions.
require_login();
require_capability('report/workflow:view', get_context_instance(CONTEXT_SYSTEM));

// This is a report/workflow page.
admin_externalpage_setup('reportworkflow', '', array(), '', array('pagelayout' => 'report'));

// Instantiate the form
// We must provide the appliesto, otherwise the checkboxes aren't correctly returned.
$form = new report_workflow_configure_form(null, array('appliesto' => $appliesto), 'GET');

if ($form->is_cancelled()) {
    // The form was cancelled -- return to the index page.
    redirect(new moodle_url('/report/workflow/index.php'));

} else if ($data = $form->get_data()) {
    // The form was submitted and passed validation.


    // Set user preferences.
    set_user_preference('report_workflow_displaytype', $data->displaytype);
    set_user_preference('report_workflow_rowsperpage', $data->rowsperpage);

    // Generate the correct type of workflow_report_table for this type of workflow.
    $generator = report_workflow::load($data->appliesto);

    // Set the table generation options.
    $options = new stdClass();
    $options->detailed = ($data->displaytype == REPORT_WORKFLOW_DETAIL) ? REPORT_WORKFLOW_DETAIL : REPORT_WORKFLOW_BRIEF;

    // If we were provided with a valid regexp, we should pass it as an option.
    if (!empty($data->courseregexp)) {
        $options->courseregexp    = $data->courseregexp;
    }


    // Create an array of block_workflow_workflow objects as these will be needed for the table generation.
    $workflows = array();
    foreach ($data->workflow as $workflowid => $v) {
        if ($v) {
            // This workflow was selected so instantiate it and add it to the list.
            $workflows[] = new block_workflow_workflow($workflowid);
        }
    }

    if (empty($workflows)) {
        // FIXME: Really formslib should be able to validate that at least one workflow is selected.
        echo $OUTPUT->header();

        echo $OUTPUT->heading(format_string(get_string('report_title', 'report_workflow')));
        $generator->print_nothing_to_display();

        // Display the footer section.
        echo $OUTPUT->footer();
        exit;
    }

    // Initiate the table using the supplied options and workflows.
    $table = $generator->generate_table($options, $workflows);

    // Set the table base URL.
    $params = array(
        'appliesto' => $data->appliesto,
        'sesskey' => sesskey(),
        '_qf__report_workflow_configure_form' => 1,
        'courseregexp' => $data->courseregexp,
        'displaytype' => $data->displaytype,
        'rowsperpage' => $data->rowsperpage,
        'submitbutton' => 1,
    );
    foreach ($workflows as $wf) {
        $params['workflow[' . $wf->id . ']'] = 1;
    }
    $table->define_baseurl(new moodle_url('/report/workflow/report.php', $params));

    // Pass the form the download value -- is_downloading should handle this correctly if a download was requested.
    $table->is_downloading($download, 'workflow-report', get_string('report_title', 'report_workflow'));

    if (!$table->is_downloading()) {
        // Display the header section.
        echo $OUTPUT->header();

        echo $OUTPUT->heading(format_string(get_string('report_title', 'report_workflow')));

        // Actually generate the table.
        $table->out($data->rowsperpage, true);

        // Display the form to allow re-submission/editing.
        $form->display();

        // Display the footer section.
        echo $OUTPUT->footer();

    } else {
        // Actually generate the table.
        $table->out($data->rowsperpage, true);
    }

} else {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string(get_string('report_title', 'report_workflow')));
    $form->display();
    echo $OUTPUT->footer();
}
