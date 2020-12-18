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
 * Data provider tests for report workflow plugin.
 *
 * @package    report_workflow
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use report_workflow\privacy\provider;

/**
 * Data provider testcase class.
 *
 * @package    report_workflow
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_workflow_privacy_testcase extends provider_testcase {

    /**
     * Test export user preferences when no value is set.
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences_not_defined() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        provider::export_user_preferences($user->id);
        $writer = writer::with_context(context_user::instance($user->id));
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * Test export user preferences.
     *
     * @throws coding_exception
     */
    public function test_export_user_preferences() {
        $this->resetAfterTest();

        // Define user preferences.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        set_user_preference('report_workflow_displaytype', 1);
        set_user_preference('report_workflow_rowsperpage', 50);

        // Validate exported data.
        provider::export_user_preferences($user->id);
        $context = \context_user::instance($user->id);
        $writer = writer::with_context($context);
        $prefs = $writer->get_user_preferences('report_workflow');

        $this->assertCount(2, (array)$prefs);
        $this->assertEquals((object)[
            'value' => 1,
            'description' => get_string('privacy:metadata:preference:report_workflow_displaytype', 'report_workflow')
        ], $prefs->report_workflow_displaytype);
        $this->assertEquals((object)[
            'value' => 50,
            'description' => get_string('privacy:metadata:preference:report_workflow_rowsperpage', 'report_workflow')
        ], $prefs->report_workflow_rowsperpage);
    }
}
