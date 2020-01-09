<?php
/**
 * Unit tests for block/leaderboard/editlib.php.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package question
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
global $DB;
require_once($CFG->libdir . '/moodlelib.php'); // Include the code to test
require_once(__DIR__ . '/../classes/functions.php');
 
/** This class contains the test cases for the functions in functions.php. */
class leaderboard_function_test extends UnitTestCase {
    function assignment_submitted_github_test() {
        // Do the test here.
    }
 
    // ... more test methods.
}
?>