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
 * Block XP upgrade.
 *
 * @package    block_xp
 * @copyright  2014 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block XP upgrade function.
 *
 * @param int $oldversion Old version.
 * @return true
 */
function xmldb_block_leaderboard_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Add a new column newcol to the mdl_myqtype_options
    if ($oldversion < 2019091840) {
        /*
        // Define table block_leaderboard_assignment to be renamed to NEWNAMEGOESHERE.
        $table = new xmldb_table('assignment_table');
        // Launch rename table for block_leaderboard_assignment.
        $dbman->rename_table($table, 'block_leaderboard_assignment');

        $table = new xmldb_table('quiz_table');
        // Launch rename table for block_leaderboard_quiz.
        $dbman->rename_table($table, 'block_leaderboard_quiz');

        $table = new xmldb_table('choice_table');
        // Launch rename table for choice_table.
        $dbman->rename_table($table, 'block_leaderboard_choice');

        $table = new xmldb_table('forum_table');
        // Launch rename table for forum_table.
        $dbman->rename_table($table, 'block_leaderboard_forum');

        $table = new xmldb_table('group_data_table');

        // Launch rename table for group_data_table.
        $dbman->rename_table($table, 'block_leaderboard_group_data');
        */
        $table = new xmldb_table('block_leaderboard_group_data');

        $field = new xmldb_field('prevoiusstanding', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'current_standing');
        // Conditionally launch add field prevoiusstanding.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lastmove', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'prevoiusstanding');
        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('current_standing', XMLDB_TYPE_INTEGER, '10', null, null, null, '1', 'multiplier');
        // Launch rename field currentstanding.
        $dbman->rename_field($table, $field, 'currentstanding');

        $field = new xmldb_field('group_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Launch rename field group_id.
        $dbman->rename_field($table, $field, 'groupid');

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2019091840, 'leaderboard');

    }
    return true;

}
