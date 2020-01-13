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
        
    if ($oldversion < 2020011020) {

        // Define field testspassed to be added to block_leaderboard_assignment.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testspassed', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'daysearly');

        // Conditionally launch add field testspassed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011020, 'leaderboard');
    }
    
    if ($oldversion < 2020011030) {

        // Define field testpoints to be added to block_leaderboard_assignment.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testpoints', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'testspassed');

        // Conditionally launch add field testpoints.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011030, 'leaderboard');
    }
    
    if ($oldversion < 2020011031) {

        // Changing nullability of field testspassed on table block_leaderboard_assignment to not null.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testspassed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'daysearly');

        // Launch change of nullability for field testspassed.
        $dbman->change_field_notnull($table, $field);

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011031, 'leaderboard');
    }

    if ($oldversion < 2020011032) {

        // Changing the default of field testspassed on table block_leaderboard_assignment to 0.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testspassed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'daysearly');

        // Launch change of default for field testspassed.
        $dbman->change_field_default($table, $field);

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011032, 'leaderboard');
    }

    if ($oldversion < 2020011033) {

        // Changing nullability of field testpoints on table block_leaderboard_assignment to not null.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testpoints', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'testspassed');

        // Launch change of nullability for field testpoints.
        $dbman->change_field_notnull($table, $field);

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011033, 'leaderboard');
    }

    if ($oldversion < 2020011034) {

        // Changing the default of field testpoints on table block_leaderboard_assignment to 0.
        $table = new xmldb_table('block_leaderboard_assignment');
        $field = new xmldb_field('testpoints', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'testspassed');

        // Launch change of default for field testpoints.
        $dbman->change_field_default($table, $field);

        // Leaderboard savepoint reached.
        upgrade_block_savepoint(true, 2020011034, 'leaderboard');
    }

    
    return true;
}