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
 * Workflow block libraries
 *
 * @package   report_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A renderer class for the report_workflow plugin
 *
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class report_workflow_renderer extends plugin_renderer_base {

    /**
     * Return an HTML Select with a list of the options that a workflow may
     * apply to
     *
     * @param   array   $options The list of options to display in the select
     *
     */
    public function select_appliesto(array $options) {
        $output = '';

        // Display some information about the select.
        $output .= $this->box_start('generalbox boxwidthwide boxaligncenter centerpara', 'appliestoform');
        $output .= $this->heading(get_string('reportsettings', 'report_workflow'));
        $output .= html_writer::tag('p', get_string('report_intro', 'report_workflow'), array('id' => 'intro'));

        // The URL we return to.
        $url = new moodle_url('/report/workflow/index.php');

        // Create the list of available workflows.
        foreach ($options as $shortname => $name) {
            $option[$shortname] = $name;
        }

        // Create the single_select.
        $select = new single_select($url, 'appliesto', $option);

        // Give it a label and change it to post the data.
        $select->set_label(get_string('appliesto', 'report_workflow'));

        // Render it.
        $output .= $this->render($select);

        // Close the box we started earlier.
        $output .= $this->box_end();

        // Return the generated output.
        return $output;
    }
}
