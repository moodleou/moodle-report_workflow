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
 * Workflow report library
 *
 * @package   report_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/blocks/workflow/locallib.php');
require_once($CFG->libdir  . '/tablelib.php');

define('REPORT_WORKFLOW_BRIEF',     0);
define('REPORT_WORKFLOW_DETAIL',    1);


/**
 * Helper class used to load a course workflow
 *
 * @package   report_workflow
 * @copyright 2011 Lancaster University Network Services Limited
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_workflow {

    /**
     * Load the relevant workflow table type for the specified appiesto
     *
     * @param   string  $appliesto The type of module that this workflow applies to
     * @return  report_workflow_table_activity The appropraite generate_table_{$type} object
     * @throws  report_workflow_exception If no applies to is specified
     *
     */
    public static function load($appliesto) {
        if (empty($appliesto)) {
            // We must have an appliesto
            throw new report_workflow_exception(get_string('invalidappliesto', 'report_workflow'));
        }

        // All classes fit this basename pattern
        $basename = 'report_workflow_table_';

        // Try and load the course class
        if ($appliesto == 'course') {
            return new report_workflow_table_course();
        }

        // Everything else is an activity. See if there's a specific class
        // for that activity
        $activityclass = $basename . 'activity_';
        $classname = $activityclass . $appliesto;
        if (class_exists($classname)) {
            return new $classname();
        }

        // Fall back to the activity class
        return new report_workflow_table_activity($appliesto);
    }
}


/**
 * Class used to generate a reporting table
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    report
 * @subpackage workflow
 */
class report_workflow_table               extends table_sql {

    /**
     * @var array   sqldata         The sql query we build up before parsing it and filling
     *                              the parent's $sql variable
     */
    protected $sqldata;

    /**
     * @var integer $maxsteps       The largest number of steps in one of the queried workflows
     */
    protected $maxsteps;

    /**
     * @var array   $openingcolumns An associative array of columns format: array('columnanme' => $columntitle)
     */
    protected $openingcolumns =  array();

    /**
     * @var array $stepcolumns An associative array of columns format: array('columnanme' => $columntitle)
     */
    protected $stepcolumns   =  array();

    /**
     * @var array $closingcolumns An associative array of columns format: array('columnanme' => $columntitle)
     */
    protected $closingcolumns =  array();

    /**
     * @var integer $detailed       Whether this report should be a detailed view
     */
    protected $detailed;

    /**
     * Create the report_workflow_table, instantiating the parent table_sql class correctly
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->closingcolumns = array('workflowname' => get_string('report_workflowname', 'report_workflow'));
    }

    /**
     * Generate the table given the specified data
     *
     * @param   stdClass $options   A list of options for creating the table
     * @param   Array    $workflows The list of workflows to generate data for
     * @return  table_sql           The instantiated report_workflow_table
     */
    public function generate_table($options, $workflows) {

        /**
         * Take the options and store/process them as appropriate
         */
        // Whether this view is detailed or brief
        $this->detailed     = $options->detailed;


        /**
         * Generate definitions for all of the columns.
         * These are broken down into three groups:
         * - Opening columns
         * - Step Columns
         * - Closing columns
         *
         */

        $columnnames = array();
        $headernames = array();
        $nosorting   = array();

        // Add the opening columns and their headers
        foreach ($this->openingcolumns as $columnname => $columnheader){
            $columnnames[] = $columnname;
            $headernames[] = $columnheader;
        }

        // Generate the step columns and their headers
        foreach ($this->get_step_columns($workflows)  as $columnname => $columnheader){
            $columnnames[] = $columnname;
            $headernames[] = $columnheader;
            $this->no_sorting($columnname);
        }

        // Add the closing columns and their headers
        foreach ($this->closingcolumns  as $columnname => $columnheader){
            $columnnames[] = $columnname;
            $headernames[] = $columnheader;
        }

        // These two function calls are provided by the flexible_table parent class
        $this->define_columns($columnnames);
        $this->define_headers($headernames);

        /**
         * Set the SQL query
         *
         * We build this by first generating a query,
         * and then converting these from our arrays into the object expected by query_db
         */
        $this->generate_query($workflows);
        $this->generate_steps_sql();

        // Whether any regular expression has been set for this query
        if (isset($options->courseregexp)) {
            $this->generate_course_conditions($options->courseregexp);
        }
        $this->generate_sql();

        // Return an object to this object so that the report generator can
        // control the table some more before calling out on it.
        return $this;
    }

