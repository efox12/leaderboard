<?php

defined('MOODLE_INTERNAL') || die();

class block_leaderboard_data_loader {
    public static function load_data_file($groups){ 
        global $DB;
        $CSV[0] = array(
            'student_id',
            'module_id',
            'module_name',
            'module_type',
            'time_finished',
            'days_early',
            'attempts',
            'days_spaced',
            'discussion_id',
            'post_id',
            'is_response'
        );
        $count = 1;
        foreach($groups as $group){
            //get each member of the group
            $students = groups_get_members($group->id, $fields='u.*', $sort='lastname ASC');
            $group_points = 0;
            foreach($students as $student){
                //get each members past contributions and add them to an array
                $individual_points = 0;
                $student_activities = $DB->get_records('assignment_table', array('activity_student'=> $student->id));
                foreach($student_activities as $activity){
                    $CSV[$count] = array(
                        $activity->activity_student,
                        $activity->activity_id,
                        $activity->module_name,
                        'Assignment',
                        $activity->time_finished,
                        $activity->days_early,
                        'null',
                        'null',
                        'null',
                        'null',
                        'null'
                    );
                    $count++;
                }
                $student_quizzes = $DB->get_records('quiz_table', array('student_id'=> $student->id));
                foreach($student_quizzes as $quiz){
                    $CSV[$count] = array(
                        $quiz->student_id,
                        $quiz->quiz_id,
                        $quiz->module_name,
                        'Quiz',
                        $quiz->time_finished,
                        $quiz->days_early,
                        $quiz->attempts,
                        round($quiz->days_spaced/100000,2),
                        'null',
                        'null',
                        'null'
                    );
                    $count++;
                }
                $student_choices = $DB->get_records('choice_table', array('student_id'=> $student->id));
                foreach($student_choices as $choice){
                    $CSV[$count] = array(
                        $choice->student_id,
                        $choice->choice_id,
                        $choice->module_name,
                        'Choice',
                        $choice->time_finished,
                        'null',
                        'null',
                        'null',
                        'null',
                        'null',
                        'null'
                    );
                    $count++;
                }
                $student_forum_posts = $DB->get_records('forum_table', array('student_id'=> $student->id));
                foreach($student_forum_posts as $post){
                    $CSV[$count] = array(
                        $post->student_id,
                        $post->forum_id,
                        $post->module_name,
                        'Forum',
                        $post->time_finished,
                        'null',
                        'null',
                        'null',
                        $post->discussion_id,
                        $post->post_id,
                        $post->is_response
    
                    );
                    $count++;
                }
            }
        }
    
        //write data to the file
        $myfile = fopen("file.csv", "w") or die("Error: ".error_get_last());
        foreach ($CSV as $line) {
            fputcsv($myfile, $line, ',');
        }
        fclose($myfile);  
    }
}