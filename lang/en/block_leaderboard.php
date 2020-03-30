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

/**
 * Created by PhpStorm.
 * User: erikfox
 * Date: 5/22/18
 * Time: 11:22 PM
 */
defined("TAB1") or define("TAB1", "\t");

// Block name.
$string['pluginname'] = 'Leaderboard';
$string['leaderboard'] = 'Leaderboard';

// Settings strings.
$string['assignmentearlysubmission'] = 'Assignments Early Submission';
$string['assignmentearlysubmission_desc'] = 'Set the points awarded for submitting an assignment early. (Order from most to least days early)';

$string['assignmenttestsearlysubmission'] = 'Assignments Tests Early Submission';
$string['assignmenttestsearlysubmission_desc'] = 'Set the points awarded for passing an assignment test early. (Order from most to least days early)';

$string['quizearlysubmission'] = 'Quiz Early Submission';
$string['quizearlysubmission_desc'] = 'Set the points awarded for submitting a quiz early. (Order from most to least days early)';

$string['quizspacing'] = 'Quiz Spacing';
$string['quizspacing_desc'] = 'Set the points awarded for spacing out quizzes. (Order from most to least days spaced)';

$string['quizattempts'] = 'Quiz Attempts';
$string['quizattempts_desc'] = 'Set the points awarded for retaking quizzes. (Order from most to least retakes)';

$string['choicesettings'] = 'Choice Settings';
$string['choicesettings_desc'] = 'Set points values and how those points values are assigned.';

$string['forum_settings'] = 'Forum Settings';
$string['forum_settings_desc'] = 'Set points values and how those points values are assigned.';

$string['glossarysettings'] = 'Glossary Settings';
$string['glossarysettings_desc'] = 'Set points values and how those points values are assigned.';

$string['othersettings'] = 'Other Settings';
$string['othersettings_desc'] = 'Set points values and how those points values are assigned.';

$string['multipliersettings'] = 'Multiplier Settings';
$string['multipliersettings_desc'] = 'Set the points required to reach the next level and multiplier. (Order from highest to lowest level)';

$string['dayssubmittedearly'] = 'Days Submitted Early';
$string['pointsearned'] = 'Pointes Earned';

$string['daysbetweenquizzes'] = 'Days Between Quizzes';
$string['numberofattempts'] = 'Number of Attempts';

$string['label_choicepoints'] = 'Choice Points';
$string['desc_choicepoints'] = 'Points earned for participating in a choice';

$string['label_forumpostpoints'] = 'Post Points';
$string['desc_forumpostpoints'] = 'Points earned for posting a question';

$string['label_forumresponsepoints'] = 'Response Points';
$string['desc_forumresponsepoints'] = 'Points earned for responding to a question';

$string['label_forumpostmaxpoints'] = 'Post Max Points';
$string['desc_forumpostmaxpoints'] = 'The maximum points that can be earned for posting questions';

$string['label_forumresponsemaxpoints'] = 'Response Max Points';
$string['desc_forumresponsemaxpoints'] = 'The maximum points that can be earned for responding to questions';

//future project: put course dates here?
$string['resetsettings'] = 'Leaderboard Reset Settings';
$string['resetsettings_desc'] = 'Set the dates when you want the leaderboard to reset. '
        . 'If they occur before or after the official course start or end dates, '
        . 'they will automatically be reset to the start or end date.';

$string['label_reset1'] = 'First Reset';
$string['desc_reset1'] = 'Type using format MM/DD/YYYY';

$string['label_reset2'] = 'Second Reset';
$string['desc_reset2'] = 'Type using format MM/DD/YYYY';

// Block strings.
$string['rankings'] = 'Rankings';
$string['num'] = '#';
$string['group'] = 'Group';
$string['points'] = 'Points';
$string['viewfullleaderboard'] = 'View Full Leaderboard';

// Full leaderboard page strings.
$string['leaderboard'] = 'Leaderboard';
$string['rank'] = 'Rank';
$string['name'] = 'Name';
$string['downloaddata'] = 'Download Data';
$string['extrapoints'] = 'Extra Points';
$string['nogroupsfound'] = 'No Groups Found';
$string['info'] = 'Info';
$string['description'] = 'On this Page you can see your full teams points breakdown as well as each of your individual contributions.';

$string['start'] = 'Start';
$string['end'] = 'End';
$string['update'] = 'Update';
$string['resettodefault'] = 'Reset To Default';
$string['changedaterange'] = 'Change Date Range';