    /**
     * Generate any step columns required for this type of workflow
     *
     * @return  array  An associative array of columns format: array('columnanme' => $columntitle)
     */
    protected function get_step_columns($workflows) {
        // Check each workflow for the number of steps
        $maxsteps = 0;
        foreach ($workflows as $w) {
            $sc = count($w->steps());
            if ($sc > $maxsteps) {
                $maxsteps = $sc;

                // The generate_steps_sql() column requires the $this->maxsteps
                $this->maxsteps = $maxsteps;
            }
        }

        // Always use Step 1, Step 2, etc. for the column headings.
        $c = array();
        for ($i = 1; $i <= $maxsteps; ++$i) {
            $c['stepno_'.$i.'_data'] = get_string('stepno_', 'report_workflow', $i);
        }
        return $c;
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required for this
     * workflow report.
     *
     * This function fills $this->sqldata with a set of fields, from, where and params. Each of
     * these is an array with a list of the appropriate SQL components.
     *
     * It is advisable if redefining this query to call parent::generate_query($workflows) as the
     * earliest opportunity and extend the data fields it provides, rather than writing the SQL from
     * scratch.
     *
     * Please note that the actual SQL is generated by {@link self::generate_sql} and *not* this
     * function
     *
     * @param   Array    $workflows The list of workflows to generate data for
     * @return  void
     */
    protected function generate_query($workflows) {
        $this->sqldata = new stdClass();

        // Define the default fields
        $this->sqldata->fields[] = 'c.id                AS contextid';
        $this->sqldata->fields[] = 'co.shortname        AS courseshortname';
        $this->sqldata->fields[] = 'co.fullname         AS coursename';
        $this->sqldata->fields[] = 'w.name              AS workflowname';
        $this->sqldata->fields[] = 'w.id                AS workflowid';
        $this->sqldata->fields[] = 's1.name             AS stepno_1_name';
        $this->sqldata->fields[] = 'ss1.state           AS stepno_1_state';
        $this->sqldata->fields[] = 'ss1.comment         AS stepno_1_comment';
        $this->sqldata->fields[] = 'ss1.commentformat   AS stepno_1_commentformat';
        $this->sqldata->fields[] = 'ss1.timemodified    AS stepno_1_timemodified';

        $this->sqldata->from[]   = '{block_workflow_workflows} AS w';
        $this->sqldata->from[]   = 'INNER JOIN {block_workflow_steps}       AS s1   ON s1.workflowid = w.id';
        $this->sqldata->from[]   = 'INNER JOIN {block_workflow_step_states} AS ss1  ON ss1.stepid   = s1.id';
        $this->sqldata->from[]   = 'LEFT  JOIN {context}                    AS c    ON c.id         = ss1.contextid';
        $this->sqldata->where[]  = 's1.stepno = 1';

        // Add the list of workflows
        foreach ($workflows as $workflow) {
            $this->sqldata->workflows[] = 'w.shortname = ?';
            $this->sqldata->params[] = $workflow->shortname;
        }

        $this->sqldata->where[]  = 'AND (' . implode(' OR ', $this->sqldata->workflows) . ')';
    }

    /**
     * Add where clauses to the instance based on a simplified regular expression syntax
     * passed from the user. This allows course searching such as CS10? or CS* to 
     * match CF101 course shortname
     *
     * @var string  $courseregexp   The simplified regular expression used to search for these courses
     *
     * @return  void
     */
    private function generate_course_conditions($courseregexp){
        global $DB;

        $shortnamecondition = '';
        $shortnamevalues = array();
        $splitshortname = preg_split('~[ ,]+~', $courseregexp, null, PREG_SPLIT_NO_EMPTY);
        if (count($splitshortname) > 0) {
            $first = true;
            foreach ($splitshortname as $option) {
                // convert to sql syntax
                $option = str_replace('*', '%', $option);
                $option = str_replace('?', '_', $option);

                // If someone types in multiple * signs, turn them into one
                $option = preg_replace('~%+~', '%', $option);

                // If the whole thing is * then ignore
                if ($option == '*') {
                    next;
                }

                if ($first) {
                    $shortnamecondition.= 'AND (';
                    $first = false;
                } else {
                    $shortnamecondition .= ' OR ';
                }

                $shortnamecondition .= $DB->sql_like('co.shortname', '?', false);
                $shortnamevalues[] = $option;
            }

            if($first){
                // we didn't get any valid regexps
                return;
            }

            $shortnamecondition .= ')';

            $this->sqldata->where[] = $shortnamecondition;
            $this->sqldata->params = array_merge($this->sqldata->params, $shortnamevalues);
        }
    }

    /**
     * Generate the contents of $this->sql as required by the query_db function
     *
     * This will take each of the $this->sqldata fields defined by {@link generate_query} and
     * convert them to the format required for the parent table_sql::query_db function.
     *
     * @return  void
     */
    protected function generate_sql() {
        $this->sql->fields  = implode(', ', $this->sqldata->fields);
        $this->sql->from    = implode(' ',  $this->sqldata->from);
        $this->sql->where   = implode(' ', $this->sqldata->where);
        $this->sql->params  = $this->sqldata->params;
        $this->countparams  = $this->sql->params;
    }

    /**
     * Generate the SQL data format for each of the steps in the workflow(s)
     *
     * This function fills $this->sqldata with a set of fields, from, where and params. Each of
     * these is an array with a list of the appropriate SQL components.
     *
     * It is *not* advisable to extend this function -- it should not be necessary
     */
    protected function generate_steps_sql() {
        $stepno = 2;
        while ($stepno <= $this->maxsteps) {
            $ssname = 'ss' . $stepno;
            $sname  = 's'  . $stepno;
            $this->sqldata->fields[]    = $sname  . '.name            AS stepno_' . $stepno . '_name';
            $this->sqldata->fields[]    = $ssname . '.state           AS stepno_' . $stepno . '_state';
            $this->sqldata->fields[]    = $ssname . '.comment         AS stepno_' . $stepno . '_comment';
            $this->sqldata->fields[]    = $ssname . '.commentformat   AS stepno_' . $stepno . '_commentformat';
            $this->sqldata->fields[]    = $ssname . '.timemodified    AS stepno_' . $stepno . '_timemodified';
            $this->sqldata->from[]      = 'LEFT JOIN {block_workflow_steps}         AS ' . $sname  . ' ON ' . $sname  . '.stepno = ' . $stepno . ' AND ' . $sname . '.workflowid = w.id';
            $this->sqldata->from[]      = 'LEFT JOIN {block_workflow_step_states}   AS ' . $ssname . ' ON ' . $ssname . '.contextid = c.id AND ' . $ssname . '.stepid = ' . $sname . '.id';
            $stepno++;
        }
    }

    /**
     * Return an array of formatted cells to displaying in the report
     *
     * If the column is a stepno_x_data field, then it is passed to col_step
     * Other fields are handled in the standard flexible_table manner
     *
     * To add a custom field, it should be possible to simply define a function by the name
     * col_[columnname] which takes the $row as it's only input and return the formatted text.
     *
     * @param   array $row  The row being processed
     * @return  array       One row for the table
     */
    function format_row($row){
        $formattedrow = array();
        foreach (array_keys($this->columns) as $column){
            $colmethodname = 'col_' . $column;

            if (preg_match('/stepno_([0-9]+)_data$/', $column, $matches)) {
                // If this field is the data field for the step call the column step settings
                $formattedcolumn = $this->col_step($row, $matches[1]);
            }
            else if (method_exists($this, $colmethodname)){
                $formattedcolumn = $this->$colmethodname($row);
            }
            else {
                $formattedcolumn = $this->other_cols($column, $row);
                if ($formattedcolumn===NULL){
                    $formattedcolumn = $row->$column;
                }
            }
            $formattedrow[$column] = $formattedcolumn;
        }
        return $formattedrow;
    }

    /**
     * Return the formatted text for a step field
     *
     * @param   stdClass    $row    The table row
     * @param   integer     $stepno The step number being processed
     * @return  string              The formatted text
     */
    public function col_step($row, $stepno) {
        // The field names stored in the database
        $stepname       = 'stepno_' . $stepno . '_name';
        $state          = 'stepno_' . $stepno . '_state';
        $comment        = 'stepno_' . $stepno . '_comment';
        $commentformat  = 'stepno_' . $stepno . '_commentformat';
        $timemodified   = 'stepno_' . $stepno . '_timemodified';

        // The string we'll be returning later
        $output = '';

        // First check for detailed/brief
        if ($this->detailed == REPORT_WORKFLOW_DETAIL) {

            $seperator = ': ';
            if (!$this->is_downloading()) {
                $seperator = html_writer::empty_tag('br');
            }

            // Report the step state (written in full)
            // and the timemodified for that state
            if ($stepstate = $row->$state) {
                $text  = get_string($stepstate, 'report_workflow');
                $text .= $seperator;
                $text .= userdate($row->$timemodified, get_string('strftimedate', 'langconfig'));
            }
            else {
                $text = get_string('notstarted', 'report_workflow');
            }

            if (!$this->is_downloading()) {
                $tooltip = $this->cell_tooltip($row->$stepname);
                $output  = html_writer::tag('div', $text, array('title' => $tooltip));
            }
            else {
                $output = $text;
            }
        }
        else {
            // The tooltip
            $tooltip = $this->cell_tooltip($row->$stepname, $row->$state, $row->$timemodified);
            // The cell text
            switch ($row->$state) {
                case (BLOCK_WORKFLOW_STATE_ACTIVE):
                    $text = get_string('brief_active',      'report_workflow');
                    break;
                case (BLOCK_WORKFLOW_STATE_COMPLETED):
                    $text = get_string('brief_completed',   'report_workflow');
                    break;
                case (BLOCK_WORKFLOW_STATE_ABORTED):
                    $text = get_string('brief_aborted',     'report_workflow');
                    break;
                default:
                    $text = get_string('brief_notstarted',  'report_workflow');
                    $tooltip = $this->cell_tooltip($row->$stepname, 'notstarted');
                    break;
            }
            if (!$this->is_downloading()) {
                $output  = html_writer::tag('div', $text, array('title' => $tooltip));
            }
            else {
                $output = $text;
            }
        }
        return $output;
    }

    /**
     * Wrapper to create and format the string step
     *
     * @param   string  $stepname   The name of the step
     * @param   string  $stepstate  The current step state
     * @param   ingeger $stepdate   The moodledate that the step was last modifed
     */
    public function cell_tooltip($stepname, $stepstate = null, $stepdate = null) {
        $tooltip = get_string('stepname', 'report_workflow') . $stepname;
        if ($stepstate) {
            $tooltip .= '&#13;' . get_string('stepstate', 'report_workflow') . get_string($stepstate, 'report_workflow');
        }
        if ($stepdate) {
            $tooltip .= '&#13;' . get_string('lastmodified', 'report_workflow') . userdate($stepdate, get_string('strftimedate', 'langconfig'));
        }
        return $tooltip;
    }

}


/**
 * Class used to generate a reporting table for a course
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    report
 * @subpackage workflow
 */
class report_workflow_table_course        extends report_workflow_table {

