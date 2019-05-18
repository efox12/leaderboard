<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 12/29/18
 */

require_once '../../config.php';
require_once "$CFG->libdir/formslib.php";

class simplehtml_form extends moodleform {
 
    function definition() {
        $mform =& $this->_form; // Don't forget the underscore! 
        echo("<script>console.log('EVENT1: ".json_encode($this->_customdata)."');</script>");

        $mform->addElement('header', 'h', "Change Date Range");
        // parameters required for the page to load
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'start');
        $mform->setType('start', PARAM_RAW);
        $mform->addElement('hidden', 'end');
        $mform->setType('end', PARAM_RAW);

        $mform->addElement('date_selector', 'startDate', "Start");
        $mform->setDefault('startDate',$this->_customdata['startDate']);
        $mform->addElement('date_selector', 'endDate', "End");        
        $mform->setDefault('endDate',$this->_customdata['endDate']);
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', "Update");
        $buttonarray[] = $mform->createElement('cancel','resetbutton',"Reset to Default");
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
        //$this->add_action_buttons($cancel=true,$submitlabel="Update",$cancellabel="TEST");
    }
} 

global $COURSE, $DB;

//urls for icons
$upurl = new moodle_url('/blocks/leaderboard/pix/up.svg');
$downurl = new moodle_url('/blocks/leaderboard/pix/down.svg');
$stayurl = new moodle_url('/blocks/leaderboard/pix/stay.svg');
$expandurl = new moodle_url('/blocks/leaderboard/pix/expand.svg');

// course id
$cid = required_param('id', PARAM_INT);
$start = required_param('start', PARAM_RAW);
$end = required_param('end', PARAM_RAW);

$course = $DB->get_record('course', array('id'=>$cid), '*', MUST_EXIST);

require_course_login($course, true);
//this page's url
$url = new moodle_url('/blocks/leaderboard/index.php', array('id' => $cid,'start'=>$start,'end'=>$end));

//setup page
$PAGE->requires->js(new moodle_url('/blocks/leaderboard/javascript/leaderboardTable.js'));
$PAGE->set_pagelayout('incourse');
$PAGE->set_url($url);
$PAGE->set_title(get_string('leaderboard', 'block_leaderboard'));
$PAGE->set_heading($course->fullname);
$PAGE->add_body_class("leaderboard page");
$is_student = false;
if(user_has_role_assignment($USER->id,5)){
    $is_student = true;
}


