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
 * Author: erikfox
 * Date Created: 5/22/18
 * Last Updated: 8/20/18
 */

// ASSIGNMENT.
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading(
    'assignmentheaderconfig',
    get_string('assignment_early_submission', 'block_leaderboard'),
    get_string('assignment_early_submission_desc', 'block_leaderboard')
));
for ($x = 5; $x >= 1; $x--) {
    $settings->add(new admin_setting_configtext(
        'leaderboard/assignmenttime'.$x,
        get_string('days_submitted_early', 'block_leaderboard'),
        '',
        $x
    ));
    if (get_config('leaderboard', 'assignmenttime'.$x) === '') {
        set_config('assignmenttime'.$x, $x, 'leaderboard');
    }

    $settings->add(new admin_setting_configtext(
        'leaderboard/assignmentpoints'.$x,
        get_string('points_earned', 'block_leaderboard'),
        '<br/>',
        $x * 5
    ));
    if (get_config('leaderboard', 'assignmentpoints'.$x) === '') {
        set_config('assignmentpoints'.$x, $x * 5, 'leaderboard');
    }
}

// QUIZ.
$settings->add(new admin_setting_heading(
    'quizheaderconfig',
    get_string('quiz_early_submission', 'block_leaderboard'),
    get_string('quiz_early_submission_desc', 'block_leaderboard')
));
for ($x = 5; $x >= 1; $x--) {
    $settings->add(new admin_setting_configtext(
        'leaderboard/quiztime'.$x,
        get_string('days_submitted_early', 'block_leaderboard'),
        '',
        $x
    ));
    if (get_config('leaderboard', 'quiztime'.$x) === '') {
        set_config('quiztime'.$x, $x, 'leaderboard');
    }

    $settings->add(new admin_setting_configtext(
        'leaderboard/quizpoints'.$x,
        get_string('points_earned', 'block_leaderboard'),
        '<br/>',
        $x * 2
    ));
    if (get_config('leaderboard', 'quizpoints'.$x) === '') {
        set_config('quizpoints'.$x, $x * 2, 'leaderboard');
    }
}
$settings->add(new admin_setting_heading(
    'quizheaderconfig2',
    get_string('quiz_spacing', 'block_leaderboard'),
    get_string('quiz_spacing_desc', 'block_leaderboard')
));

$vals = array(round(1 / 48, 2), 1 / 2, 1);
for ($x = 3; $x >= 1; $x--) {
    $settings->add(new admin_setting_configtext(
        'leaderboard/quizspacing'.$x,
        get_string('days_between_quizzes', 'block_leaderboard'),
        '',
        $vals[$x - 1]
    ));
    if (get_config('leaderboard', 'quizspacing'.$x) === '') {
        set_config('quizspacing'.$x, $vals[$x - 1], 'leaderboard');
    }

    $settings->add(new admin_setting_configtext(
        'leaderboard/quizspacingpoints'.$x,
        get_string('points_earned', 'block_leaderboard'),
        '<br/>',
        $x * 2
    ));
    if (get_config('leaderboard', 'quizspacingpoints'.$x) === '') {
        set_config('quizspacingpoints'.$x, $x * 5, 'leaderboard');
    }
}
$settings->add(new admin_setting_heading(
    'quizheaderconfig3',
    get_string('quiz_attempts', 'block_leaderboard'),
    get_string('quiz_attempts_desc', 'block_leaderboard')
));
$settings->add(new admin_setting_configtext(
    'leaderboard/quizattempts',
    get_string('number_of_attempts', 'block_leaderboard'),
    '',
    3
));
if (get_config('leaderboard', 'quizattempts') === '') {
    set_config('quizattempts', 3, 'leaderboard');
}

$settings->add(new admin_setting_configtext(
    'leaderboard/quizattemptspoints',
    get_string('points_earned', 'block_leaderboard'),
    '<br/>',
    2
));
if (get_config('leaderboard', 'quizattemptspoints') === '') {
    set_config('quizattemptspoints', 2, 'leaderboard');
}

// CHOICE.
$settings->add(new admin_setting_heading(
'choiceheaderconfig',
get_string('choice_settings', 'block_leaderboard'),
get_string('choice_settings_desc', 'block_leaderboard')
));

$settings->add(new admin_setting_configtext(
    'leaderboard/choicepoints',
    get_string('label_choice_points', 'block_leaderboard'),
    get_string('desc_choice_points', 'block_leaderboard'),
    5
));
if (get_config('leaderboard', 'choicepoints') === '') {
    set_config('choicepoints', 5, 'leaderboard');
}

// FORUM.
$settings->add(new admin_setting_heading(
    'forumheaderconfig',
    get_string('forum_settings', 'block_leaderboard'),
    get_string('forum_settings_desc', 'block_leaderboard')
));

$settings->add(new admin_setting_configtext(
    'leaderboard/forumpostpoints',
    get_string('label_forum_post_points', 'block_leaderboard'),
    get_string('desc_forum_post_points', 'block_leaderboard'),
    1
));
if (get_config('leaderboard', 'forumpostpoints') === '') {
    set_config('forumpostpoints', 1, 'leaderboard');
}

$settings->add(new admin_setting_configtext(
    'leaderboard/forumresponsepoints',
    get_string('label_forum_response_points', 'block_leaderboard'),
    get_string('desc_forum_response_points', 'block_leaderboard'),
    2
));
if (get_config('leaderboard', 'forumresponsepoints') === '') {
    set_config('forumresponsepoints', 2, 'leaderboard');
}

// MISC.
$settings->add(new admin_setting_heading(
    'resetheaderconfig',
    get_string('reset_settings', 'block_leaderboard'),
    get_string('reset_settings_desc', 'block_leaderboard')
));
$settings->add(new admin_setting_configtext(
    'leaderboard/reset1',
    get_string('label_reset1', 'block_leaderboard'),
    get_string('desc_reset1', 'block_leaderboard'),
    1
));

$settings->add(new admin_setting_configtext(
    'leaderboard/reset2',
    get_string('label_reset2', 'block_leaderboard'),
    get_string('desc_reset2', 'block_leaderboard'),
    2
));

$settings->add(new admin_setting_heading(
    'glossaryheaderconfig',
    get_string('glossary_settings', 'block_leaderboard'),
    get_string('glossary_settings_desc', 'block_leaderboard')
));

$settings->add(new admin_setting_heading(
    'mischeaderconfig',
    get_string('other_settings', 'block_leaderboard'),
    get_string('other_settings_desc', 'block_leaderboard')
));



