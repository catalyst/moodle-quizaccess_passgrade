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
 * Implementaton of the quizaccess_passgrade plugin.
 *
 * @package   quizaccess_passgrade
 * @copyright 2013 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');


/**
 * A rule requiring the students have not achieved a pass grade
 */
class quizaccess_passgrade extends quiz_access_rule_base {


    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {

        if (empty($quizobj->get_quiz()->passgrade)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {

        $mform->addElement('text', 'passgrade', get_string("preventpassed", "quizaccess_passgrade"), 'maxlength="3" size="3"');
        $mform->setType('passgrade', PARAM_INT);
        $mform->addHelpButton('passgrade',
                'preventpassed', 'quizaccess_passgrade');
    }

    public static function save_settings($quiz) {
        global $DB;
        if (empty($quiz->passgrade)) {
            $DB->delete_records('quizaccess_passgrade', array('quizid' => $quiz->id));
        } else {
            if ($record = $DB->get_record('quizaccess_passgrade', array('quizid' => $quiz->id))) {
                $record->passgrade = $quiz->passgrade;
                $DB->update_record('quizaccess_passgrade', $record);
            } else {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->passgrade = $quiz->passgrade;
                $DB->insert_record('quizaccess_passgrade', $record);
            }
        }
    }

    public static function get_settings_sql($quizid) {
        return array(
            'passgrade',
            'LEFT JOIN {quizaccess_passgrade} passgrade ON passgrade.quizid = quiz.id',
            array());
    }

    public function prevent_new_attempt($numattempts, $lastattempt) {
        global $DB;

        if ($numattempts == 0) {
            return false;
        }

        //Check if preventonpass is set, and whether the student has passed the minimum passing grade
        $previousattempts = $DB->get_records_select('quiz_attempts',
                "quiz = :quizid AND userid = :userid AND timefinish > 0 and preview != 1",
                array('quizid' => $this->quiz->id, 'userid' => $lastattempt->userid));

        if (quiz_calculate_best_grade($this->quiz, $previousattempts) >= $this->quiz->passgrade) {
            return get_string('accessprevented', 'quizaccess_passgrade');
        }

        return false;
    }
}
