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
 * Last Updated: 9/21/18
 */

defined('MOODLE_INTERNAL') || die();

class block_leaderboard_functions{
    public static function get_date_range($courseid) {
        global $DB;
        $sql = "SELECT course.startdate,course.enddate
                FROM {course} course
                WHERE course.id = ?;";

        $course = $DB->get_record_sql($sql, array($courseid));

        $start = $course->startdate;
        $end = $course->enddate;
        if ($end == 0) {
            $end = (int)$start + 61516800;
        }

        $reset1ut = 0;
        $reset2ut = 0;

        $reset1 = get_config('leaderboard', 'reset1');
        $reset2 = get_config('leaderboard', 'reset2');

        if ($reset1 != ''  && $reset2 != '') {
            $reset1ut = strtotime($reset1);
            $reset2ut = strtotime($reset2);
        }

        if (time() < $reset1ut) {
            $end = $reset1ut;
        } else if (time() >= $reset1ut && time() < $reset2ut) {
            $start = $reset1ut;
            $end = $reset2ut;
        } else if (time() >= $reset2) {
            $start = $reset2ut;
        }

        $daterange = new stdClass();
        $daterange->start = $start;
        $daterange->end = $end;
        return $daterange;
    }

    public static function update_standing($groupdata, $currentstanding) {
        global $DB;
        // Table icon urls.
        $upurl = new moodle_url('/blocks/leaderboard/pix/up.svg');
        $downurl = new moodle_url('/blocks/leaderboard/pix/down.svg');
        $stayurl = new moodle_url('/blocks/leaderboard/pix/stay.svg');

        $move = substr($groupdata->paststanding, -2, 1); // 0 for up, 1 for down, 2 for stay.
        $initialposition = substr($groupdata->paststanding, -1);
        $paststanding = substr($groupdata->paststanding, 0, -2);
        $symbol = " ";

        if ($groupdata->time_updated < floor((time() - 7 * 60) / 86400)) {
            $initialposition = $paststanding;
        }
        if ($paststanding > $currentstanding) {
            $symbol = '<img src='.$upurl.'>';
            $move = 0;
        } else if ($paststanding < $currentstanding) {
            $symbol = '<img src='.$downurl.'>';
            $move = 1;
        } else if ($initialposition == $paststanding) {
            $symbol = '<img src='.$stayurl.'>';
            $move = 2;
        } else {
            if ($move == 0) {
                $symbol = '<img src='.$upurl.'>';
            } else if ($move == 1) {
                $symbol = '<img src='.$downurl.'>';
            } else if ($move == 2) {
                $symbol = '<img src='.$stayurl.'>';
            }
        }
        // Update the groups current standing.
        if ($groupdata->id) {
            $storedgroupdata = $DB->get_record('group_data_table',
                                    array('group_id' => $groupdata->id), $fields = '*', $strictness = IGNORE_MISSING);
            $storedgroupdata->currentstanding = (int)($currentstanding.$move.$initialposition);
            $DB->update_record('group_data_table', $storedgroupdata);
        }

        return $symbol;
    }
    public static function get_average_group_size($groups) {
        // Determine average group size.
        $numgroups = count($groups);
        $numstudents = 0;
        if ($numgroups > 0) {
            foreach ($groups as $group) {
                // Get each member of the group.
                $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');
                $numstudents += count($students);
            }
            $averagegroupsize = ceil($numstudents / $numgroups);
            return $averagegroupsize;
        } else {
            return 0;
        }
    }

