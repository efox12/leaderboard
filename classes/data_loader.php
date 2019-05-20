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
 * Last Updated: 8/21/18
 */

require_once('../../../config.php'); // Specify path to moodle /config.php file.
require_login(); // Require valid moodle login.  Will redirect to login page if not logged in.
$cid = required_param('id', PARAM_INT);
$start = required_param('start', PARAM_RAW);
$end = required_param('end', PARAM_RAW);

$url = new moodle_url('/blocks/leaderboard/classes/data_loader.php', array('id' => $cid));
$PAGE->set_url($url);

global $DB, $COURSE;
$csv[0] = array(
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
$groups = $DB->get_records('groups', array('courseid' => $cid));
foreach ($groups as $group) {
    // Get each member of the group.
    $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');
    foreach ($students as $student) {
        // Get each members past contributions and add them to an array.
        $studentactivities = $DB->get_records('assignment_table', array('activity_student' => $student->id));
        foreach ($studentactivities as $activity) {
            if ($activity->time_finished >= $start && $activity->time_finished <= $end) {
                $csv[$count] = array(
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
        }
        $studentquizzes = $DB->get_records('quiz_table', array('student_id' => $student->id));
        foreach ($studentquizzes as $quiz) {
            if ($quiz->time_finished >= $start && $quiz->time_finished <= $end) {
                $csv[$count] = array(
                    $quiz->student_id,
                    $quiz->quiz_id,
                    $quiz->module_name,
                    'Quiz',
                    $quiz->time_finished,
                    $quiz->days_early,
                    $quiz->attempts,
                    round($quiz->days_spaced, 2),
                    'null',
                    'null',
                    'null'
                );
                $count++;
            }
        }
        $studentchoices = $DB->get_records('choice_table', array('student_id' => $student->id));
        foreach ($studentchoices as $choice) {
            if ($choice->time_finished >= $start && $choice->time_finished <= $end) {
                $csv[$count] = array(
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
        }
        $studentforumposts = $DB->get_records('forum_table', array('student_id' => $student->id));
        foreach ($studentforumposts as $post) {
            if ($post->time_finished >= $start && $post->time_finished <= $end) {
                $csv[$count] = array(
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
}

global $CFG;
require_once($CFG->libdir . '/csvlib.class.php');
$filename = clean_filename('data');
$csvexport = new csv_export_writer();
$csvexport->set_filename($filename);
foreach ($csv as $line) {
    $csvexport->add_data($line);
}
$csvexport->download_file();
die;