//-------------------------------------------------------------------------------------------------------------------//
// CREATE TABLE
//create an html table
//get all groups from the current course
$groups = $DB->get_records('groups', array('courseid'=>$cid));
if(count($groups) > 0){ //there are groups to display
    //create the table
    $table = new html_table();
    $table->head = array("",get_string('rank', 'block_leaderboard'),"",get_string('name', 'block_leaderboard'),get_string('points', 'block_leaderboard'));
    $table->attributes['class'] = 'generaltable leaderboardtable';

    //get average group size
    $num_groups = count($groups);
    $num_students = 0;
    foreach($groups as $group){
        //get each member of the group
        $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
        $num_students += count($students);
    }
    //get the average group size
    $average_group_size = ceil($num_students/$num_groups);

    //get all group data
    $group_data_array = [];
    foreach($groups as $group){
        $multiplier = new block_leaderboard_functions;
        $group_data_array[] = $multiplier->get_group_data($group, $average_group_size,$start,$end);
    }

    //sort the groups by points
    if(count($group_data_array) > 1){ //only sort if there is something to sort
        usort($group_data_array, function ($a, $b) {
            return $b->points <=> $a->points;
        });
    }

    //make teams that are tied have the same rank
    $rank_array = [];
    $count = 1;
    $position = 1;
    for($i = 0; $i<count($group_data_array); $i++){ 
        $position++;
        $rank_array[$i] = $count;
        if($i < (count($group_data_array) - 1)){
            if($group_data_array[$i]->points != $group_data_array[$i+1]->points){   
                $count = $position;
            }
        }
    }

    //display each groupin the table
    $group_index = 0;
    foreach($group_data_array as $group_data){ 
        //set groups change in position icon
        $current_standing = $rank_array[$group_index];
        $symbol = " ";

        $move = substr($group_data->past_standing, -2,1); //0 for up, 1 for down, 2 for stay
        $initialPosition = substr($group_data->past_standing, -1);
        $past_standing = substr($group_data->past_standing, 0, -2);
        
        if ($group_data->time_updated < floor((time()-7*60)/86400)){
            if($past_standing > $current_standing){
                $symbol = '<img src='.$upurl.'>';
                $move = 0;
            } else if ($past_standing < $current_standing) {
                $symbol = '<img src='.$downurl.'>';
                $move = 1;
            } else if ($initialPosition == $past_standing) {
                $symbol = '<img src='.$stayurl.'>';
                $move = 2;
            } else {
                if ($move == 0){
                    $symbol = '<img src='.$upurl.'>';
                }
                if ($move == 1){
                    $symbol = '<img src='.$downurl.'>';
                }
                if ($move == 2){
                    $symbol = '<img src='.$stayurl.'>';
                }
            }
            $initialPosition = $past_standing;
        } else{
            if($past_standing > $current_standing){
                $symbol = '<img src='.$upurl.'>';
                $move = 0;
            } else if ($past_standing < $current_standing) {
                $symbol = '<img src='.$downurl.'>';
                $move = 1;
            } else {
                if ($move == 0){
                    $symbol = '<img src='.$upurl.'>';
                }
                if ($move == 1){
                    $symbol = '<img src='.$downurl.'>';
                }
                if ($move == 2){
                    $symbol = '<img src='.$stayurl.'>';
                }
            }
        }
        
        //update the groups current standing
        if($group_data->id){
            $stored_group_data = $DB->get_record('group_data_table', array('group_id'=> $group_data->id), $fields='*', $strictness=IGNORE_MISSING);
            $stored_group_data->current_standing = (int)($current_standing.$move.$initialPosition);
            $DB->update_record('group_data_table', $stored_group_data);
        }

        

        //add the groups row to the table
        if($group_data->is_users_group || !$is_student){ //include group students
            $group_row = new html_table_row(array('<img class="dropdown" src='.$expandurl.'>',$current_standing,$symbol,$group_data->name, round($group_data->points)));
            if($group_data->is_users_group){ //bold the group
                $group_row->attributes['class'] = 'group this_group collapsible rank'.$current_standing;
                $group_row->attributes['name'] = $group_index;
            } else{ //don't bold the group
                $group_row->attributes['class'] = 'group collapsible rank'.$current_standing;
                $group_row->attributes['name'] = $group_index;
            }
            $table->data[] = $group_row;
        } else {
            $group_row = new html_table_row(array('',$current_standing,$symbol,$group_data->name, round($group_data->points)));
            $group_row->attributes['class'] = 'group rank'.$current_standing;
            $table->data[] = $group_row;
        }
        
        if(!$is_student || $group_data->is_users_group){ //if this is the teacher or current user group
            //add the students to the table
            $students_data = $group_data->students_data;
            $count=0;
            foreach($students_data as $key=>$value){
                $student_data = $value;

                //add the student to the table
                if(!$is_student || $student_data->id == $USER->id){ //include student history
                    if(empty($student_data->history) != 1){
                        $individual_row = new html_table_row(array("",'<img class="dropdown" src='.$expandurl.'>',"",$student_data->firstname." ".$student_data->lastname, round($student_data->points)));  
                    } else {
                        $individual_row = new html_table_row(array("","","",$student_data->firstname." ".$student_data->lastname, round($student_data->points)));
                    }
                    if($student_data->id === $USER->id){ //bold the current user
                        $individual_row->attributes['class'] = 'this_user content';
                    } else{ //don't bold
                        $individual_row->attributes['class'] = 'content';
                    }
                    $individual_row->attributes['name'] = 'c'.$group_index;
                    $individual_row->attributes['child'] = 's'.$count;
                    $table->data[] = $individual_row;
                    
                    if(empty($student_data->history) != 1){
                        //add the students data to the table
                        $infoCount = 0;
                        foreach($student_data->history as $points_module){
                            //$module_row = new html_table_row();
                            //echo("<script>console.log('EVENT1: ".json_encode($points_module)."');</script>");
                            if(property_exists($points_module, "is_response")){
                                if($points_module->is_response == 0){
                                    $module_row = new html_table_row(array("","","","Forum Post",round($points_module->points_earned)));
                                } else if($points_module->is_response == 1){
                                    $module_row = new html_table_row(array("","","","Forum Response",round($points_module->points_earned)));
                                } 
                            } else {
                                if(property_exists($points_module, "days_early") && $points_module->points_earned > 0){
                                    $module_row = new html_table_row(array("",'<img class="dropdown" src='.$expandurl.'>',"",$points_module->module_name,round($points_module->points_earned)));
                                } else {
                                    $module_row = new html_table_row(array("","","",$points_module->module_name,round($points_module->points_earned)));
                                }
                            }
                            $module_row->attributes['class'] = 'subcontent';
                            $module_row->attributes['child'] = 'i'.$infoCount;
                            $module_row->attributes['name'] = 'c'.$group_index.'s'.$count;
                            $table->data[] = $module_row;

                            $early_points = 0;
                            $attempts_points = 0;
                            $spacing_points = 0;

                            //include info about how many days early a task was completed
                            if(property_exists($points_module, "days_early")){
                                $days_early = $points_module->days_early;
                                if(property_exists($points_module, "attempts")){
                                    for($x=1; $x<=5; $x++){
                                        $current_time = get_config('leaderboard','quiztime'.$x);
                                        if($x < 5) {
                                            $next_time = get_config('leaderboard','quiztime'.($x+1));
                                            if($days_early >= $current_time && $days_early < $next_time){
                                                $early_points = get_config('leaderboard','quizpoints'.$x);
                                            }
                                        } else {
                                            if($days_early >= $current_time){
                                                $early_points = get_config('leaderboard','quizpoints'.$x);
                                            }
                                        }
                                    }
                                } else {
                                    for($x=1; $x<=5; $x++){
                                        $current_time = get_config('leaderboard','assignmenttime'.$x);
                                        if($x < 5) {
                                            $next_time = get_config('leaderboard','assignmenttime'.($x+1));
                                            if($days_early >= $current_time && $days_early < $next_time){
                                                $early_points = get_config('leaderboard','assignmnetpoints'.$x);
                                            }
                                        } else {
                                            if($days_early >= $current_time){
                                                $early_points = get_config('leaderboard','assignmnetpoints'.$x);
                                            }
                                        }
                                    }
                                }
                                if($early_points > 0){
                                    $module_row = new html_table_row(array("","","","Submitted ".abs(round($points_module->days_early))." days early", $early_points));
                                    $module_row->attributes['class'] = 'contentInfo';
                                    $module_row->attributes['name'] = 'c'.$group_index.'s'.$count.'i'.$infoCount;
                                    $table->data[] = $module_row;
                                }
                            }
                            //include info about how many times a quiz was attempted
                            if(property_exists($points_module, "attempts")){
                                
                                $attempts_points = get_config('leaderboard','quizattemptspoints')*($points_module->attempts - 1);
                                if($attempts_points > 0){
                                    $module_row = new html_table_row(array("","","",$points_module->attempts." attempts", $attempts_points));
                                    $module_row->attributes['class'] = 'contentInfo';
                                    $module_row->attributes['name'] = 'c'.$group_index.'s'.$count.'i'.$infoCount;
                                    $table->data[] = $module_row;
                                }
                            }

                            //include info about how long quizzes were spaced out
                            if(property_exists($points_module, "days_spaced")){
                                $spacing_points = 0;
                                $unit = " days spaced";
                                $quiz_spacing = round($points_module->days_spaced,5);    
                                //get the spacing_points for the given days_spaced
                                for($x=1; $x<=3; $x++){
                                    $current_spacing = get_config('leaderboard','quizspacing'.$x);
                                    if($x < 3) {
                                        $next_spacing = get_config('leaderboard','quizspacing'.($x+1));
                                        if($quiz_spacing >= $current_spacing && $quiz_spacing < $next_spacing){
                                            $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                                            break;
                                        }
                                    } else {
                                        if($current_spacing <= $quiz_spacing){
                                            $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                                            $quiz_spacing = $current_spacing;
                                            $unit = " or more days spaced";
                                        }
                                    }
                                }
                                
                                if($spacing_points > 0){
                                    $module_row = new html_table_row(array("","","",$quiz_spacing.$unit,$spacing_points));
                                    $module_row->attributes['class'] = 'contentInfo';
                                    $module_row->attributes['name'] = 'c'.$group_index.'s'.$count.'i'.$infoCount;
                                    $table->data[] = $module_row;
                                }
                            }

                            $infoCount++;
                        }
                    }
                } else { //don't include student history
                    $individual_row = new html_table_row(array("","","",$student_data->firstname." ".$student_data->lastname, round($student_data->points)));
                    //don't bold student
                    $individual_row->attributes['class'] = 'content';
                    $individual_row->attributes['name'] = 'c'.$group_index;
                    $table->data[] = $individual_row;
                }
                $count++;
            }
            //if the teams are not equal add visible bonus points to the table
            if($group_data->bonus_points > 0){
                $individual_row = new html_table_row(array("","","",get_string('extra_points', 'block_leaderboard'), round($group_data->bonus_points)));
                $individual_row->attributes['class'] = 'content';
                $individual_row->attributes['name'] = 'c'.$group_index;
                $individual_row->attributes['child'] = 's'.$count;
                $table->data[] = $individual_row;
            }
        }
        $group_index++;
    }    
} else { //there are no groups in the class
    $table = new html_table();
    $table->head = array("",get_string('rank', 'block_leaderboard'),"",get_string('name', 'block_leaderboard'),get_string('points', 'block_leaderboard'));
    $row = new html_table_row(array("","",get_string('no_Groups_Found', 'block_leaderboard'),"",""));
    $table->data[] = $row;
}
$mform = new simplehtml_form(null, array('startDate'=>$start, 'endDate'=>$end));

