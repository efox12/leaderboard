<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 8/20/18
 */

defined('MOODLE_INTERNAL') || die;

class block_leaderboard_renderer extends plugin_renderer_base {
    public function leaderboard_block($course){
        global $DB, $USER, $OUTPUT, $COURSE;
        
        //determine if the curent user is a student
        $is_student = false;
        if(user_has_role_assignment($USER->id,5)){
            $is_student = true;
        }

        //-------------------------------------------------------------------------------------------------------------------//
        //PREPARE DATA FOR TABLE
        $courseid  = $COURSE->id;
        $functions = new block_leaderboard_functions;
        $dateRange = $functions->get_date_range($courseid);
        $start = $dateRange->start;
        $end = $dateRange->end;
        

        $url = new moodle_url('/blocks/leaderboard/index.php', array('id' => $courseid,'start' => $start,'end' => $end));

        //get all groups from the current course
        $groups = $DB->get_records('groups', array('courseid'=>$courseid));
        //only display content in the block if there are groups
        if(count($groups) > 0){
            $average_group_size = $functions->get_average_group_size($groups);
            //get data for the groups
            $group_data_array = array();
            foreach($groups as $group){
                $group_data_array[] = $functions->get_group_data($group, $average_group_size,$start,$end);
            }

            //sort groups by points
            if(count($group_data_array) > 1){ //only sort if there is something to sort
                usort($group_data_array, function ($a, $b) {
                    return $b->points <=> $a->points;
                });
            }
    
            //-------------------------------------------------------------------------------------------------------------------//
            // CREATE TABLE

            //create an html table
            $table = new html_table();
            //fill the html table and get the current users group
            $this->create_leaderboard($group_data_array,$table,$functions);

        } else {
            // Create empty table
            $table = new html_table();
            $table->head = array(get_string('num', 'block_leaderboard')," ",get_string('group', 'block_leaderboard'),get_string('points', 'block_leaderboard'));
            $row = new html_table_row(array("","",get_string('no_Groups_Found', 'block_leaderboard'),""));
            $table->data[] = $row;
        }

        //-------------------------------------------------------------------------------------------------------------------//
        // DISPLAY BLOCK CONTENT
        $output = "";
        $output .= "<block_header>".get_string('rankings', 'block_leaderboard')."</block_header><br>";    
        $output .= html_writer::table($table);
        $output .= $OUTPUT->single_button($url, get_string('view_full_leaderboard', 'block_leaderboard'),'get');
        return $output;
    }

    //-------------------------------------------------------------------------------------------------------------------//
    //FUNCTIONS
    public function create_leaderboard($group_data_array,$table,$functions){
        global $DB;
        
        //create a new object
        $our_group_data = new stdClass();

        //add table header
        $table->head = array(get_string('num', 'block_leaderboard')," ",get_string('group', 'block_leaderboard'),get_string('points', 'block_leaderboard'));
        //add groups to the table

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

        foreach($group_data_array as $group_data){
            //set the groups current standing to the groups current index in the sorted array
            $group_index = array_search($group_data, $group_data_array);
            $current_standing = $rank_array[$group_index];
           
            $standingChanges = $functions->update_standing($group_data->past_standing,$current_standing,$group_data->time_updated);
            $symbol = $standingChanges->symbol;
            $move = $standingChanges->move;
            $initialPosition = $standingChanges->initialPosition;
            
            //update the groups current standing
            if($group_data->id){
                $stored_group_data = $DB->get_record('group_data_table', array('group_id'=> $group_data->id), $fields='*', $strictness=IGNORE_MISSING);
                $stored_group_data->current_standing = (int)($current_standing.$move.$initialPosition);
                /*if ($stored_group_data->multiplier < floor((time()-7*60)/86400)){
                    $stored_group_data->multiplier = floor((time()-7*60)/86400);
                }*/
                $DB->update_record('group_data_table', $stored_group_data);
            }
        
            //add the top three groups row to the table
            if($current_standing <=3){
                //add the group to the table
                $row = new html_table_row(array($current_standing,$symbol,$group_data->name, round($group_data->points)));
                if($group_data->is_users_group){ //bold current user group
                    $our_group_data = $group_data;
                    $row->attributes['class'] = 'this_group rank'.$current_standing;
                } else { //don't bold
                    $row->attributes['class'] = 'rank'.$current_standing;
                }
                $table->data[] = $row;
            }
            else{
                if($group_data->is_users_group){
                    //include a visual break in the table if the group has a standing of 5 or greater
                    if($current_standing > 4){
                        $break_row = new html_table_row(array("","",'<img src='.$moreurl.'>', ""));
                        $break_row->attributes['class'] = 'break_row';
                        $table->data[] = $break_row;
                    }
                    //add the current users group to the table
                    $our_group_data = $group_data;
                    $row = new html_table_row(array($current_standing,$symbol,$group_data->name, round($group_data->points)));
                    $row->attributes['class'] = 'this_group';
                    $table->data[] = $row;
                }
            }
        }
        //return $our_group_data;
    }
}