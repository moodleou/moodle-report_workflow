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
 * Workflow report
 *
 * @package   report_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/generate_form.php');
require_once($CFG->dirroot . '/blocks/workflow/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// Retrieve the submitted parameters
$appliesto  = optional_param('appliesto', null, PARAM_ALPHA);

// Require login and capability
require_login();
require_capability('report/workflow:view', get_context_instance(CONTEXT_SYSTEM));

// This is a report workflow page
admin_externalpage_setup('reportworkflow');

// Display the header section
echo $OUTPUT->header();

$allappliesto = $DB->get_records_sql('SELECT DISTINCT(appliesto) FROM {block_workflow_workflows}');

// Display appliesto chooser if not set, or invalid appliesto specified
if (empty($appliesto) || !array_key_exists($appliesto, $allappliesto)) {
    if (count($allappliesto) > 0) {
        // Only list contexts which have workflows available
        $appliestolist = array_intersect_key(block_workflow_appliesto_list(), $allappliesto);

        // Retrieve the renderer
        $renderer = $PAGE->get_renderer('report_workflow');

        // If no appliesto has been set, then display the select dialogue
        echo $renderer->select_appliesto($appliestolist);
    }
    else {
        // No workflows are defined so no data to run reports on
        echo $OUTPUT->heading(get_string('nothingtodisplay'));
        echo $OUTPUT->box_start('generablbox boxwidthwide boxaligncenter');
        echo get_string('noworkflowsdefined', 'report_workflow');
        echo $OUTPUT->box_end();
    }

} else {
    // We've been passed the appliesto data

    // Create the form
    $reporturl = new moodle_url('/report/workflow/report.php');
    $form = new report_workflow_configure_form($reporturl, array('appliesto' => $appliesto), 'GET');

    // Set the form defaults
    $defaults = new stdClass();
    $defaults->appliesto = $appliesto;
    $form->set_data($defaults);

    echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
    echo $OUTPUT->heading(format_string(get_string('report_title', 'report_workflow')));
    // Display
    $form->display();
    echo $OUTPUT->box_end();
}

// Display the footer section
echo $OUTPUT->footer();
