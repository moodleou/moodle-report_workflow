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
 * Privacy subsystem implementation.
 *
 * @package report_workflow
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_workflow\privacy;

use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

/**
 * Privacy subsystem implementation.
 *
 * @copyright 2018 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference('report_workflow_displaytype',
            'privacy:metadata:preference:report_workflow_displaytype');
        $collection->add_user_preference('report_workflow_rowsperpage',
            'privacy:metadata:preference:report_workflow_rowsperpage');

        return $collection;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     * @throws \coding_exception
     */
    public static function export_user_preferences(int $userid) {
        // Export display type.
        $prefvalue = get_user_preferences('report_workflow_displaytype', null, $userid);
        if ($prefvalue !== null) {
            writer::export_user_preference('report_workflow', 'report_workflow_displaytype', $prefvalue,
                get_string('privacy:metadata:preference:report_workflow_displaytype', 'report_workflow'));
        }

        // Export rows per page.
        $prefvalue = get_user_preferences('report_workflow_rowsperpage', null, $userid);
        if ($prefvalue !== null) {
            writer::export_user_preference('report_workflow', 'report_workflow_rowsperpage', $prefvalue,
                get_string('privacy:metadata:preference:report_workflow_rowsperpage', 'report_workflow'));
        }
    }
}