    public function __construct() {
        parent::__construct('block-workflow-report-overview-course');
        $this->openingcolumns['courseshortname']   = get_string('course');
        $this->closingcolumns['categoryname'] = get_string('report_categoryname', 'report_workflow');
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required for a course
     * report.
     *
     * Please see full documentation as provided by {@link report_workflow_table::generate_query}.
     *
     * This extension links the course and course_categories tables and selects the categoryname
     * field
     *
     * @param   Array    $workflows The list of workflows to generate data for
     * @return  void
     */
    protected function generate_query($workflows) {
        parent::generate_query($workflows);
        $this->sqldata->fields[] = 'cc.name             AS categoryname';
        $this->sqldata->from[]   = 'LEFT  JOIN {course}                     AS co ON co.id        = c.instanceid';
        $this->sqldata->from[]   = 'LEFT  JOIN {course_categories}          AS cc ON cc.id        = co.category';
    }

    /**
     * Format the coursename column to add link to workflow overview if not 
     * downloading
     *
     * @param   string  The field contents
     */
    protected function col_courseshortname($row){

        if ($this->is_downloading()) {
            return $row->courseshortname;
        }

        $url = new moodle_url('/blocks/workflow/overview.php',
                               array('contextid'  => $row->contextid,
                                     'workflowid' => $row->workflowid
                              ));
        return html_writer::link($url, $row->courseshortname);
    }
}


/**
 * Class used to generate a reporting table for an activity
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    report
 * @subpackage workflow
 */
class report_workflow_table_activity      extends report_workflow_table {
    protected $appliesto;

