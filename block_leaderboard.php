<?php
/**
 * Created by PhpStorm.
 * User: erikfox
 * Date: 5/22/18
 * Time: 11:15 PM
 */

class block_leaderboard extends block_base {

    public function init() {
        $this->title = get_string('leaderboard', 'block_leaderboard');
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.

    function has_config() {
        return true;
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_leaderboard');
        $this->content->text = $renderer->leaderboard_block($this->page->course);
        
        //updates points values for assignments retrospectively
        global $DB;
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
                    $assignment_table = $DB->get_record('assignment_table', array('activity_student'=> $student->id, 'activity_id' => $key));
                    if($assignment_table != false){
                        $days_early = ($due_date - $assignment_table->time_finished)/86400;
                        if($assignment_table->points_earned == 0 && $days_early > 0){
                            $points = 0;
                            for($x=1; $x<=5; $x++){
                                $current_time = get_config('leaderboard','assignmenttime'.$x);
                                if($x < 5) {
                                    $next_time = get_config('leaderboard','assignmenttime'.($x+1));
                                    if($days_early >= $current_time && $days_early < $next_time){
                                        $points = get_config('leaderboard','assignmnetpoints'.$x);
                                        break;
                                    }
                                } else {
                                    if($days_early >= $current_time){
                                        $points = get_config('leaderboard','assignmnetpoints'.$x);
                                        break;
                                    }
                                }
                            }
                            $assignment_table->points_earned = block_leaderboard_multiplier::calculate_points($student->id, $points);
                            $DB->update_record('assignment_table', $assignment_table);
                        }
                    }
                }
            }
        }
        return $this->content;
    }

    public function hide_header() {
        return true;
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_leaderboard'; // Append our class to class attribute
        return $attributes;
    }
}