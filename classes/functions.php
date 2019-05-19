<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 9/21/18
 */

defined('MOODLE_INTERNAL') || die();

class block_leaderboard_functions{    
    public static function get_date_range($courseid){
        global $DB;
        $sql = "SELECT course.startdate,course.enddate
                FROM {course} AS course
                WHERE course.id = ?;";

        $course = $DB->get_record_sql($sql, array($courseid));
        
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

        $dateRange = new stdClass();
        $dateRange->start = $start;
        $dateRange->end = $end;
        return $dateRange;
    }

    public static function update_standing($past_standing,$current_standing,$time_updated){
        //table icon urls
        $upurl = new moodle_url('/blocks/leaderboard/pix/up.svg');
        $downurl = new moodle_url('/blocks/leaderboard/pix/down.svg');
        $stayurl = new moodle_url('/blocks/leaderboard/pix/stay.svg');
        
        $move = substr($past_standing, -2,1); //0 for up, 1 for down, 2 for stay
        $initialPosition = substr($past_standing, -1);
        $past_standing = substr($past_standing, 0, -2);
        $symbol = " ";

        if ($time_updated < floor((time()-7*60)/86400)){
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
                else if ($move == 1){
                    $symbol = '<img src='.$downurl.'>';
                }
                else if ($move == 2){
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
                else if ($move == 1){
                    $symbol = '<img src='.$downurl.'>';
                }
                else if ($move == 2){
                    $symbol = '<img src='.$stayurl.'>';
                }
            }
        }
        $standingChanges = new stdClass;
        $standingChanges->symbol = $symbol;
        $standingChanges->move = $move;
        $standingChanges->initialPosition = $initialPosition;
        return $standingChanges;
    }
    public static function get_average_group_size($groups){
        //determine average group size
        $num_groups = count($groups);
        $num_students = 0;
        if($num_groups > 0){
            foreach($groups as $group){
                //get each member of the group
                $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
                $num_students += count($students);
            }
            $average_group_size = ceil($num_students/$num_groups);
            return $average_group_size;
        } else {
            return 0;
        }
    }


    //private static function get_student_data($student)
    public static function get_group_data($group, $average_group_size,$start,$end){
        global $DB, $USER;;

        $past_week_points = 0;
        $past_two_weeks_points = 0;
        $total_points = 0;
        $is_users_group = false;

        //add up all of the members points
        $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
        $students_data = [];
        foreach($students as $student){
            $points = self::get_points($student,$start,$end);
            $past_week_points += $points->past_week;
            $past_two_weeks_points += $points->past_two_weeks;
            $total_points += $points->all;

            $student_data = new stdClass();
            $student_data->points = $points->all;
            $student_data->history = $points->history;
            $student_data->id = $student->id;
            $student_data->firstname = $student->firstname;
            $student_data->lastname = $student->lastname;
            $students_data[] = $student_data;
            
            //set to true if this student matches the current logged in $USER
            if($student->id === $USER->id){
                $is_users_group = true;
            }
        }

        //if the teams are not equal size make it a fair size
        $group_size = count($students);
        $bonus_points = 0;
        
        if($group_size != $average_group_size){
            $bonus_points = $total_points/$group_size * $average_group_size - $total_points;
            $past_week_points = $past_week_points / $group_size * $average_group_size;
            $past_two_weeks_points = $past_two_weeks_points / $group_size * $average_group_size;
            $total_points = $total_points / $group_size * $average_group_size;
        }
        //calculate the points per week
        $points_per_week = $past_week_points;
        $points_per_two_weeks = $past_two_weeks_points / 2;

        //take the one week rate if it is higher to account for slow weeks or fall/spring breaks
        if($points_per_week > $points_per_two_weeks){
            $points_per_week = $points_per_week;
        } else {
            $points_per_week = $points_per_two_weeks;
        }
        $points_per_week = round($points_per_week);
        
        $stored_group_data = $DB->get_record('group_data_table', array('group_id'=> $group->id), $fields='*', $strictness=IGNORE_MISSING);
        if(!$stored_group_data){
            $stored_group_data = new stdClass();
            $stored_group_data->current_standing = 020;
            $stored_group_data->multiplier = floor((time()-7*60)/86400);
            $stored_group_data->group_id = $group->id;
            $DB->insert_record('group_data_table',$stored_group_data);
        } else if(strlen((string)$stored_group_data->current_standing) < 3){
            $stored_group_data->current_standing = (int)($stored_group_data->current_standing.'2'.$stored_group_data->current_standing);
            $stored_group_data->group_id = $group->id;
            $DB->update_record('group_data_table',$stored_group_data);
        }

        //load the groups data into an object
        $group_data = new stdClass();
        $group_data->name = $group->name;
        $group_data->id = $group->id;
        $group_data->past_standing = $stored_group_data->current_standing;
        $group_data->time_updated = $stored_group_data->multiplier;
        $group_data->points = $total_points;
        $group_data->is_users_group = $is_users_group;
        $group_data->points_per_week = $points_per_week;
        $group_data->students_data = $students_data;
        $group_data->bonus_points = $bonus_points;
        return $group_data;
    }

