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
 * Upgrade logic.
 *
 * @package    local_user_provisioning
 * @copyright  2026 Catalyst IT Europe Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Performs data migrations and updates on upgrade.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_upgrade($oldversion): bool {
    global $CFG, $DB;

    if ($oldversion < 2026052800) {
        require_once($CFG->dirroot . '/local/user_provisioning/extensions/registry.php');

        $teamfield = $DB->get_record('user_info_field', ['shortname' => 'team'], 'id,name', IGNORE_MISSING);

        if ($teamfield) {
            $label = trim((string)$teamfield->name);
            if ($label === '') {
                $label = 'Team';
            }

            $newentry = 'team|' . $label;

            if (\local_user_provisioning\is_moodleworkplace()) {
                $tenants = \tool_tenant\tenant::get_records(['archived' => 0]);

                foreach ($tenants as $tenant) {
                    $tenantid = (int)$tenant->get('id');
                    $configkey = "tenant_{$tenantid}_custom_fields";
                    $configtext = (string)get_config('local_user_provisioning', $configkey);
                    $registry = \local_user_provisioning\custom_field_registry_from_config($configtext);

                    if (array_key_exists('team', $registry)) {
                        continue;
                    }

                    $newconfigtext = trim($configtext);
                    if ($newconfigtext !== '') {
                        $newconfigtext .= PHP_EOL;
                    }
                    $newconfigtext .= $newentry;

                    // One-time upgrade write, only for tenants that still miss the team entry.
                    set_config($configkey, $newconfigtext, 'local_user_provisioning');
                }
            } else {
                $configtext = (string)get_config('local_user_provisioning', 'custom_fields');
                $registry = \local_user_provisioning\custom_field_registry_from_config($configtext);

                if (!array_key_exists('team', $registry)) {
                    $newconfigtext = trim($configtext);
                    if ($newconfigtext !== '') {
                        $newconfigtext .= PHP_EOL;
                    }
                    $newconfigtext .= $newentry;

                    set_config('custom_fields', $newconfigtext, 'local_user_provisioning');
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026052800, 'local', 'user_provisioning');
    }

    return true;
}