    public function __construct($appliesto, $uniqueid = 'block-workflow-report-overview-activity') {
        parent::__construct($uniqueid);
        $this->appliesto = $appliesto;
        $this->openingcolumns['courseshortname'] = get_string('course');
        $this->openingcolumns['activityname'] = get_string('report_activityname', 'report_workflow');
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required for an
     * activity report.
     *
     * Please see full documentation as provided by {@link report_workflow_table::generate_query}.
     *
     * This extension links the course and course_categories, modules, and course_modules tables and
     * selects the categoryname, and activityname fields
     *
     * @param   Array    $workflows The list of workflows to generate data for
     * @return  void
     */
    protected function generate_query($workflows) {
        global $DB;

        parent::generate_query($workflows);

        $modid = $DB->get_field('modules', 'id', array('name' => $this->appliesto));

        $this->sqldata->fields[] = 'cc.name             AS categoryname';
        $this->sqldata->fields[] = "{$this->appliesto}.name                 AS activityname";
        $this->sqldata->from[]   = 'LEFT  JOIN {course_modules}             AS cm ON cm.id        = c.instanceid AND cm.module = ' . $modid;
        $this->sqldata->from[]   = "LEFT  JOIN {{$this->appliesto}}         AS {$this->appliesto} ON {$this->appliesto}.id = cm.instance";
        $this->sqldata->from[]   = 'LEFT  JOIN {course}                     AS co ON co.id        = cm.course';
        $this->sqldata->from[]   = 'LEFT  JOIN {course_categories}          AS cc ON cc.id        = co.category';
    }

    /**
     * Format the activity name column to add link to workflow overview
     *
     * @param   string  The field contents
     */
    protected function col_activityname($row){

        if ($this->is_downloading()) {
            return $row->activityname;
        }

        $url = new moodle_url('/blocks/workflow/overview.php',
                               array('contextid'  => $row->contextid,
                                     'workflowid' => $row->workflowid
                              ));
        return html_writer::link($url, $row->activityname);
    }
}


/**
 * Class used to generate a reporting table for a quiz activity
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    report
 * @subpackage workflow
 */
class report_workflow_table_activity_quiz extends report_workflow_table_activity {