    public static function get_group_data($group, $averagegroupsize, $start, $end) {
        global $DB, $USER;

        $pastweekpoints = 0;
        $pasttwoweekspoints = 0;
        $totalpoints = 0;
        $isusersgroup = false;

        // Add up all of the members points.
        $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');
        $studentsdata = [];
        foreach ($students as $student) {
            $points = self::get_points($student, $start, $end);
            $pastweekpoints += $points->pastweek;
            $pasttwoweekspoints += $points->pasttwoweeks;
            $totalpoints += $points->all;

            $studentdata = new stdClass();
            $studentdata->points = $points->all;
            $studentdata->history = $points->history;
            $studentdata->id = $student->id;
            $studentdata->firstname = $student->firstname;
            $studentdata->lastname = $student->lastname;
            $studentsdata[] = $studentdata;

            // Set to true if this student matches the current logged in $USER.
            if ($student->id === $USER->id) {
                $isusersgroup = true;
            }
        }

        // If the teams are not equal size make it a fair size.
        $groupsize = count($students);
        $bonuspoints = 0;

        if ($groupsize != $averagegroupsize) {
            $bonuspoints = $totalpoints / $groupsize * $averagegroupsize - $totalpoints;
            $pastweekpoints = $pastweekpoints / $groupsize * $averagegroupsize;
            $pasttwoweekspoints = $pasttwoweekspoints / $groupsize * $averagegroupsize;
            $totalpoints = $totalpoints / $groupsize * $averagegroupsize;
        }
        // Calculate the points per week.
        $pointsperweek = $pastweekpoints;
        $pointspertwoweeks = $pasttwoweekspoints / 2;

        // Take the one week rate if it is higher to account for slow weeks or fall/spring breaks.
        if ($pointsperweek > $pointspertwoweeks) {
            $pointsperweek = $pointsperweek;
        } else {
            $pointsperweek = $pointspertwoweeks;
        }
        $pointsperweek = round($pointsperweek);

        $storedgroupdata = $DB->get_record('group_data_table',
                                array('group_id' => $group->id), $fields = '*', $strictness = IGNORE_MISSING);
        if (!$storedgroupdata) {
            $storedgroupdata = new stdClass();
            $storedgroupdata->current_standing = 020;
            $storedgroupdata->multiplier = floor((time() - 7 * 60) / 86400);
            $storedgroupdata->group_id = $group->id;
            $DB->insert_record('group_data_table', $storedgroupdata);
        } else if (strlen((string)$storedgroupdata->current_standing) < 3) {
            $storedgroupdata->current_standing = (int)($storedgroupdata->current_standing.'2'.$storedgroupdata->current_standing);
            $storedgroupdata->group_id = $group->id;
            $DB->update_record('group_data_table', $storedgroupdata);
        }

        // Load the groups data into an object.
        $groupdata = new stdClass();
        $groupdata->name = $group->name;
        $groupdata->id = $group->id;
        $groupdata->paststanding = $storedgroupdata->current_standing;
        $groupdata->time_updated = $storedgroupdata->multiplier;
        $groupdata->points = $totalpoints;
        $groupdata->isusersgroup = $isusersgroup;
        $groupdata->pointsperweek = $pointsperweek;
        $groupdata->studentsdata = $studentsdata;
        $groupdata->bonuspoints = $bonuspoints;
        return $groupdata;
    }

    public static function get_points($student, $start, $end) {
        global $DB;

        // Create a new object.
        $points = new stdClass();
        $points->all = 0;
        $points->pastweek = 0;
        $points->pasttwoweeks = 0;
        $points->history = [];
        $studenthistory = [];

        // Add up student points for all points, past week, past two weeks, and fill student history array.

        // ACTIVITY.
        $sql = "SELECT assignment_table.*,assign.duedate
                FROM {assign_submission} assign_submission
                INNER JOIN {assignment_table} assignment_table ON assign_submission.id = assignment_table.activity_id
                INNER JOIN {assign} assign ON assign.id = assignment_table.activity_id
                WHERE assignment_table.activity_student = ?;";

        $studentactivities = $DB->get_records_sql($sql, array($student->id));
        $points = self::get_module_points($studentactivities, $start, $end, $points);

        // QUIZ.
        $sql = "SELECT quiz_table.*, quiz.timeclose
                FROM {quiz_table} quiz_table
                INNER JOIN {quiz} quiz ON quiz.id = quiz_table.quiz_id
                WHERE quiz_table.student_id = ? AND quiz_table.time_finished IS NOT NULL;";

        $studentquizzes = $DB->get_records_sql($sql, array($student->id));
        $points = self::get_module_points($studentquizzes, $start, $end, $points);

        // CHOICE.
        $studentchoices = $DB->get_records('choice_table', array('student_id' => $student->id));
        $points = self::get_module_points($studentchoices, $start, $end, $points);

        // FORUM.
        $studentforumposts = $DB->get_records('forum_table', array('student_id' => $student->id));
        $points = self::get_module_points($studentforumposts, $start, $end, $points);

        $studenthistory = $points->history;
        if (count($studenthistory) > 1) { // Only sort if there is something to sort.
            usort($studenthistory, function ($a, $b) {
                return $b->time_finished <=> $a->time_finished;
            });
        }
        $points->history = $studenthistory;

        return $points;
    }
    public static function rank_groups($groupdataarray) {
        $rankarray = [];
        $count = 1;
        $position = 1;
        for ($i = 0; $i < count($groupdataarray); $i++) {
            $position++;
            $rankarray[$i] = $count;
            if ($i < (count($groupdataarray) - 1)) {
                if ($groupdataarray[$i]->points != $groupdataarray[$i + 1]->points) {
                    $count = $position;
                }
            }
        }
        return $rankarray;
    }

    public static function get_module_points($list, $start, $end, $points) {
        $time = time();
        foreach ($list as $activity) {
            $duedate = $activity->time_finished;
            if (isset($activity->duedate)) {
                $duedate = $activity->duedate;
            } else if (isset($activity->timeclose)) {
                $duedate = $activity->timeclose;
            }

            if ($time >= $duedate && $duedate >= $start && $duedate <= $end && $activity->module_name != '') {
                $points->all += $activity->points_earned;
                if (($time - $activity->time_finished) / 86400 <= 7) {
                    $points->pastweek += $activity->points_earned;
                }
                if (($time - $activity->time_finished) / 86400 <= 14) {
                    $points->pasttwoweeks += $activity->points_earned;
                }
                $points->history[] = $activity;
            }
        }
        return $points;
    }
}

