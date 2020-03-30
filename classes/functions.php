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
    /**
     * Gets the number of points earned given a number of days submitted early.
     *
     * @param int $daysbeforesubmission The number days submitted early.
     * @param string $type Either 'assignment' or 'quiz' indicating which point scale to look at.
     * @return int The number of points earned.
     */
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

    /**
     * Gets the number of points earned given a number of quiz attempts.
     *
     * @param int $attempts The number of attempts.
     * @return int The number of points earned.
     */
    public static function get_quiz_attempts_points($attempts) {
        $maxattempts = get_config('leaderboard', 'quizattempts');
        if ($attempts == 0) {
            return 0;
        } else if ($attempts <= $maxattempts) {
            return get_config('leaderboard', 'quizattemptspoints') * ($attempts - 1);
        }
        return get_config('leaderboard', 'quizattemptspoints') * ($maxattempts);
    }
    /**
     * Gets the number of points earned given an amount of time spaced since the last quiz.
     *
     * @param float $quizspacing The amount of time spaced in days.
     * @return int The number of points earned.
     */
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
    /**
     * Gets a the current range of dates the leaderboard will look at and indicates the range with a start and end in unix time.
     * @param int $coursid The id of the current course.
     * @return stdClass An object with a range indicated by integer values 'start' and 'end'.
     */
    public static function get_date_range($courseid) {
        global $DB;
        $sql = "SELECT course.startdate,course.enddate
                FROM {course} course
                WHERE course.id = ?;";

        $course = $DB->get_record_sql($sql, array($courseid));

        //get course start and end date
        $start = $course->startdate;
        $end = $course->enddate;
        if ($end == 0) {
            $end = (int)$start + 61516800;
        }

        $reset1ut = 0;
        $reset2ut = 0;

        //get reset times
        $reset1 = get_config('leaderboard', 'reset1');
        $reset2 = get_config('leaderboard', 'reset2');

        if ($reset1 != ''  && $reset2 != '') {
            $reset1ut = strtotime($reset1);
            $reset2ut = strtotime($reset2);
        }
        
        //if there are resets outside of the course time dates, resets them
        if($reset1ut < $start || $reset1ut > $end) {
            $reset1ut = $start;
        }
        if($reset2ut < $start || $reset2ut > $end) {
            $reset2ut = $end;
        }        
        //automatically handles error of accidentally flipping the reset order
        //by flipping the resets
        if($reset2ut < $reset1ut) {
            $temp = $reset2ut;
            $reset2ut = $reset1ut;
            $reset1ut = $reset2ut;
        }
        
        //find the start and end time
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

    /**
     * Updates a groups standing in the leaderboard indicating whether they moved up, down, or stayed.
     * @param stdClass $groupdata Various data about the group
     * @param int $currentstanding The groups current standing.
     * @return string An html element with the image for the appropriate icon to display.
     */
    public static function update_standing($groupdata, $currentstanding) {
        global $DB;
        // Table icon urls.
        $upurl = new moodle_url('/blocks/leaderboard/pix/up.svg');
        $downurl = new moodle_url('/blocks/leaderboard/pix/down.svg');
        $stayurl = new moodle_url('/blocks/leaderboard/pix/stay.svg');

        $move = $groupdata->lastmove; // 0 for up, 1 for down, 2 for stay.
        $paststanding = $groupdata->currentstanding;
        $symbol = " ";
        if (strlen((string)$groupdata->time_updated) > 8) {
            $groupdata->time_updated = (int)date("Ymd");
        }
        if ($paststanding === null) {
            $paststanding = $currentstanding;
            $move = 2;
        }

        if ($groupdata->time_updated < (int)date("Ymd")) {
            // Use neutral visual queue if a group hasn't moved standings in a set amount of time.
            if ($currentstanding == $paststanding) {
                $symbol = '<img src='.$stayurl.'>';
                $move = 2;
            }
            $groupdata->time_updated = (int)date("Ymd");
        }
        // Only allow change in visual queues for moving up or down when page loads.
        if ($paststanding > $currentstanding) {
            $symbol = '<img src='.$upurl.'>';
            $move = 0;
        } else if ($paststanding < $currentstanding) {
            $symbol = '<img src='.$downurl.'>';
            $move = 1;
        } else { // Otherwise look at the last move taken by the group.
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
            $storedgroupdata = $DB->get_record('block_leaderboard_group_data',
                                    array('groupid' => $groupdata->id), $fields = '*', $strictness = IGNORE_MISSING);
            $storedgroupdata->currentstanding = $currentstanding;
            $storedgroupdata->lastmove = $move;
            $storedgroupdata->timeupdated = $groupdata->time_updated;
            $DB->update_record('block_leaderboard_group_data', $storedgroupdata);
        }

        return $symbol;
    }

    /**
     * Calculates the average group size based on a list of groups.
     * @param array $groups A list of groups.
     * @return int The average number of students per group.
     */
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
    
        /**
     * Calculates the max group size based on a list of groups.
     * @param array $groups A list of groups.
     * @return int The max number of students per group.
     */
    public static function get_max_group_size($groups) {
        // Determine average group size.
        $numgroups = count($groups);
        $maxgroupsize = 0;
        
        if ($numgroups > 0) {
            foreach ($groups as $group) {
                // Get each member of the group.
                $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');
                if(count($students) > $maxgroupsize) {
                    $maxgroupsize = count($students);
                }
            }
            return $maxgroupsize;
        } else {
            return 0;
        }
    }

    /**
     * Gets all of the data about a group of students during a specific date range.
     * @param stdClass $group A group of students.
     * @param int $maxgroupsize The max number of students per group.
     * @param int $start A unit timestamp.
     * @param int $end A unix timestamp.
     * @return stdClass All important data about a group of students.
     */
    public static function get_group_data($group, $maxgroupsize, $start, $end) {
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

        // If the teams less than max size level the playing field.
        $groupsize = count($students);
        $bonuspoints = 0;

        if ($groupsize < $maxgroupsize) {
            $bonuspoints = (($totalpoints / $groupsize) * $maxgroupsize) - $totalpoints;
            $pastweekpoints = $pastweekpoints / $groupsize * $maxgroupsize;
            $pasttwoweekspoints = $pasttwoweekspoints / $groupsize * $maxgroupsize;
            $totalpoints = $totalpoints / $groupsize * $maxgroupsize;
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

        $storedgroupdata = $DB->get_record('block_leaderboard_group_data',
                                array('groupid' => $group->id), $fields = '*', $strictness = IGNORE_MISSING);
        if (!$storedgroupdata) {
            $storedgroupdata = new stdClass();
            $storedgroupdata->currentstanding = 0;
            $storedgroupdata->lastmove = 2;
            $storedgroupdata->timeupdated = floor((time() - 7 * 60) / 86400);
            $storedgroupdata->groupid = $group->id;
            $DB->insert_record('block_leaderboard_group_data', $storedgroupdata);
        } else if (strlen((string)$storedgroupdata->currentstanding) < 3) {
            $storedgroupdata->currentstanding = $storedgroupdata->currentstanding;
            $storedgroupdata->groupid = $group->id;
            $DB->update_record('block_leaderboard_group_data', $storedgroupdata);
        }

        // Load the groups data into an object.
        $groupdata = new stdClass();
        $groupdata->name = $group->name;
        $groupdata->id = $group->id;
        $groupdata->currentstanding = $storedgroupdata->currentstanding;
        $groupdata->lastmove = $storedgroupdata->lastmove;
        $groupdata->time_updated = $storedgroupdata->timeupdated;
        $groupdata->points = $totalpoints;
        $groupdata->isusersgroup = $isusersgroup;
        $groupdata->pointsperweek = $pointsperweek;
        $groupdata->studentsdata = $studentsdata;
        $groupdata->bonuspoints = $bonuspoints;
        return $groupdata;
    }

    /**
     * Gets all of the points for a student during a specific date range.
     * @param stdClass $student A student and it's data.
     * @param int $start A unix timestamp.
     * @param int $end A unix timestamp.
     * @return stdClass An object with data about points earned by the student.
     */
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
        //FIXME to prevent assignment from different course from being considered.
          $sql = "SELECT block_leaderboard_assignment.*, assign.duedate
                  FROM {block_leaderboard_assignment} block_leaderboard_assignment
                  INNER JOIN {assign} assign ON assign.name = block_leaderboard_assignment.modulename
                  WHERE block_leaderboard_assignment.studentid = ? AND assign.duedate >= ? AND assign.duedate <= ? 
                  AND block_leaderboard_assignment.timefinished >= assign.allowsubmissionsfromdate ;";

        $studentactivities = $DB->get_records_sql($sql, array($student->id, $start, $end));        
        $pointsdata = self::get_module_points($studentactivities, $start, $end);
        $points->all += $pointsdata->all;
        $points->pastweek += $pointsdata->pastweek;
        $points->pasttwoweeks += $pointsdata->pasttwoweeks;
        $points->history = $pointsdata->history;

        // QUIZ.
        $sql = "SELECT block_leaderboard_quiz.*, quiz.timeclose
                FROM {block_leaderboard_quiz} block_leaderboard_quiz
                INNER JOIN {quiz} quiz ON quiz.id = block_leaderboard_quiz.quizid
                WHERE block_leaderboard_quiz.studentid = ? AND block_leaderboard_quiz.timefinished IS NOT NULL;";

        $studentquizzes = $DB->get_records_sql($sql, array($student->id));
        $pointsdata = self::get_module_points($studentquizzes, $start, $end);
        $points->all += $pointsdata->all;
        $points->pastweek += $pointsdata->pastweek;
        $points->pasttwoweeks += $pointsdata->pasttwoweeks;
        $points->history += array_merge($points->history, $pointsdata->history);

        // CHOICE.
        $studentchoices = $DB->get_records('block_leaderboard_choice', array('studentid' => $student->id));
        $pointsdata = self::get_module_points($studentchoices, $start, $end);
        $points->all += $pointsdata->all;
        $points->pastweek += $pointsdata->pastweek;
        $points->pasttwoweeks += $pointsdata->pasttwoweeks;
        $points->history += array_merge($points->history, $pointsdata->history);

        // FORUM.
        $studentforumposts = $DB->get_records('block_leaderboard_forum', array('studentid' => $student->id));
        $pointsdata = self::get_module_points($studentforumposts, $start, $end);
        $points->all += self::get_forum_points_with_max($pointsdata->history);
        $points->pastweek += $pointsdata->pastweek;
        $points->pasttwoweeks += $pointsdata->pasttwoweeks;
        $points->history = array_merge($points->history, $pointsdata->history);

        $studenthistory = $points->history;
        if (count($studenthistory) > 1) { // Only sort if there is something to sort.
            usort($studenthistory, function ($a, $b) {
                return $b->timefinished <=> $a->timefinished;
            });
        }
        $points->history = $studenthistory;
        return $points;
    }
    
    /*
     * Gets the total forum points, maxed out by requirements in settings
     * Possibility of adding more maxes for other categories
     * @params array $history. Contains all forum posts/responses for given student
     * @return int $totalpoints. Returns points earned
     */
    public static function get_forum_points_with_max($history) {
        $maxresponse = get_config('leaderboard', 'forumresponsemaxpoints');
        $maxpost = get_config('leaderboard', 'forumpostmaxpoints');
        $responsepoints = 0;
        $postpoints = 0;
        
        foreach($history as $forum) {
            //check property exists
            if(property_exists($forum, "isresponse") && property_exists($forum, "pointsearned")) {
                if($forum->isresponse == true) {
                    $pointsearned = round($forum->pointsearned);
                    while($responsepoints < $maxresponse && $pointsearned > 0) {
                        $responsepoints++;
                        $pointsearned--;
                    }                  
                }
                else {
                    $pointsearned = round($forum->pointsearned);
                    while($postpoints < $maxpost && $pointsearned > 0) {
                        $postpoints++;
                        $pointsearned--;
                    }     
                }
            }
        }
        $totalpoints = $postpoints + $responsepoints;
        return $totalpoints;
    }

    /**
     * Rankes the sorted groups accounting for ties.
     * @param array $groupdataarray An array of group data sorted from most to least points earned.
     * @return array An array of ranks corresponding by index to each group in $groupdataarray.
     */
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

    /**
     * Gets information on all points for a specific module given a date range.
     * @param array $coursid The id of the current course.
     * @param int $start A unix timestamp.
     * @param int $end A unix timestamp.
     * @param stdClass $points An object containing running data about points information from other sources
     * @return stdClass An object with all points information from this source added on to it.
     */
    public static function get_module_points($list, $start, $end) {
        $points = new stdClass;
        $points->all = 0;
        $points->pastweek = 0;
        $points->pasttwoweeks = 0;
        $points->history = [];
        $time = time();
        foreach ($list as $activity) {
            $duedate = $activity->timefinished;
            if (isset($activity->duedate)) {
                $duedate = $activity->duedate;
            } else if (isset($activity->timeclose)) {
                $duedate = $activity->timeclose;
            }
            //$time >= $duedate && 
            if ($duedate >= $start && $duedate <= $end && $activity->modulename != '') {
                $points->all += $activity->pointsearned;
                if (($time - $activity->timefinished) / 86400 <= 7) {
                    $points->pastweek += $activity->pointsearned;
                }
                if (($time - $activity->timefinished) / 86400 <= 14) {
                    $points->pasttwoweeks += $activity->pointsearned;
                }
                $points->history[] = $activity;
            }
        }
        return $points;
    }

    /**
     * Alternate assignment_submitted handler for when assignments are submitted to github
     * Looks through all commits from the block_leaderboard_travis_builds table for assignments 
     * due in a given date range and updates the block_leaderboard_assignment table accordingly.
     * 
     * 
     * travis table:
     * | id | build_id | commit_timestamp | committer_email | github_assignment_acceptor | 
     *  commit_message | pa | organization_name | total_tests | passed_tests |
     *
     * 
     * @param $startdate = the start of the time period for data to compute.
     * @param $enddate = the end of the time period for data to compute.
     * @param $courseid = the id of the course we want
     * @return void
     */
    public static function update_assignment_submitted_github($start, $end, $courseid) {
        global $DB;
       
        // Get all commits from course
        $all_assignments = $DB->get_records('assign', array('course' => $courseid));
        $commits = array();
        
        // For all assignments, if it is within the given due date, then it will
        // call a function that will search and add all commits associated with
        // that assignment to $commits        
        //FIXME - make strickter requirements (courseid?)
        foreach($all_assignments as $assignment) {
            if($assignment->duedate >= $start && $assignment->duedate <= $end) {
                $commits = self::select_travis_commits($commits, $assignment->name);
            }
        }
        //FIXME problem is making assignments for not just 1 but 2 courses.
        //get assignment code should shut down any non courseid stuff
        //possibility code came from loading other course though
        //
//        echo("<script>console.log(". json_encode('all assignments:', JSON_HEX_TAG) .");</script>");
//        echo("<script>console.log(". json_encode($all_assignments, JSON_HEX_TAG) .");</script>");

//        echo("<script>console.log(". json_encode('all commits:', JSON_HEX_TAG) .");</script>");
//        $all_commits = $DB->get_records('block_leaderboard_travis_builds');
//        echo("<script>console.log(". json_encode($all_commits, JSON_HEX_TAG) .");</script>");
//        echo("<script>console.log(". json_encode('commits:', JSON_HEX_TAG) .");</script>");
//        echo("<script>console.log(". json_encode($commits, JSON_HEX_TAG) .");</script>");
        
        // Gets all commits that are for assignments within the given range
        // Commits must be in order time wise for this to work properly
        // WHERE block_leaderboard_travis_builds.pa IN ({implode(',', $assignments)})            
        
        foreach($commits as $commit) {
            //Gets user 
            $user = $DB->get_record_sql('SELECT * FROM {user} '
                    . 'WHERE ' . $DB->sql_compare_text('description') . ' = ?', 
                    array('description' => $commit->github_assignment_acceptor));
            
            //specifically checks if user is a student. if not, nothing happens.
            if ($user && user_has_role_assignment($user->id, 5)) { //in moodle library
                //convert commit_timestamp from UTC time (2019-12-02T05:06:20Z format) to unixtime
                $commit_timestamp = $commit->commit_timestamp;

                // Searches for previous records of this assignment being submitted
                $activity = $DB->get_record_sql('SELECT * FROM {block_leaderboard_assignment}
                    WHERE ' . $DB->sql_compare_text('modulename') . ' = ? AND studentid = ? AND courseid = ?;',
                        array('modulename' => $commit->pa, 'studentid' => $user->id, 'courseid' => $courseid));

//                echo("<script>console.log(". json_encode('previous activity:', JSON_HEX_TAG) .");</script>");
//                echo("<script>console.log(". json_encode($activity, JSON_HEX_TAG) .");</script>");
//                echo("<script>console.log(". json_encode('courseid:', JSON_HEX_TAG) .");</script>");
//                echo("<script>console.log(". json_encode($courseid, JSON_HEX_TAG) .");</script>");
                
                //CHECK obviously
                // If there was previous records, and the commit is new, update them
                if ($activity) {
                    if ($commit_timestamp > $activity->timefinished) {
                        $eventdata = self::create_assignment_record($commit, $user->id, $commit_timestamp, 
                                $courseid, $activity->testspassed, $activity->testpoints);

                        $eventdata->id = $activity->id;
                        $DB->update_record('block_leaderboard_assignment', $eventdata);
//                        echo("<script>console.log(". json_encode('Update new record', JSON_HEX_TAG) .");</script>");
//                        echo("<script>console.log(". json_encode($eventdata, JSON_HEX_TAG) .");</script>");
                    }
                }
                // else create new record 
                else {     
                    $eventdata = self::create_assignment_record($commit, $user->id, $commit_timestamp, $courseid);
                    $DB->insert_record('block_leaderboard_assignment', $eventdata);
//                    echo("<script>console.log(". json_encode('Insert new record', JSON_HEX_TAG) .");</script>");
//                    echo("<script>console.log(". json_encode($eventdata, JSON_HEX_TAG) .");</script>");
                } 
                
            }
        }
        $all_assignments = $DB->get_records('block_leaderboard_assignment');
//        echo("<script>console.log(". json_encode('leaderboard assignments:', JSON_HEX_TAG) .");</script>");
//        echo("<script>console.log(". json_encode($all_assignments, JSON_HEX_TAG) .");</script>");
    }
    
    /**
     * Given an array of commits and a pa, finds commits from the pa and attaches
     * them to the existing array.
     * 
     * @param $chosen_commits = array of previously selected commits
     * @param $pa = the new assignment to be selected
     * @return all commits, new and previous
     */
    public static function select_travis_commits($chosen_commits, $pa) {
        global $DB;
                
        $sql = "SELECT * 
            FROM {block_leaderboard_travis_builds} block_leaderboard_travis_builds
            WHERE block_leaderboard_travis_builds.pa = ?
            ORDER BY commit_timestamp ASC;";
        $new_commits = $DB->get_records_sql($sql, array($pa));
        
        $new_commits = array_merge($chosen_commits, $new_commits);
        
        return $new_commits;
    }
    
    /**
     * Given parameters, creates an stdClass to store assignment data
     * 
     * @param $commit = stdClass of a commit
     * @param $userid = the userid of the one who completed the assignment
     * @param $commit_timestamp = the time the commit occured, in unixtime
     * @param $passed_tests = number of tests passed in the assignment previously
     * @param $existing_tests_points = already earned points for tests only
     * @return $eventdata
     */
    public static function create_assignment_record($commit, $userid, $commit_timestamp, $courseid, 
                $passed_tests = 0, $existing_tests_points = 0) {
        global $DB;

        $eventdata = new stdClass;

        // Gets the data of the assignment
        $sql = "SELECT assign.*
            FROM {assign} assign
            WHERE assign.name = ? AND assign.course = ?";
        $assignmentdata = $DB->get_record_sql($sql, array($commit->pa, 'course' => $courseid));

        // 86400 seconds per day in unix time.
        // The function intdiv() is integer divinsion for PHP '/' is foating point division.
        $daysbeforesubmission = intdiv(($assignmentdata->duedate - $commit_timestamp), 86400);
        
        // Set the point value.
        $points = 0;
        
        //ensures student gets no points if they haven't passed at least one test
        if($commit->passed_tests > 0) {
            $points =self::get_early_submission_points($daysbeforesubmission, 'assignment');
        }        

        $test_points = $existing_tests_points;
        // If more tests have been passed than previously, adds points to total
        if ($commit->passed_tests > $passed_tests) {
            $test_points += self::get_early_submission_points($daysbeforesubmission, 'assignmenttests');
        }
        $points += $test_points;

        // Assign data to stdClass
        $eventdata->pointsearned = $points;
        $eventdata->studentid = $userid;
        $eventdata->activityid = $commit->build_id;
        $eventdata->timefinished = $commit_timestamp;
        $eventdata->modulename = $commit->pa;
        $eventdata->courseid = $courseid;
        $eventdata->daysearly = $daysbeforesubmission;
        $eventdata->testspassed = $commit->passed_tests;
        $eventdata->testpoints = $test_points;

        return $eventdata;
    }
    
    /*Determines if a user is an any of the groups entered in and returns a boolean
     * @param groups = array of group objects
     * @param userid = id of the given user
     * @return whether they are or aren't
     * 
     */
    public static function is_user_in_a_group($groups, $userid) {
        
        //for all groups
        foreach($groups as $group) {
            //get group members
            $students = groups_get_members($group->id, $fields = 'u.*', $sort = 'lastname ASC');
            //and see if one of their students is the user
            foreach($students as $student) {
                if($student->id === $userid) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Adds data to leaderboard global tables. Be very careful with
     * @return none
     */
    public static function test_add_to_globals($string) {
        global $DB;
        
        if($string == 'add') {
            $commit = new stdClass;
            $commit->build_id = 262019864;
            $commit->commit_timestamp = 1578787200;
            $commit->committer_email = 'sprint.gonzaga.edu';
            $commit->commit_message = 'NULL';
            $commit->github_assignment_acceptor = sprint;
            $commit->pa = 'pa2';
            $commit->organization_name = 'cs122';
            $commit->total_tests = 10;
            $commit->passed_tests = 100;
            
            $DB->insert_record('block_leaderboard_travis_builds', $commit);
        }
        //purge commit and assignment globals
        else if($string == 'delete') {
            $DB->delete_records('block_leaderboard_travis_builds');
            $DB->delete_records('block_leaderboard_assignment');
        }
        
    }
    
    /**
     * function for testing the create_assignment_record helper function
     * @return none
     */
    public static function test_create_assignment_record() {
        //| id | build_id | commit_timestamp | committer_email | github_assignment_acceptor | 
        //commit_message | pa | organization_name | total_tests | passed_tests |
        
        $commit = new stdClass;
        $commit->build_id = 262019864;
        $commit->commit_timestamp = 1575263180;
        $commit->committer_email = 'sprint.gonzaga.edu';
        $commit->commit_message = 'NULL';
        $commit->github_assignment_acceptor = sprint;
        $commit->pa = 'pa1';
        $commit->organization_name = 'cs122';
        $commit->total_tests = 2;
        $commit->passed_tests = 2;
        
        echo("<script>console.log(". json_encode($commit, JSON_HEX_TAG) .");</script>");
        
        $assignment = new stdClass;
        $user = $commit->github_assignment_acceptor;
        $assignment = self::create_assignment_record($commit, $user, $commit->commit_timestamp, 0, 0);
        $string = '';
        echo("<script>console.log(". json_encode($assignment, JSON_HEX_TAG) .");</script>");
        if($assignment->pointsearned == 15) {
            $string = 'points correct\n';
        }
        else {
            $string = 'points not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
        $output = $assignment->pointsearned;
        echo('<script>console.log(' . json_encode($output, JSON_HEX_TAG) . ');</script>');
        
        
        if($assignment->studentid == $user) {
            $string = 'studentid correct\n';
        }
        else {
            $string = 'studentid not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
        
        if($assignment->activityid == $commit->build_id) {
            $string = 'activityid correct\n';
        }
        else {
            $string = 'activityid not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
       
        
        if($assignment->timefinished == $commit_timestamp) {
            $string = 'timefinished correct\n';
        }
        else {
            $string = 'timefinished not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
        
        if($assignment->modulename == $commit->pa) {
            $string = 'modulename correct\n';
        }
        else {
            $string = 'modulename not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
        
        if($assignment->daysearly == $commit->total_tests) {
            $string = 'daysearly correct\n';
        }
        else {
            $string = 'daysearly not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
        
        if($assignment->testpoints == 10) {
            $string = 'testpoints correct\n';
        }
        else {
            $string = 'testpoints not correct\n';
        }
        echo("<script>console.log('STRING: ".$string."');</script>");
       
    }
    
    
}