    public function __construct() {
        parent::__construct('quiz', 'block-workflow-report-overview-quiz');

        // Two weeks before opening date
        $this->closingcolumns['2wbod'] = get_string('report_2wbod',     'report_workflow');
        $this->no_sorting('2wbod');

        // Quiz Opening date
        $this->closingcolumns['timeopen'] =  get_string('report_opendate',  'report_workflow');

        // Quiz Closing date
        $this->closingcolumns['timeclose'] = get_string('report_closedate', 'report_workflow');
    }

    /**
     * Generate the intermediate SQL data structure to retrieve the information required for a
     * quiz report.
     *
     * Please see full documentation as provided by {@link report_workflow_table::generate_query}.
     *
     * This extension links the quiz table, and selects the timeopen, and timeclose fields.
     *
     * @param   Array    $workflows The list of workflows to generate data for
     * @return  void
     */
    protected function generate_query($workflows) {
        parent::generate_query($workflows);
        $this->sqldata->fields[] = 'quiz.timeopen          AS timeopen';
        $this->sqldata->fields[] = 'quiz.timeclose         AS timeclose';
    }

    /**
     * Add the column for Two Weeks before Open Date
     *
     * @param   string  The field contents
     */
    protected function col_2wbod($row) {
        $output  = '';
        if ($row->timeopen) {
            $output = userdate(strtotime('-2 weeks', $row->timeopen), get_string('strftimedatetimeshort', 'langconfig'));
        }
        return $output;
    }

    /**
     * Add the column for Quiz open date
     *
     * @param   string  The field contents
     */
    protected function col_timeopen($row) {
        $output  = '';
        if ($row->timeopen) {
            $output = userdate($row->timeopen, get_string('strftimedatetimeshort', 'langconfig'));
        }
        return $output;
    }

    /**
     * Add the column for Quiz close date
     *
     * @param   string  The field contents
     */
    protected function col_timeclose($row) {
        $output  = '';
        if ($row->timeclose) {
            $output = userdate($row->timeclose, get_string('strftimedatetimeshort', 'langconfig'));
        }
        return $output;
    }
}


/**
 * The report_workflow_exception class which extends the moodle_exception.
 *
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    report
 * @subpackage workflow
 */
class report_workflow_exception extends moodle_exception {
}