$toform=new stdClass;
$toform->id=$cid;
$toform->start=$start;
$toform->end=$end;
$mform->set_data($toform);

if(!$is_student){
    if ($mform->is_cancelled()) {
        $sql = "SELECT course.startdate,course.enddate
                FROM {course} AS course
                WHERE course.id = ?;";

        $course = $DB->get_record_sql($sql, array($cid));
        
        $start = $course->startdate;
        $end = $course->enddate;
        if($end == 0){
            $end = (int)$start+61516800;
        }

        $reset1UT = 0;
        $reset2UT = 0;
        
        $reset1 = get_config('leaderboard','reset1');
        $reset2 = get_config('leaderboard','reset2');

        if($reset1 != ''  && $reset2 != ''){
            $reset1UT = strtotime($reset1);
            $reset2UT = strtotime($reset2);
        }
        if(time() < $reset1UT){
            $end = $reset1UT;
        }
        else if(time() >= $reset1UT && time() < $reset2UT){
            $start = $reset1UT;
            $end = $reset2UT;
        } else if(time() >= $reset2) {
            $start = $reset2UT;
        }

        $defaulturl = new moodle_url('/blocks/leaderboard/index.php', array('id' => $cid,'start' => $start,'end' => $end));
        redirect($defaulturl);
    } else if ($fromform = $mform->get_data()){
        $nexturl = new moodle_url('/blocks/leaderboard/index.php', array('id' => $cid,'start'=>$fromform->startDate,'end'=>$fromform->endDate));
        redirect($nexturl);
    }
}
//-------------------------------------------------------------------------------------------------------------------//
// DISPLAY PAGE CONTENT
echo $OUTPUT->header();
echo '<h2>'.get_string('leaderboard', 'block_leaderboard').'</h2>';
echo html_writer::table($table);

