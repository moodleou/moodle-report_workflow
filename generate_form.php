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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/lib.php');

class report_workflow_configure_form extends moodleform {
    protected $worflows;
    public function definition () {
        $mform      =  $this->_form;
        $appliesto  =  $this->_customdata['appliesto'];
        $this->workflows  =  block_workflow_workflow::available_workflows($appliesto);

        if ($appliesto == 'course') {
            $appliestostr = get_string('course');
        }
        else {
            $appliestostr = get_string('pluginname', 'mod_' . $appliesto);
        }

        $mform->addElement('static', 'appliestodesc', get_string('appliesto', 'report_workflow'),
                $appliestostr);
        // Add the list of checkbox
        foreach ($this->workflows as $workflow) {
            $mform->addElement('advcheckbox', 'workflow[' . $workflow->id . ']',
                    $workflow->name, null, array('group' => 1));
            $mform->setDefault('workflow[' . $workflow->id . ']', 1);
        }
        $this->add_checkbox_controller(1, null, null);

        $mform->addElement('text', 'courseregexp', get_string('courseregexp', 'report_workflow'));
        $mform->addHelpButton('courseregexp', 'courseregexp', 'report_workflow');
        $mform->setType('courseregexp', PARAM_TEXT);

        $displaytypeoptions = array(
            REPORT_WORKFLOW_DETAIL => get_string('full', 'report_workflow'),
            REPORT_WORKFLOW_BRIEF  => get_string('brief', 'report_workflow')
        );

        // Add the displaytype selection
        $mform->addElement('select', 'displaytype', get_string('displaytype', 'report_workflow'),
                $displaytypeoptions);
        $mform->setDefault('displaytype',
                get_user_preferences('report_workflow_displaytype', REPORT_WORKFLOW_DETAIL));

        // Display the number of rows per page
        $perpageoptions = array();
        for ($i = 10; $i <= 100; $i+=10) {
            $perpageoptions[$i] = $i;
        }
        $mform->addElement('select', 'rowsperpage', get_string('rowsperpage', 'report_workflow'),
                $perpageoptions);
        $mform->setDefault('rowsperpage',
                get_user_preferences('report_workflow_rowsperpage', 10));

        // Pass the appliesto as we'll need this again
        $mform->addElement('hidden', 'appliesto');
        $mform->setType('appliesto', PARAM_TEXT);

        // Finally add the submit button
        $this->add_action_buttons(false, get_string('generatereport', 'report_workflow'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $firstworkflowid = 0;
        $oneselected = false;
        foreach ($data['workflow'] as $wfid => $selected) {
            $oneselected = $oneselected || (int) $selected;
            if (!$firstworkflowid) {
                $firstworkflowid = $wfid;
            }
        }
        if (!$oneselected) {
            $errors['workflow[' . $firstworkflowid . ']'] =
                    get_string('mustselectaworkflow', 'report_workflow');
        }

        return $errors;
    }
}
