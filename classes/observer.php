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

/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 12/29/18
 */

defined('MOODLE_INTERNAL') || die();
class block_leaderboard_observer {
    // ASSIGNMENT EVENTS.
    public static function assignment_submitted_handler(\mod_assign\event\assessable_submitted $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            $eventdata = new \stdClass();

            // The id of the object the event is occuring on.
            $eventid = $event->objectid;

            // The data of the submission.
            $sql = "SELECT assign.*, assign_submission.userid
                FROM {assign_submission} assign_submission
                INNER JOIN {assign} assign ON assign.id = assign_submission.assignment
                WHERE assign_submission.id = ?;";

            $assignmentdata = $DB->get_record_sql($sql, array($eventid));

            // 86400 seconds per day in unix time.
            // The function intdiv() is integer divinsion for PHP '/' is foating point division.
            $daysbeforesubmission = ($assignmentdata->duedate - $event->timecreated) / 86400;

            // Set the point value.
            $points = get_early_submission_points($daysbeforesubmission, 'assignment');
            $eventdata->points_earned = $points;
            $eventdata->activity_student = $assignmentdata->userid;
            $eventdata->activity_id = $eventid;
            $eventdata->time_finished = $event->timecreated;
            $eventdata->module_name = $assignmentdata->name;
            $eventdata->days_early = $daysbeforesubmission;

            $activities = $DB->get_records('assignment_table',
                            array('activity_id' => $eventid, 'activity_student' => $assignmentdata->userid));
            // Insert the new data into the databese if new, update if old.
            if ($activities) {
                // The id of the object is required for update_record().
                foreach ($activities as $activity) {
                    if ($activity->activity_id == $eventid) {
                        $eventdata->id = $activity->id;
                        break;
                    }
                }
                $DB->update_record('assignment_table', $eventdata);
                return;
            }
            $DB->insert_record('assignment_table', $eventdata);
            return;
        }
    }

    // QUIZ EVENTS.
    // When officially starting the quiz.
    // Use this to add the starting time of the quiz to the database and create questions data table.
    public static function quiz_started_handler(\mod_quiz\event\attempt_started $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            // The id corresponding to the users current attempt.
            $currentid = $event->objectid;

            // The data of the submission.
            $sql = "SELECT quiz.*
                FROM {quiz_attempts} quiz_attempts
                INNER JOIN {quiz} quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $quiz = $DB->get_record_sql($sql, array($currentid));

            $sql = "SELECT quiz.*
                FROM {quiz_attempts} quiz_attempts
                INNER JOIN {quiz} quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $thisquiz = $DB->get_record_sql($sql, array($currentid));
            if (!$thisquiz) {
                // Create a new quiz.
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

    // When clicking the confirmation button to submit the quiz
    // use this to retroactively determine points.
    public static function quiz_submitted_handler(\mod_quiz\event\attempt_submitted $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            // The users current attempt.
            $currentid = $event->objectid;

            // The quiz.
            $sql = "SELECT quiz.*
                FROM {quiz_attempts} quiz_attempts
                INNER JOIN {quiz} quiz ON quiz.id = quiz_attempts.quiz
                WHERE quiz_attempts.id = ?;";

            $thisquiz = $DB->get_record_sql($sql, array($currentid));
            $duedate = $thisquiz->timeclose;

            // The table for the leader board block.
            $quiztable = $quiztable = $DB->get_record('quiz_table',
                                    array('quiz_id' => $thisquiz->id, 'student_id' => $event->userid),
                                    $fields = '*',
                                    $strictness = IGNORE_MISSING);

            // Add a quiz to the database if one doesn't already exist.
            if ($quiztable === false) {
                $quiztable = new \stdClass();
                $quiztable->time_started = $event->timecreated;
                $quiztable->quiz_id = $thisquiz->id;
                $quiztable->student_id = $event->userid;
                $quiztable->attempts = 0;
                $quiztable->days_early = 0;
                $quiztable->days_spaced = 0;
                $quiztable->time_finished = $event->timecreated;
                $quiztable->module_name = $thisquiz->name;
                $DB->insert_record('quiz_table', $quiztable);
                $quiztable = $quiztable = $DB->get_record('quiz_table',
                                    array('quiz_id' => $thisquiz->id, 'student_id' => $event->userid),
                                    $fields = '*',
                                    $strictness = IGNORE_MISSING);
            }

            if ($quiztable->attempts == 0) { // If this is the first attempt of the quiz.
                // EARLY FINISH.
                // Assign points for finishing early.
                $daysbeforesubmission = ($duedate - $event->timecreated) / 86400;

                $quiztable->days_early = $daysbeforesubmission;
                if (abs($daysbeforesubmission) > 50) { // Quizzes without duedates will produce a value like -17788.
                    $quiztable->days_early = 0;
                    $daysbeforesubmission = 0;
                }

                $pointsearned = get_early_submission_points($daysbeforesubmission, 'quiz');
                $quiztable->points_earned = $pointsearned;

                // QUIZ SPACING.
                // Gets the most recent completed quiz submission time.
                $pastquizzes = $DB->get_records('quiz_table', array('student_id' => $event->userid));
                $recenttimefinished = 0;
                foreach ($pastquizzes as $pastquiz) {
                    if ($pastquiz->time_finished > $recenttimefinished) {
                        $recenttimefinished = $pastquiz->time_finished;
                    }
                }
                // Bonus points get awarded for spacing out quizzes instead of cramming (only judges the 2 most recent quizzes).
                $quizspacing = ($quiztable->time_started - $recenttimefinished) / (float)86400;
                // Make sure that days spaced doesn't go above a maximum of 5 days.
                $quiztable->days_spaced = min($quizspacing, 5.0);

                // Bonus points get awarded for spacing out quizzes instead of cramming (only judges the 2 most recent quizzes).
                $spacingpoints = get_quiz_spacing_points($quizspacing);
                $quiztable->points_earned += $spacingpoints;

            }
            // Bonus points for attempting quiz again.
            $multipleattemptpoints = get_quiz_attempts_points($quiztable->attempts);
            $quiztable->points_earned += $multipleattemptpoints;
            $quiztable->attempts += 1;
            $DB->update_record('quiz_table', $quiztable);
        }
    }

    public static function get_early_submission_points($daysbeforesubmission, $type) {
        for ($x = 1; $x <= 5; $x++) {
            $currenttime = get_config('leaderboard', $type.'time'.$x);
            $nexttime = INF;
            if ($x < 5) {
                $nexttime = get_config('leaderboard', $type.'time'.($x + 1));
            }
            if ($daysbeforesubmission >= $currenttime && $daysbeforesubmission < $nexttime) {
                return get_config('leaderboard', $type.'points'.$x);
            }
        }
        return 0;
    }

    public static function get_quiz_spacing_points($quizspacing) {
        for ($x = 1; $x <= 3; $x++) {
            $currentspacing = get_config('leaderboard', 'quizspacing'.$x);
            $nextspacing = INF;
            if ($x < 3) {
                $nextspacing = get_config('leaderboard', 'quizspacing'.($x + 1));
            }
            if ($quizspacing >= $currentspacing && $quizspacing < $nextspacing) {
                return get_config('leaderboard', 'quizspacingpoints'.$x);
            }
        }
        return 0;
    }

    public static function get_quiz_attempts_points($attempts) {
        $maxattempts = get_config('leaderboard', 'quizattempts');
        if ($attempts <= $maxattempts) {
            return get_config('leaderboard', 'quizattemptspoints');
        }
        return 0;
    }

    // Unsure.
    public static function quiz_overdue_handler(\mod_quiz\event\attempt_becameoverdue $event) {
        echo("<script>console.log('EVENT: ".json_encode($event->get_data())."');</script>");
    }

    // CHOICE EVENTS.
    public static function choice_submitted_handler(\mod_choice\event\answer_created $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            $sql = "SELECT choice_answers.id, choice.name
                FROM {choice_answers} choice_answers
                INNER JOIN {choice} choice ON choice.id = choice_answers.choiceid
                WHERE choice_answers.id = ?;";

            $choice = $DB->get_record_sql($sql, array($event->objectid));
            if ($DB->get_record('choice_table', array('choice_id' => $choice->id, 'student_id' => $event->userid),
                    $fields = '*', $strictness = IGNORE_MISSING) == false) { // If new choice then add to database.
                $choicedata = new \stdClass();
                $choicedata->student_id = $event->userid;
                $choicedata->choice_id = $choice->id;
                $choicedata->points_earned = get_config('leaderboard', 'choicepoints');
                $choicedata->time_finished = $event->timecreated;
                $choicedata->module_name = $choice->name;

                $DB->insert_record('choice_table', $choicedata);
            }
        }
    }

    // FORUM EVENTS.
    public static function forum_posted_handler(\mod_moodleoverflow\event\post_created $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            $forumdata = new \stdClass();
            $forumdata->student_id = $event->userid;
            $forumdata->forum_id = $event->other{'moodleoverflowid'};
            $forumdata->discussion_id = $event->other{'discussionid'};
            $forumdata->post_id = $event->objectid;
            $forumdata->is_response = true;
            $forumdata->points_earned = get_config('leaderboard', 'forumresponsepoints');
            $forumdata->time_finished = $event->timecreated;
            $forumdata->module_name = "Forum Response";

            $DB->insert_record('forum_table', $forumdata);
        }
    }

    public static function discussion_created_handler(\mod_moodleoverflow\event\discussion_created $event) {
        global $DB, $USER;
        if (user_has_role_assignment($USER->id, 5)) {
            $discussion = $DB->get_record('moodleoverflow_discussions',
                            array('id' => $event->objectid), $fields = '*', $strictness = IGNORE_MISSING);
            $forumdata = new \stdClass();
            $forumdata->student_id = $event->userid;
            $forumdata->forum_id = $discussion->moodleoverflow;
            $forumdata->post_id = $discussion->firstpost;
            $forumdata->discussion_id = $event->objectid;
            $forumdata->is_response = false;
            $forumdata->points_earned = get_config('leaderboard', 'forumpostpoints');
            $forumdata->time_finished = $event->timecreated;
            $forumdata->module_name = $discussion->name;

            $DB->insert_record('forum_table', $forumdata);
        }
    }
}