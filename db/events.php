<?php
/*
 * Author: Erik Fox
 * Date Created: 5/22/18
 * Last Updated: 8/20/18
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
//Assignment Events
    array (
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback'  => 'block_leaderboard_observer::assignment_submitted_handler',
    ),

//Quiz Events
    //array (
    //    'eventname' => '\mod_quiz\event\attempt_abandoned',
    //    'callback'  => 'block_leaderboard_observer::quiz_abandoned_handler',
    //),
    array (
        'eventname' => '\mod_quiz\event\attempt_started',
        'callback'  => 'block_leaderboard_observer::quiz_started_handler',
    ),
    array (
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback'  => 'block_leaderboard_observer::quiz_submitted_handler',
    ),
    //array (
    //    'eventname' => '\mod_quiz\event\attempt_viewed',
    //    'callback'  => 'block_leaderboard_observer::quiz_viewed_handler',
    //),
    array (
        'eventname' => '\mod_quiz\event\course_module_viewed',
        'callback'  => 'block_leaderboard_observer::quizmodule_viewed_handler',
    ),
    array (
        'eventname' => '\mod_quiz\event\attempt_becameoverdue',
        'callback'  => 'block_leaderboard_observer::quiz_overdue_handler',
    ),

//Forum Events
    /*array (
        'eventname' => '\mod_forum\event\post_created',
        'callback'  => 'block_leaderboard_observer::forum_posted_handler',
    ),
    array (
        'eventname' => '\mod_forum\event\discussion_created',
        'callback'  => 'block_leaderboard_observer::discussion_created_handler',
    ),*/
    /*array (
        'eventname' => '\mod_hsuforum\event\post_created',
        'callback'  => 'block_leaderboard_observer::forum_posted_handler',
    ),
    array (
        'eventname' => '\mod_hsuforum\event\discussion_created',
        'callback'  => 'block_leaderboard_observer::discussion_created_handler',
    ),*/

        //Was Found Helpful	

    array (
        'eventname' => '\mod_moodleoverflow\event\post_created',
        'callback'  => 'block_leaderboard_observer::forum_posted_handler',
    ),
    array (
        'eventname' => '\mod_moodleoverflow\event\discussion_created',
        'callback'  => 'block_leaderboard_observer::discussion_created_handler',
    ),
//Choice Events
    array (
        'eventname' => '\mod_choice\event\answer_created',
        'callback'  => 'block_leaderboard_observer::choice_submitted_handler',
    ),
        //Updated Choice

//Glossary Events
        //Created Entry

//Video Events
        //Watched Video
        //Reviewed Video

//Lecture Note Events
        //Reviewed Notes

);
