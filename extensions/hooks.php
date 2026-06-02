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
 * Hook and mapping helpers for user provisioning extensions.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Sarah Cotton <sarah.cotton@catalyst-eu.net>
 */

namespace local_user_provisioning;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/registry.php');
require_once(__DIR__ . '/profile_fields.php');

/**
 * Settings updated callback for custom fields.
 *
 * Ensures backing Moodle profile fields are created immediately after settings save.
 *
 * @return void
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_fields_updated(): void {
    local_user_provisioning_custom_ensure_profile_fields_exist();

    static $feedbackshown = false;
    if ($feedbackshown) {
        return;
    }
    $feedbackshown = true;

    $duplicates = local_user_provisioning_custom_duplicate_field_keys();
    if (!empty($duplicates)) {
        $keys = implode(', ', array_keys($duplicates));
        \core\notification::warning(
            get_string('warning:duplicatecustomfieldkeys', 'local_user_provisioning', $keys)
        );
    }
}

/**
 * Get additional schema attributes for custom fields.
 *
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_user_schema_attributes(): array {
    $attributes = [];
    foreach (local_user_provisioning_custom_field_registry() as $field) {
        $attributes[] = $field['schema'];
    }
    return $attributes;
}

/**
 * Add query fields and joins for custom profile fields.
 *
 * @param string $fields
 * @param string $join
 * @return void
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_add_userquery_fields_and_joins(string &$fields, string &$join): void {
    $index = 1;
    foreach (local_user_provisioning_custom_field_registry() as $key => $field) {
        $fieldid = local_user_provisioning_custom_get_or_create_profilefieldid($field['shortname']);
        if (!$fieldid) {
            continue;
        }
        $fieldid = (int)$fieldid;
        $alias = 'uidc' . $index;
        $fields .= ', ' . $alias . '.data AS ' . $key;
        $join .= ' LEFT JOIN {user_info_data} ' . $alias . ' ON u.id = ' . $alias . '.userid'
            . ' AND ' . $alias . '.fieldid = ' . $fieldid;
        $index++;
    }
}

/**
 * Map inbound JSON field to user object if it is a custom field.
 *
 * @param string $key
 * @param mixed $value
 * @param object $user
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_map_inbound_field(string $key, $value, object $user): bool {
    $registry = local_user_provisioning_custom_field_registry();
    if (!array_key_exists($key, $registry)) {
        return false;
    }

    $user->{$key} = trim((string)$value);
    return true;
}

/**
 * Map PATCH path to user object if it is a custom field.
 *
 * @param string $path
 * @param mixed $value
 * @param object $user
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_map_patch_field(string $path, $value, object $user): bool {
    $prefixes = [
        scimresponse::SCIM2_CUSTOM_USER_URN . ':',
        scimresponse::SCIM2_CUSTOM_USER_URN . '.',
    ];

    foreach ($prefixes as $customurnprefix) {
        if (strpos($path, $customurnprefix) === 0) {
            $path = substr($path, strlen($customurnprefix));
            break;
        }
    }

    return local_user_provisioning_custom_map_inbound_field($path, $value, $user);
}

/**
 * Validate unique custom fields before create.
 *
 * @param object $user
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_validate_before_create(object $user): array {
    $errors = [];

    foreach (local_user_provisioning_custom_field_registry() as $key => $field) {
        if (!$field['unique'] || !isset($user->{$key})) {
            continue;
        }

        $fieldid = local_user_provisioning_custom_get_or_create_profilefieldid($field['shortname']);

        if ($fieldid && !local_user_provisioning_custom_profilefield_is_unique($fieldid)) {
            $errors[] = get_string('error:customfieldnotunique', 'local_user_provisioning', (object)['field' => $key]);
            continue;
        }

        if ($fieldid && local_user_provisioning_custom_profilefield_value_exists($user->{$key}, $fieldid)) {
            $errors[] = get_string('error:customfieldexists', 'local_user_provisioning', (object)['field' => $key]);
        }
    }

    return $errors;
}

/**
 * Validate unique custom fields before update.
 *
 * @param object $user
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_validate_before_update(object $user): array {
    $errors = [];

    foreach (local_user_provisioning_custom_field_registry() as $key => $field) {
        if (!$field['unique'] || !isset($user->{$key})) {
            continue;
        }

        $fieldid = local_user_provisioning_custom_get_or_create_profilefieldid($field['shortname']);

        if ($fieldid && !local_user_provisioning_custom_profilefield_is_unique($fieldid)) {
            $errors[] = get_string('error:customfieldnotunique', 'local_user_provisioning', (object)['field' => $key]);
            continue;
        }

        if ($fieldid && local_user_provisioning_custom_profilefield_value_exists($user->{$key}, $fieldid, $user->id)) {
            $errors[] = get_string('error:customfieldexists', 'local_user_provisioning', (object)['field' => $key]);
        }
    }

    return $errors;
}

/**
 * Persist custom profile field values.
 *
 * @param object $user
 * @return void
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_persist_profile_fields(object $user): void {
    foreach (local_user_provisioning_custom_field_registry() as $key => $field) {
        if (!isset($user->{$key})) {
            continue;
        }

        $fieldid = local_user_provisioning_custom_get_or_create_profilefieldid($field['shortname']);

        if ($fieldid) {
            local_user_provisioning_custom_set_profilefield_value($user->id, $fieldid, $user->{$key});
        }
    }
}

/**
 * Append custom SCIM response fields.
 *
 * @param object $user
 * @param array $returnvar
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_append_response_fields(object $user, array $returnvar): array {
    if (!isset($returnvar[scimresponse::SCIM2_CUSTOM_USER_URN]) || !is_array($returnvar[scimresponse::SCIM2_CUSTOM_USER_URN])) {
        $returnvar[scimresponse::SCIM2_CUSTOM_USER_URN] = [];
    }

    foreach (local_user_provisioning_custom_field_registry() as $key => $field) {
        $value = isset($user->{$key}) ? $user->{$key} : '';
        $returnvar[scimresponse::SCIM2_CUSTOM_USER_URN][$key] = $value;
    }

    return $returnvar;
}
