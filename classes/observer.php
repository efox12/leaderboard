<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 12/29/18
 */

defined('MOODLE_INTERNAL') || die();
class block_leaderboard_observer {  
    //-------------------------------------------------------------------------------------------------------------------//
    //ASSIGNMENT EVENTS
    public static function assignment_submitted_handler(\mod_assign\event\assessable_submitted $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            $eventdata = new \stdClass();
            
            //The id of the object the event is occuring on
            $eventid = $event->objectid;

            //The data of the submission
            $sql = "SELECT assign.*, assign_submission.userid
                FROM {assign_submission} AS assign_submission
                INNER JOIN {assign} AS assign ON assign.id = assign_submission.assignment
                WHERE assign_submission.id = ?;";

            $assignment_data = $DB->get_record_sql($sql, array($eventid));
            

            //86400 seconds per day in unix time
            //intdiv() is integer divinsion for PHP '/' is foating point division
            $days_before_submission = ($assignment_data->duedate - $event->timecreated)/86400;
            
            //Set the point value
            $points = 0;
            for($x=1; $x<=5; $x++){
                $current_time = get_config('leaderboard','assignmenttime'.$x);
                if($x < 5) {
                    $next_time = get_config('leaderboard','assignmenttime'.($x+1));
                    if($days_before_submission >= $current_time && $days_before_submission < $next_time){
                        $points = get_config('leaderboard','assignmnetpoints'.$x);
                        break;
                    }
                } else {
                    if($days_before_submission >= $current_time){
                        $points = get_config('leaderboard','assignmnetpoints'.$x);
                        break;
                    }
                }
            }
            $eventdata->points_earned = block_leaderboard_functions::calculate_points($event->userid, $points);
            $eventdata->activity_student = $assignment_data->userid;
            $eventdata->activity_id = $eventid;
            $eventdata->time_finished = $event->timecreated;
            $eventdata->module_name = $assignment_data->name;
            $eventdata->days_early = $days_before_submission;

            $activities = $DB->get_records('assignment_table', array('activity_id'=> $eventid,'activity_student' => $assignment_data->userid));
            //Insert the new data into the databese if new, update if old
            if($activities){
                //the id of the object is required for update_record();
                foreach ($activities as $activity){
                    if($activity->activity_id == $eventid){
                        $eventdata->id = $activity->id;
                        break;
                    }
                }
                $DB->update_record('assignment_table', $eventdata);
            } else{ 
                $DB->insert_record('assignment_table', $eventdata);
            }
        }         
    }

    //-------------------------------------------------------------------------------------------------------------------//
    //QUIZ EVENTS
    //when officially starting the quiz
    //use this to add the starting time of the quiz to the database and create questions data table
    public static function quiz_started_handler(\mod_quiz\event\attempt_started $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            //the id corresponding to the users current attempt
            $current_id = $event->objectid;
        
            //The data of the submission
            $sql = "SELECT quiz.*
                FROM {quiz_attempts} AS quiz_attempts
                INNER JOIN {quiz} AS quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $quiz = $DB->get_record_sql($sql, array($current_id));

            $sql = "SELECT quiz.*
                FROM {quiz_attempts} AS quiz_attempts
                INNER JOIN {quiz} AS quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $this_quiz = $DB->get_record_sql($sql, array($current_id));
            if(!$this_quiz){
                //create a new quiz
                $quizdata = new \stdClass();
                $quizdata->time_started = $event->timecreated;
                $quizdata->quiz_id = $quiz->id;
                $quizdata->student_id = $event->userid;
                $quizdata->attempts = 0;
                $quizdata->days_early = 0;
                $quizdata->days_spaced = 0;
                $quizdata->module_name = $quiz->name;
                $DB->insert_record('quiz_table', $quizdata);
            }
        }
    }

    //when clicking the confirmation button to submit the quiz
    //use this to retroactively determine points
    public static function quiz_submitted_handler(\mod_quiz\event\attempt_submitted $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            //the users current attempt
            $current_id = $event->objectid;
            
            //the quiz
            $sql = "SELECT quiz.*
                FROM {quiz_attempts} AS quiz_attempts
                INNER JOIN {quiz} AS quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $this_quiz = $DB->get_record_sql($sql, array($current_id));
            $due_date = $this_quiz->timeclose;
            
            //the table for the leader board block
            $quiz_table = $quiz_table = $DB->get_record('quiz_table',
                                    array('quiz_id'=> $this_quiz->id, 'student_id'=> $event->userid),
                                    $fields='*',
                                    $strictness=IGNORE_MISSING);

            //add a quiz to the database if one doesn't already exist
            if($quiz_table === false){
                $quiz_table = new \stdClass();
                $quiz_table->time_started = $event->timecreated;
                $quiz_table->quiz_id = $this_quiz->id;
                $quiz_table->student_id = $event->userid;
                $quiz_table->attempts = 0;
                $quiz_table->days_early = 0;
                $quiz_table->days_spaced = 0;
                $DB->insert_record('quiz_table', $quiz_table);
                $quiz_table = $quiz_table = $DB->get_record('quiz_table',
                                    array('quiz_id'=> $this_quiz->id, 'student_id'=> $event->userid),
                                    $fields='*',
                                    $strictness=IGNORE_MISSING);
            }
            $quiz_table->time_finished = $event->timecreated;
            $quiz_table->module_name = $this_quiz->name;
            if($quiz_table->attempts == 0){ //if this is the first attempt of the quiz
                $quiz_table->attempts = 1;
                //assign points for finishing early
                $days_before_submission = ($due_date - $event->timecreated)/86400;
                $points_earned = 0;
                if(abs($days_before_submission) < 50){ //quizzes without duedates will produce a value like -17788
                    $quiz_table->days_early = $days_before_submission;
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
                    $quiz_table->days_early = 0;
                    $points_earned = 0;
                }

                $quiz_table->points_earned = block_leaderboard_functions::calculate_points($event->userid, $points_earned);  
                
                //gets the most recent completed quiz submission time
                $past_quizzes = $DB->get_records('quiz_table',array('student_id'=> $event->userid));
                $recent_time_finished = 0;
                foreach($past_quizzes as $past_quiz){
                    if($past_quiz->time_finished > $recent_time_finished){
                        $recent_time_finished = $past_quiz->time_finished;
                    }
                }

                //bonus points get awarded for spacing out quizzes instead of cramming (only judges the 2 most recent quizzes)
                $spacing_points = 0;
                $quiz_spacing = ($quiz_table->time_started - $recent_time_finished)/(float)86400;
                echo("<script>console.log('EVENT1: ".$quiz_spacing."');</script>");
                //make sure that days spaced doesn't go above a maximum of 5 days
                $quiz_table->days_spaced = min($quiz_spacing, 5);
                
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
                echo("<script>console.log('EVENT: ".$quiz_table->days_spaced."');</script>");
                $quiz_table->points_earned += block_leaderboard_functions::calculate_points($event->userid, $spacing_points);

            } else { //this is another attempt
                //bonus points for attempting quiz again (need to find a way to limit abuse)
                $multiple_attempt_points = 0;
                $quiz_attempts = get_config('leaderboard','quizattempts');
                if($quiz_table->attempts <= $quiz_attempts){
                    $multiple_attempt_points = get_config('leaderboard','quizattemptspoints');
                }
                $quiz_table->attempts += 1;
                $quiz_table->points_earned += block_leaderboard_functions::calculate_points($event->userid, $multiple_attempt_points);
            }
            $DB->update_record('quiz_table', $quiz_table);
        }
    }

    //unsure
    public static function quiz_overdue_handler(\mod_quiz\event\attempt_becameoverdue $event){
        echo("<script>console.log('EVENT: ".json_encode($event->get_data())."');</script>");
    }

    //-------------------------------------------------------------------------------------------------------------------//
    //CHOICE EVENTS
    public static function choice_submitted_handler(\mod_choice\event\answer_created $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            $sql = "SELECT choice_answers.id, choice.name
                FROM {choice_answers} AS choice_answers
                INNER JOIN {choice} AS choice ON choice.id = choice_answers.choiceid
                WHERE choice_answers.id = ?;";

            $choice = $DB->get_record_sql($sql, array($event->objectid));
            if($DB->get_record('choice_table', array('choice_id'=> $choice->id, 'student_id'=> $event->userid), $fields='*', $strictness=IGNORE_MISSING) == false){ //if new coice then add to database
                $choicedata = new \stdClass();
                $choicedata->student_id = $event->userid;
                $choicedata->choice_id = $choice->id;
                $choicedata->points_earned = block_leaderboard_functions::calculate_points($event->userid, get_config('leaderboard','choicepoints'));
                $choicedata->time_finished = $event->timecreated;
                $choicedata->module_name = $choice->name;
                
                $DB->insert_record('choice_table', $choicedata);
            }
        }
    }

    //-------------------------------------------------------------------------------------------------------------------//
    //FORUM EVENTS
    public static function forum_posted_handler(\mod_moodleoverflow\event\post_created $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            $forumdata = new \stdClass();
            $forumdata->student_id = $event->userid;
            $forumdata->forum_id = $event->other{'moodleoverflowid'}; 
            $forumdata->discussion_id = $event->other{'discussionid'};
            $forumdata->post_id = $event->objectid;
            $forumdata->is_response = true;
            $forumdata->points_earned = block_leaderboard_functions::calculate_points($event->userid, get_config('leaderboard','forumresponsepoints'));
            $forumdata->time_finished = $event->timecreated;
            $forumdata->module_name = "Forum Response";
            
            $DB->insert_record('forum_table', $forumdata);
        }
    }

    public static function discussion_created_handler(\mod_moodleoverflow\event\discussion_created $event){
        global $DB, $USER;
        if(user_has_role_assignment($USER->id,5)){
            $discussion = $DB->get_record('moodleoverflow_discussions', array('id'=> $event->objectid), $fields='*', $strictness=IGNORE_MISSING);        
            $forum_id = $event->other{'forumid'};
            $forumdata = new \stdClass();
            $forumdata->student_id = $event->userid;
            $forumdata->forum_id = $discussion->moodleoverflow;
            $forumdata->post_id = $discussion->firstpost;
            $forumdata->discussion_id = $event->objectid;
            $forumdata->is_response = false;
            $forumdata->points_earned = block_leaderboard_functions::calculate_points($event->userid, get_config('leaderboard','forumpostpoints'));
            $forumdata->time_finished = $event->timecreated;
            $forumdata->module_name = $discussion->name;

            $DB->insert_record('forum_table', $forumdata);
        }  
    }
}