//load CSV file with student data
if(!$is_student){
    //display the download button
    $mform->display();
    echo html_writer::div($OUTPUT->single_button(new moodle_url('classes/data_loader.php', array('id' => $cid,'start' => $start,'end' => $end)), get_string('download_data', 'block_leaderboard'),'get'), 'download_button');    
    
}

//display the Q/A
echo '<div class="info">'.get_string('info', 'block_leaderboard').'</div>';
echo '<div class="description">'.get_string('description', 'block_leaderboard').'</div>';
echo '<div class="info">'.get_string('QA', 'block_leaderboard').'</div>';
echo '<div class="q">'.get_string('q0', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a0', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q1', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a1', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q2', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a partone">'.get_string('a2', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a levels">'.get_string('a22', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q6', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a6', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q7', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a7', 'block_leaderboard').'</div>';
echo $OUTPUT->footer();

//if($USER->id==5){
    echo("<script>console.log('Erik:');</script>");
    foreach($groups as $group){
        //get each member of the group
        $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
        foreach($students as $student){
            $past_quizzes = $DB->get_records('quiz_table',array('student_id'=> $student->id), $sort='time_started ASC');
            $clean_quizzes = [];
            foreach($past_quizzes as $past_quiz){
                if ($past_quiz->time_finished != null){
                    $clean_quizzes[] = $past_quiz;
                }
            }
            echo("<script>console.log('EVENT1: ".json_encode($clean_quizzes)."');</script>");
            $previous_time = 0;
            foreach($clean_quizzes as $quiz){
                $days_before_submission = $quiz->days_early;
                $points_earned = 0;
                if(abs($days_before_submission) < 50){ //quizzes without duedates will produce a value like -17788
                    $quiz->days_early = $days_before_submission;
                    for($x=1; $x<=5; $x++){
                        $current_time = get_config('leaderboard','quiztime'.$x);
                        if($x < 5) {
                            $next_time = get_config('leaderboard','quiztime'.($x+1));
                            if($days_before_submission >= $current_time && $days_before_submission < $next_time){
                                $points_earned = get_config('leaderboard','quizpoints'.$x);
                            }
                        } else {
                            if($days_before_submission >= $current_time){
                                $points_earned = get_config('leaderboard','quizpoints'.$x);
                            }
                        }
                    }
                } else {
                    $quiz->days_early = 0;
                    $points_earned = 0;
                }

                $quiz->points_earned = $points_earned;

                $spacing_points = 0;
                //echo("<script>console.log('EVENT1: ".$quiz->days_spaced."');</script>");
                $quiz_spacing = ($quiz->time_started - $previous_time)/(float)86400;
                echo("<script>console.log('SPACING: ".$quiz_spacing."');</script>");

                //make sure that days spaced doesn't go above a maximum of 5 days
                $quiz->days_spaced = min($quiz_spacing, 5.0);
                //echo("<script>console.log('EVENT1: ".$quiz."');</script>");
                echo("<script>console.log('SPACING: ".$quiz->days_spaced."');</script>");

                echo("<script>console.log('SPACING: ".json_encode($quiz)."');</script>");
                for($x=1; $x<=3; $x++){
                    $current_spacing = get_config('leaderboard','quizspacing'.$x);
                    if($x < 3) {
                        $next_spacing = get_config('leaderboard','quizspacing'.($x+1));
                        if($quiz_spacing >= $current_spacing && $quiz_spacing < $next_spacing){
                            $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                            break;
                        }
                    } else {
                        if($current_spacing <= $quiz_spacing){
                            $spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                        }
                    }
                }
                $previous_time = $quiz->time_started;
                $quiz->points_earned += $spacing_points;
                $multiple_attempt_points = 0;
                $points = 0;
                $quiz_attempts = get_config('leaderboard','quizattempts');
                
                $multiple_attempt_points = get_config('leaderboard','quizattemptspoints');
                
                
                $points += $multiple_attempt_points*($quiz->attempts-1);
                $quiz->points_earned += $multiple_attempt_points*($quiz->attempts-1);
                //$quiz->points_earned = 0;
                //$quiz->days_spaced = 0;
                
                $DB->update_record('quiz_table', $quiz);
            }
        }
    }
//}
