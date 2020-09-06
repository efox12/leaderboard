<?php
// This file is part of Moodle - http:// moodle.org/
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
 * Creates and displays the leaderboard in its own page.
 *
 * @package    blocks_leaderboard
 * @copyright  2019 Erik Fox
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once("$CFG->libdir/formslib.php");

global $CFG, $DB;

class date_selector_form extends moodleform {

    /**
     * A form for selecting a starting date and an ending date.
     *
     * @return void
     */
    public function definition() {
        $mform = & $this->_form; // Don't forget the underscore!
        $mform->addElement('header', 'h', get_string('changedaterange', 'block_leaderboard'));

        // Parameters required for the page to load.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'start');
        $mform->setType('start', PARAM_RAW);
        $mform->addElement('hidden', 'end');
        $mform->setType('end', PARAM_RAW);

        // The form elements for selecting dates with defaults set to the current date range.
        $mform->addElement('date_selector', 'startDate', get_string('start', 'block_leaderboard'));
        $mform->setDefault('startDate', $this->_customdata['startDate']);
        $mform->addElement('date_selector', 'endDate', get_string('end', 'block_leaderboard'));
        $mform->setDefault('endDate', $this->_customdata['endDate']);

        // The buttons to update the leaderboard with new dates or reset to the default dates.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('update', 'block_leaderboard'));
        $buttonarray[] = $mform->createElement('cancel', 'resetbutton', get_string('resettodefault', 'block_leaderboard'));
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
}