$string['QA'] = 'Q/A';
$string['q0'] = '<strong>Q0</strong>: What if my team is smaller than other teams? Will we be at a disadvantage?';
$string['a0'] = '<strong>A0</strong>: If your team happens to be smaller than the average team size, don\'t worry, you will get extra points based on your teams average points per person. These will be displayed as "Extra Points" in the table.';
$string['q1'] = '<strong>Q1</strong>: What are ways that we can earn points?';
$string['a1'] = '<strong>A1</strong>: Your team can earn points by practicing good study habits. Turning in assignments early and not completing unit tests all at once will award the most points. Other ways of earning points include completing quizzes early,spacing out quizzes instead of cramming them into one, retaking quizzes for extra practice, posting and responding to questions on the forum, and even rating your understanding in the choice module will all award points.';
$string['q2'] = '<strong>Q2</strong>: What are the exact point breakdowns?';
$string['a2'] = '<strong>A2</strong>: Here is a breakdown of the points before multipliers:';
$string['a22'] = 'Finish Assignments <strong>'.get_config('leaderboard', 'assignmenttime5').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmentpoints5').'</strong> points,
                    <br/>Finish Assignments <strong>'.get_config('leaderboard', 'assignmenttime4').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmentpoints4').'</strong> points,
                    <br/>Finish Assignments <strong>'.get_config('leaderboard', 'assignmenttime3').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmentpoints3').'</strong> points,
                    <br/>Finish Assignments <strong>'.get_config('leaderboard', 'assignmenttime2').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmentpoints2').'</strong> points,
                    <br/>Finish Assignments <strong>'.get_config('leaderboard', 'assignmenttime1').'</strong> day early to earn <strong>'.get_config('leaderboard', 'assignmentpoints1').'</strong> points.
                    <br/><br/>Submit Assignment Unit Test <strong>'.get_config('leaderboard', 'assignmentteststime5').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmenttestspoints5').'</strong> points,
                    <br/>Submit Assignments Unit Test <strong>'.get_config('leaderboard', 'assignmentteststime4').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmenttestspoints4').'</strong> points,
                    <br/>Submit Assignments Unit Test <strong>'.get_config('leaderboard', 'assignmentteststime3').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmenttestspoints3').'</strong> points,
                    <br/>Submit Assignments Unit Test <strong>'.get_config('leaderboard', 'assignmentteststime2').'</strong> days early to earn <strong>'.get_config('leaderboard', 'assignmenttestspoints2').'</strong> points,
                    <br/>Submit Assignments Unit Test <strong>'.get_config('leaderboard', 'assignmentteststime1').'</strong> day early to earn <strong>'.get_config('leaderboard', 'assignmenttestspoints1').'</strong> points.                    
                    <br/><br/>Submit Quizzes <strong>'.get_config('leaderboard', 'quiztime5').'</strong> days early to earn <strong>'.get_config('leaderboard', 'quizpoints5').'</strong> points,
                    <br/>Submit Quizzes <strong>'.get_config('leaderboard', 'quiztime4').'</strong> days early to earn <strong>'.get_config('leaderboard', 'quizpoints4').'</strong> points,
                    <br/>Submit Quizzes <strong>'.get_config('leaderboard', 'quiztime3').'</strong> days early to earn <strong>'.get_config('leaderboard', 'quizpoints3').'</strong> points,
                    <br/>Submit Quizzes <strong>'.get_config('leaderboard', 'quiztime2').'</strong> days early to earn <strong>'.get_config('leaderboard', 'quizpoints2').'</strong> points,
                    <br/>Submit Quizzes <strong>'.get_config('leaderboard', 'quiztime1').'</strong> day early to earn <strong>'.get_config('leaderboard', 'quizpoints1').'</strong> points.
                    <br/><br/>Take <strong>'.get_config('leaderboard', 'quizspacing3').'</strong> days between Quizzes to earn <strong>'.get_config('leaderboard', 'quizspacingpoints3').'</strong> points,
                    <br/>Take <strong>'.get_config('leaderboard', 'quizspacing2').'</strong> days between Quizzes to earn <strong>'.get_config('leaderboard', 'quizspacingpoints2').'</strong> points,
                    <br/>Take <strong>'.get_config('leaderboard', 'quizspacing1').'</strong> days between Quizzes to earn <strong>'.get_config('leaderboard', 'quizspacingpoints1').'</strong> points.
                    <br/><br/>Attempt Quizzes up to <strong>'.get_config('leaderboard', 'quizattempts').'</strong> times to earn <strong>'.get_config('leaderboard', 'quizattemptspoints').'</strong> points each attempt.
                    <br/><br/>Earn <strong>'.get_config('leaderboard', 'forumpostpoints').'</strong> points for posting in a Forum
                    <br/>Earn <strong>'.get_config('leaderboard', 'forumresponsepoints').'</strong> points for responding to a Forum
                    <br/><br/>Earn <strong>'.get_config('leaderboard', 'choicepoints').'</strong> points for participating in a Choice';
$string['q6'] = '<strong>Q3</strong>: I completed a bunch of unit tests all at once, why did I only get one unit test worth of points for all of them?';
$string['a6'] = '<strong>A3</strong>: This is intentional. Good programming practice has you completing one unit test at a time. Thus whenever you submit a commit that successfully raises the number of passed tests you have for an assignment, you get the same number of points, without consideration to the number of passed tests. To maximize your point gain you must complete one test per commit.';
$string['q7'] = '<strong>Q4</strong>: I submitted an assignment early, why don\'t I see any points?';
$string['a7'] = '<strong>A4</strong>: This is normal. Your points will not be recorded in the leaderboard until after the due date for an assignment or quiz has passed. This is in place to so that an assignment cannot earn points for submission both before and after a reset. Furthermore, the system can be slow to process points when lots of people are submitting their work. Wait an hour or two first, and if it has not cleared up by then send us a message. Alternatively, if you turned in an assignment without completing any tests you will not get any points for it, to prevent scenarios of awarding points for no work';
$string['q8'] = '<strong>Q5</strong>: Is my data being logged?';
$string['a8'] = '<strong>A5</strong>: Yes. This data is being used for research purposes to track in class study habits and how to improve them. However your names and personal data are not attached to any of the data being logged.';
