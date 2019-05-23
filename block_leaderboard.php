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
 * Base file for leaderboard block.
 *
 * @package    blocks_leaderboard
 * @copyright  2019 Erik Fox
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class block_leaderboard extends block_base {

    /**
     * Init.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('leaderboard', 'block_leaderboard');
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.

    /**
     * The plugin has a settings.php file.
     *
     * @return boolean True.
     */
    public function has_config() {
        return true;
    }

    /**
     * Get content.
     *
     * @return stdClass
     */
    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_leaderboard');
        $this->content->text = $renderer->leaderboard_block($this->page->course);

        /*
        // If the point values for assignments or quizzes get lost somehow, uncomment this code.
        $this->fix_assignments();
        $this->fix_quizzes();
        */

        return $this->content;
    }

    /**
     * Hide the header.
     *
     * @return boolean True
     */
    public function hide_header() {
        return true;
    }

    /**
     * Add special html attributes.
     *
     * @return stdClass
     */
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values.
        $attributes['class'] .= ' block_leaderboard'; // Append our class to class attribute.
        return $attributes;
    }

    /**
     * Updates points values for assignments retrospectively.
     *
     * @return void
     */
    public function fix_assignments() {
        /*
        Uncomment this if some assignments were not recorded for any reason.
        This was initially used because the first few assignments did not have
        due dates so no points were recorded.
        */

        /*global $DB;
        $groups = $DB->get_records('groups');
        $all_assignments = $DB->get_records('assign');
        foreach($groups as $group){
            //get each member of the group
            $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
            foreach($students as $student){
                $submission_data = $DB->get_records('assign_submission',array('userid'=> $student->id));
                $keys = array_keys($submission_data);
                foreach($keys as $key){
                    $assignment_data_key = $submission_data[$key]->assignment;
                    $assignment_data = $all_assignments[$assignment_data_key];
                    $due_date = $assignment_data->duedate;
                    $assignment_table = $DB->get_record('assignment_table',
                                            array('studentid'=> $student->id, 'activityid' => $key));
                    if($assignment_table != false){
                        $daysearly = ($due_date - $assignment_table->timefinished)/86400;
                        if($assignment_table->pointsearned == 0 && $daysearly > 0 ||
                            $assignment_table->daysearly != $daysearly){
                            $points = 0;
                            for($x=1; $x<=5; $x++){
                                $current_time = get_config('leaderboard','assignmenttime'.$x);
                                if($x < 5) {
                                    $next_time = get_config('leaderboard','assignmenttime'.($x+1));
                                    if($daysearly >= $current_time && $daysearly < $next_time){
                                        $points = get_config('leaderboard','assignmentpoints'.$x);
                                        break;
                                    }
                                }
                                else {
                                    if($daysearly >= $current_time){
                                        $points = get_config('leaderboard','assignmentpoints'.$x);
                                        break;
                                    }
                                }
                            }
                            $assignment_table->pointsearned = $points;
                            $assignment_table->daysearly = $daysearly;
                            $DB->update_record('assignment_table', $assignment_table);
                        }
                    }
                }
            }
        }*/
    }

    /**
     * Updates points values for quizzes retrospectively.
     *
     * @return void
     */
    public function fix_quizzes() {
        /*global $DB;
        $groups = $DB->get_records('groups');
        $all_assignments = $DB->get_records('assign');
        foreach($groups as $group){
            //get each member of the group
            $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
            foreach($students as $student){
                $past_quizzes = $DB->get_records('quiz_table',array('student_id'=> $student->id), $sort='timestarted ASC');
                $clean_quizzes = [];
                foreach($past_quizzes as $past_quiz){
                    if ($past_quiz->timefinished != null){
                        $clean_quizzes[] = $past_quiz;
                    }
                }
                echo("<script>console.log('EVENT1: ".json_encode($clean_quizzes)."');</script>");
                $previous_time = 0;
                foreach($clean_quizzes as $quiz){
                    $days_before_submission = $quiz->daysearly;
                    $pointsearned = 0;
                    if(abs($days_before_submission) < 50){ //quizzes without duedates will produce a value like -17788
                        $quiz->daysearly = $days_before_submission;
                        for($x=1; $x<=5; $x++){
                            $current_time = get_config('leaderboard','quiztime'.$x);
                            if($x < 5) {
                                $next_time = get_config('leaderboard','quiztime'.($x+1));
                                if($days_before_submission >= $current_time && $days_before_submission < $next_time){
                                    $pointsearned = get_config('leaderboard','quizpoints'.$x);
                                }
                            }
                            else {
                                if($days_before_submission >= $current_time){
                                    $pointsearned = get_config('leaderboard','quizpoints'.$x);
                                }
                            }
                        }
                    }
                    else {
                        $quiz->daysearly = 0;
                        $pointsearned = 0;
                    }

                    $quiz->pointsearned = $pointsearned;

                    $spacing_points = 0;
                    //echo("<script>console.log('EVENT1: ".$quiz->daysspaced."');</script>");
                    $quizspacing = ($quiz->timestarted - $previous_time)/(float)86400;

                    //make sure that days spaced doesn't go above a maximum of 5 days
                    $quiz->daysspaced = min($quizspacing, 5);
                    //echo("<script>console.log('EVENT1: ".$quiz."');</script>");

                    for($x=1; $x<=3; $x++){
                        $current_spacing = get_config('leaderboard','quizspacing'.$x);
                        if($x < 3) {
                            $next_spacing = get_config('leaderboard','quizspacing'.($x+1));
                            if($quizspacing >= $current_spacing && $quizspacing < $next_spacing){
                                $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                                break;
                            }
                        }
                        else {
                            if($current_spacing <= $quizspacing){
                                $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                            }
                        }
                    }
                    $previous_time = $quiz->timestarted;
                    $quiz->pointsearned += $spacing_points;

                    $multiple_attempt_points = 0;
                    $points = 0;
                    $quizattempts = get_config('leaderboard','quizattempts');

                    $multiple_attempt_points = get_config('leaderboard','quizattemptspoints');

                    for($i=0; $i<$quiz->attempts;$i++){
                        $points += $multiple_attempt_points;
                    }
                    $quiz->pointsearned += $multiple_attempt_points;
                    //$quiz->pointsearned = 0;
                    //$quiz->daysspaced = 0;
                    $DB->update_record('quiz_table', $quiz);
                }
            }
        }*/
    }
}
