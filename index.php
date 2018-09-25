<?php
require_once('../../config.php');

global $COURSE, $DB;

//urls for icons
$upurl = new moodle_url('/blocks/leaderboard/pix/up.svg');
$downurl = new moodle_url('/blocks/leaderboard/pix/down.svg');
$stayurl = new moodle_url('/blocks/leaderboard/pix/stay.svg');
$expandurl = new moodle_url('/blocks/leaderboard/pix/expand.svg');

// course id
$cid = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id'=>$cid), '*', MUST_EXIST);
require_course_login($course, true);

//this page's url
$url = new moodle_url('/blocks/leaderboard/index.php', array('id' => $course->id));

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

echo $OUTPUT->header();

//-------------------------------------------------------------------------------------------------------------------//
// CREATE TABLE
//create an html table
//get all groups
$groups = $DB->get_records('groups');
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
        $multiplier = new block_leaderboard_multiplier;
        $group_data_array[] = $multiplier->get_group_data($group, $average_group_size);
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
        if($group_data->past_standing > $current_standing){
            $symbol = '<img src='.$upurl.'>';
        } else if ($group_data->past_standing < $current_standing) {
            $symbol = '<img src='.$downurl.'>';
        } else {
            $symbol = '<img src='.$stayurl.'>';
        }

        //update the groups current standing
        if($group_data->id){
            $groupdata = $DB->get_record('group_data_table', array('group_id'=> $group->id), $fields='*', $strictness=IGNORE_MISSING);
            $groupdata->current_standing = $current_standing;
            $DB->update_record('group_data_table', $groupdata);
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
                                $quiz_spacing = 0;
                                
                                if($points_module->days_spaced == 0){
                                    $spacing_points = $points_module->points_earned - $attempts_points+$early_points;
                                    $points_module->days_spaced = $quiz_spacing;
                                    for($x=1; $x<=3; $x++){
                                        $current_spacing_points = get_config('leaderboard','quizspacingpoints'.$x);
                                        if($current_spacing_points <= $spacing_points){
                                            $quiz_spacing = get_config('leaderboard','quizspacing'.$x);
                                        }
                                    }
                                } else {
                                    if($points_module->days_spaced >= 20){
                                        $quiz_spacing = round($points_module->days_spaced/100000,2);
                                        
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
                                    }
                                }
                                if($spacing_points > 0){
                                    $module_row = new html_table_row(array("","","",$quiz_spacing." days spaced",$spacing_points));
                                    $module_row->attributes['class'] = 'contentInfo';
                                    $module_row->attributes['name'] = 'c'.$group_index.'s'.$count.'i'.$infoCount;
                                    $table->data[] = $module_row;
                                }
                            }

                            //include info about extra points from multiplier
                            if($points_module->points_earned - ($attempts_points+$early_points+$spacing_points) > 0){
                                $multiplier_points = $points_module->points_earned - ($attempts_points+$early_points+$spacing_points);
                                $module_row = new html_table_row(array("","","","Points from multiplier",$multiplier_points));
                                $module_row->attributes['class'] = 'contentInfo';
                                $module_row->attributes['name'] = 'c'.$group_index.'s'.$count.'i'.$infoCount;
                                $table->data[] = $module_row;
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

//-------------------------------------------------------------------------------------------------------------------//
// DISPLAY PAGE CONTENT
echo '<h2>'.get_string('leaderboard', 'block_leaderboard').'</h2>';
echo html_writer::table($table);

//load CSV file with student data
if(!$is_student){
    //display the download button
    echo html_writer::div($OUTPUT->single_button(new moodle_url('classes/data_loader.php'), get_string('download_data', 'block_leaderboard'),"get"), 'download_button');
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
echo '<div class="q">'.get_string('q3', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a3', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q4', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a partone">'.get_string('a41', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a levels">'.get_string('a42', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q5', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a5', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="q">'.get_string('q6', 'block_leaderboard').'</div>';
echo '<br/>';
echo '<div class="a">'.get_string('a6', 'block_leaderboard').'</div>';

echo $OUTPUT->footer();
