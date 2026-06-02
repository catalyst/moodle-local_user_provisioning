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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Custom-field registry helpers for SCIM payload mapping.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_provisioning;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/workplace.php');

/**
 * Resolve current tenant id from bearer token context in Workplace.
 *
 * Returns null when tenant context is unavailable.
 *
 * @return int|null
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_current_tenantid(): ?int {
    if (!local_user_provisioning_custom_is_moodleworkplace()) {
        return null;
    }

    $bearertoken = null;

    if (function_exists('\\local_user_provisioning\\local_user_provisioning_get_bearertoken')) {
        $bearertoken = \local_user_provisioning\local_user_provisioning_get_bearertoken();
    }

    if (empty($bearertoken)) {
        return null;
    }

    try {
        $tenant = (new workplace_helper($bearertoken))->get_tenant();
        return (int)$tenant->get('id');
    } catch (\Throwable $e) {
        debugging(
            'Tenant resolution failed in local_user_provisioning_custom_current_tenantid(): ' . $e->getMessage(),
            DEBUG_DEVELOPER
        );
        return null;
    }
}

/**
 * Parse registry config from tenant-specific plugin config key.
 *
 * @param int $tenantid
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_field_registry_for_tenant(int $tenantid): array {
    $configkey = "tenant_{$tenantid}_custom_fields";
    $configtext = (string)get_config('local_user_provisioning', $configkey);

    return local_user_provisioning_custom_field_registry_from_config($configtext);
}

/**
 * Merge registries by field key.
 *
 * Registries are additive. Existing keys are preserved and only missing keys
 * are appended from the additional registry.
 *
 * @param array $base
 * @param array $additional
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_merge_registries(array $base, array $additional): array {
    foreach ($additional as $key => $field) {
        if (!array_key_exists($key, $base)) {
            $base[$key] = $field;
            continue;
        }

        // If any source marks a field unique, keep it unique.
        if (!empty($field['unique']) && empty($base[$key]['unique'])) {
            $base[$key]['unique'] = true;
            $base[$key]['schema']['uniqueness'] = 'server';
        }
    }

    return $base;
}

/**
 * Parse custom field config text into registry records.
 *
 * Each non-empty line should be in the format:
 * shortname|Label|unique
 *
 * unique is optional and accepts: 1, true, yes, y, unique.
 *
 * @param string $configtext
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_field_registry_from_config(string $configtext): array {
    $fields = [];
    $lines = preg_split('/\r\n|\r|\n/', $configtext);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = array_map('trim', explode('|', $line));
        $shortname = $parts[0] ?? '';
        $label = $parts[1] ?? $shortname;
        $uniquestr = \core_text::strtolower($parts[2] ?? '');

        if ($shortname === '' || !preg_match('/^[a-z0-9_]+$/', $shortname)) {
            continue;
        }

        $unique = in_array($uniquestr, ['1', 'true', 'yes', 'y', 'unique'], true);

        $fields[$shortname] = [
            'shortname' => $shortname,
            'name' => ($label === '' ? $shortname : $label),
            'unique' => $unique,
            'schema' => [
                'name' => $shortname,
                'type' => 'string',
                'multiValued' => false,
                'required' => false,
                'caseExact' => false,
                'mutability' => 'readWrite',
                'returned' => 'default',
                'uniqueness' => $unique ? 'server' : 'none',
                'description' => ($label === '' ? $shortname : $label),
            ],
        ];
    }

    return $fields;
}

/**
 * Resolve active registry of custom SCIM fields.
 *
 * In Workplace request context, tenant-specific custom fields are merged
 * with global custom_fields so tenants can use both.
 *
 * @param int|null $tenantid
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_field_registry(?int $tenantid = null): array {
    $globalconfigtext = (string)get_config('local_user_provisioning', 'custom_fields');
    $globalregistry = local_user_provisioning_custom_field_registry_from_config($globalconfigtext);

    if (is_null($tenantid)) {
        $tenantid = local_user_provisioning_custom_current_tenantid();
    }

    if (is_null($tenantid)) {
        return $globalregistry;
    }

    $tenantregistry = local_user_provisioning_custom_field_registry_for_tenant($tenantid);
    return local_user_provisioning_custom_merge_registries($globalregistry, $tenantregistry);
}

/**
 * Get merged registry across global and tenant-specific configs.
 *
 * Intended for maintenance operations like creating missing profile fields.
 *
 * @return array
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_field_registry_all(): array {
    $globalconfigtext = (string)get_config('local_user_provisioning', 'custom_fields');
    $mergedregistry = local_user_provisioning_custom_field_registry_from_config($globalconfigtext);

    if (!local_user_provisioning_custom_is_moodleworkplace()) {
        return $mergedregistry;
    }

    $tenantid = local_user_provisioning_custom_current_tenantid();

    if (!is_null($tenantid)) {
        $tenantregistry = local_user_provisioning_custom_field_registry_for_tenant($tenantid);
        $mergedregistry = local_user_provisioning_custom_merge_registries($mergedregistry, $tenantregistry);
    }

    return $mergedregistry;
}

/**
 * Detect duplicate custom field keys across global and tenant registries.
 *
 * Duplicate keys cannot create separate profile fields without tenant prefixes.
 *
 * @return array<string, array<int, string>>
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_duplicate_field_keys(): array {
    $seen = [];

    $globalconfigtext = (string)get_config('local_user_provisioning', 'custom_fields');
    $globalregistry = local_user_provisioning_custom_field_registry_from_config($globalconfigtext);
    foreach (array_keys($globalregistry) as $key) {
        $seen[$key] = ['global'];
    }

    if (\local_user_provisioning\local_user_provisioning_is_moodleworkplace()) {
        $tenants = \tool_tenant\tenant::get_records(['archived' => 0]);
        foreach ($tenants as $tenant) {
            $tenantid = (int)$tenant->get('id');
            $tenantname = trim((string)$tenant->get('name'));
            $source = $tenantname === '' ? 'tenant ' . $tenantid : $tenantname;

            $tenantregistry = local_user_provisioning_custom_field_registry_for_tenant($tenantid);
            foreach (array_keys($tenantregistry) as $key) {
                if (!array_key_exists($key, $seen)) {
                    $seen[$key] = [$source];
                    continue;
                }

                if (!in_array($source, $seen[$key], true)) {
                    $seen[$key][] = $source;
                }
            }
        }
    }

    $duplicates = [];
    foreach ($seen as $key => $sources) {
        if (count($sources) > 1) {
            $duplicates[$key] = $sources;
        }
    }

    return $duplicates;
}