    public static function get_points($student,$start,$end){
        global $DB;

        //create a new object
        $points = new stdClass();
        $points->all = 0;
        $points->past_week = 0;
        $points->past_two_weeks = 0;
        $points->history = [];
        $student_history = [];


        //add up student points for all points, past week, past two weeks, and fill student history array
        $time = time();
        $reset = 0;
        $reset1 = get_config('leaderboard','reset1');
        $reset2 = get_config('leaderboard','reset2');
        if($reset1 != ''  && $reset2 != ''){
            $reset1 = strtotime($reset1);
            $reset2 = strtotime($reset2);
        }
        if(time() >= $reset1 && time() < $reset2){
            $reset = $reset1;
        } else if(time() >= $reset2) {
            $reset = $reset2;
        }

        //ACTIVITY
        $sql = "SELECT assignment_table.*,assign.duedate
                FROM {assign_submission} AS assign_submission
                INNER JOIN {assignment_table} AS assignment_table ON assign_submission.id = assignment_table.activity_id
                INNER JOIN {assign} AS assign ON assign.id = assignment_table.activity_id
                WHERE assignment_table.activity_student = ?;";

        $student_activities = $DB->get_records_sql($sql, array($student->id));
        $points = self::test2($student_activities,$start,$end,$points);
        
        //QUIZ
        $sql = "SELECT quiz_table.*, quiz.timeclose
                FROM {quiz_table} AS quiz_table
                INNER JOIN {quiz} AS quiz ON quiz.id = quiz_table.quiz_id
                WHERE quiz_table.student_id = ? AND quiz_table.time_finished IS NOT NULL;";

        $student_quizzes = $DB->get_records_sql($sql, array($student->id));
        $points = self::test2($student_quizzes,$start,$end,$points);
        
        //CHOICE
        $student_choices = $DB->get_records('choice_table', array('student_id'=> $student->id));
        $points = self::test($student_choices,$start,$end,$points);
        
        //FORUM
        $student_forum_posts = $DB->get_records('forum_table', array('student_id'=> $student->id));
        $points = self::test($student_forum_posts,$start,$end,$points);
        
        $student_history = $points->history;
        if(count($student_history) > 1){ //only sort if there is something to sort
            usort($student_history, function ($a, $b) {
                return $b->time_finished <=> $a->time_finished;
            });
        }
        $points->history = $student_history;

        return $points;
    }

    public static function test($list,$start,$end,$points){
        $time = time();
        foreach($list as $post){
            if($post->time_finished >= $start && $post->time_finished <= $end){
                $points->all += $post->points_earned;
                if(($time - $post->time_finished)/86400 <= 7){
                    $points->past_week += $post->points_earned;
                }
                if(($time - $post->time_finished)/86400 <= 14){
                    $points->past_two_weeks += $post->points_earned;
                }
                if($post->module_name != ''){
                    $points->history[] = $post;
                }
            }
        }
        return $points;
    }

    public static function test2($list,$start,$end,$points){
        $time = time();
        foreach($list as $activity){
            $due_date = INF;
            if(isset($activity->duedate)){
                $due_date = $activity->duedate;
            } else if(isset($activity->timeclose)) {
                $due_date = $activity->timeclose;
            }

            if($time >= $due_date && $due_date > $start && $due_date < $end){
                $points->all += $activity->points_earned;
                if(($time - $activity->time_finished)/86400 <= 7){
                    $points->past_week += $activity->points_earned;
                }
                if(($time - $activity->time_finished)/86400 <= 14){
                    $points->past_two_weeks += $activity->points_earned;
                }
                if($activity->module_name != ''){
                    $points->history[] = $activity;
                }
            }
        }
        return $points;
    }
}

