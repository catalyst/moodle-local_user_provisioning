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
 * local_user_provisioning upgrade library.
 *
 * @package    factor_auth
 * @copyright  Catalyst IT Australia 2026
 * @author     Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * local_user_provisioning upgrade helper function
 *
 * @param int $oldversion
 */
function xmldb_local_user_provisioning_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026041501) {

        // Define table local_user_provisioning_clients to be created.
        $table = new xmldb_table('local_user_provisioning_clients');

        // Adding fields to table local_user_provisioning_clients.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('client_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('client_secret', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        $table->add_field('additionalscopes', XMLDB_TYPE_CHAR, '1333', null, null, null, null);

        // Adding keys to table local_user_provisioning_clients.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_user_provisioning_clients.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // User_provisioning savepoint reached.
        upgrade_plugin_savepoint(true, 2026041501, 'local', 'user_provisioning');
    }

    if ($oldversion < 2026041502) {

        // Define table local_user_provisioning_accesstoken to be created.
        $table = new xmldb_table('local_user_provisioning_accesstoken');

        // Adding fields to table local_user_provisioning_accesstoken.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('access_token', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('client_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expires', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scope', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_user_provisioning_accesstoken.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('clientid', XMLDB_KEY_FOREIGN, ['client_id'], 'local_user_provisioning_clients', ['id']);

        // Conditionally launch create table for local_user_provisioning_accesstoken.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // User_provisioning savepoint reached.
        upgrade_plugin_savepoint(true, 2026041502, 'local', 'user_provisioning');
    }

    return true;
}
