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
 * Profile field helpers for custom SCIM mappings.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_provisioning;

use stdClass;
use tool_tenant\profile_manager;

defined('MOODLE_INTERNAL') || die();

if (!defined('USERPROFILEFIELDCATEGORY')) {
    define('USERPROFILEFIELDCATEGORY', 'User Provisioning');
}

if (!defined('USERPROFILEFIELDCATEGORYTENANTPREFIX')) {
    define('USERPROFILEFIELDCATEGORYTENANTPREFIX', 'User Provisioning - ');
}

/**
 * Build tenant-specific profile field category name.
 *
 * @param string $tenantname
 * @return string
 * @package local_user_provisioning
 */
function custom_tenant_category_name(string $tenantname): string {
    $tenantname = trim($tenantname);
    if ($tenantname === '') {
        $tenantname = 'Tenant';
    }

    return USERPROFILEFIELDCATEGORYTENANTPREFIX . $tenantname;
}

/**
 * Resolve or create a user profile field category by name.
 *
 * @param string $categoryname
 * @param int|null $tenantid
 * @return int
 * @package local_user_provisioning
 */
function custom_get_or_create_categoryid(string $categoryname, ?int $tenantid = null): int {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/user/profile/definelib.php');

    if ($categoryid = $DB->get_field('user_info_category', 'id', ['name' => $categoryname])) {
        return (int)$categoryid;
    }

    $categoryrecord = new stdClass();
    $categoryrecord->id = 0;
    $categoryrecord->name = $categoryname;
    profile_save_category($categoryrecord);

    // If we have a tenant ID, make sure the profile fields are only available for that tenant.
    if ($tenantid) {
        $categoryavailable = new stdClass();
        $categoryavailable->id = $categoryrecord->id;
        $categoryavailable->name = $categoryrecord->name;
        $categoryavailable->availability = 'only';
        $categoryavailable->onlytenants = [];
        $categoryavailable->onlytenants[] = $tenantid;
        profile_manager::save_category_config($categoryavailable);
    }

    return (int)$categoryrecord->id;
}

/**
 * Get profile field ID by shortname.
 *
 * @param string $shortname
 * @return int|null
 * @package local_user_provisioning
 */
function custom_get_profilefieldid(
    string $shortname
): ?int {
    global $DB;

    if ($fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname])) {
        return (int)$fieldid;
    }

    return null;
}

/**
 * Get existing profile field records keyed by shortname.
 *
 * @param array $shortnames
 * @return array
 * @package local_user_provisioning
 */
function custom_get_profilefields_by_shortnames(array $shortnames): array {
    global $DB;

    $shortnames = array_values(array_unique(array_filter(array_map('strval', $shortnames), static function(string $name): bool {
        return trim($name) !== '';
    })));

    if (empty($shortnames)) {
        return [];
    }

    [$insql, $params] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED);
    return $DB->get_records_select('user_info_field', "shortname {$insql}", $params, '', 'id,shortname,forceunique');
}

/**
 * Create profile field by shortname if missing.
 *
 * @param string $shortname
 * @param string|null $name
 * @param int $forceunique
 * @param string $datatype
 * @param int|null $tenantid
 * @return int|null
 * @package local_user_provisioning
 */
function custom_create_profilefieldid(
    string $shortname,
    ?string $name = null,
    int $forceunique = 0,
    string $datatype = 'text',
    ?string $categoryname = null,
    ?int $tenantid = null
): ?int {
    global $CFG;

    require_once($CFG->dirroot . '/user/profile/definelib.php');
    require_once($CFG->dirroot . '/user/profile/lib.php');

    if ($fieldid = custom_get_profilefieldid($shortname)) {
        return (int)$fieldid;
    }

    if ($categoryname === null || trim($categoryname) === '') {
        $categoryname = USERPROFILEFIELDCATEGORY;
    }

    $categoryid = custom_get_or_create_categoryid($categoryname, $tenantid);

    $fieldrecord = new stdClass();
    $fieldrecord->id = 0;
    $fieldrecord->shortname = $shortname;
    $fieldrecord->name = $name ?? strtoupper($shortname);
    $fieldrecord->datatype = $datatype;
    $fieldrecord->categoryid = $categoryid;
    $fieldrecord->required = 0;
    $fieldrecord->locked = 0;
    $fieldrecord->visible = PROFILE_VISIBLE_NONE;
    $fieldrecord->forceunique = $forceunique;
    $fieldrecord->signup = 0;
    $fieldrecord->description = ['text' => '', 'format' => FORMAT_HTML];
    $fieldrecord->defaultdata = '';

    if ($datatype === 'text') {
        $fieldrecord->param1 = 30;
        $fieldrecord->param2 = 2048;
        $fieldrecord->param3 = 0;
        $fieldrecord->param4 = '';
        $fieldrecord->param5 = '';
    }

    profile_save_field($fieldrecord, []);
    return (int)$fieldrecord->id;
}

/**
 * Ensure every registered custom field has a backing Moodle profile field.
 *
 * @return void
 * @package local_user_provisioning
 */
function custom_ensure_profile_fields_exist(): void {
    // Get the global field registry - fields that apply to all tenants.
    $globalregistry = custom_field_registry();

    // Start with an empty tenants array.
    $tenants = [];
    // Only fetch tenants if we're running Moodle Workplace.
    if (\local_user_provisioning\is_moodleworkplace()) {
        // Get all active (non-archived) tenants.
        $tenants = \tool_tenant\tenant::get_records(['archived' => 0]);
    }

    // If there are no tenants, there's nothing to do so exit early.
    if (empty($tenants)) {
        return;
    }

    // Start the shortnames list with all global field shortnames.
    $allshortnames = array_column($globalregistry, 'shortname');
    // Loop through each tenant to also collect their specific field shortnames.
    foreach ($tenants as $tenant) {
        $tenantid = (int)$tenant->get('id');
        // Get fields specific to this tenant.
        $tenantregistry = custom_field_registry_for_tenant($tenantid);
        // Add each tenant field shortname to the master list.
        foreach ($tenantregistry as $field) {
            $allshortnames[] = $field['shortname'];
        }
    }

    // Do a single DB lookup for all shortnames at once (global + all tenants).
    $existingfields = custom_get_profilefields_by_shortnames($allshortnames);

    // Loop through global fields and create any that don't already exist.
    foreach ($globalregistry as $field) {
        // If this field already exists in the DB, skip it.
        if (!empty($existingfields[$field['shortname']]) || $field['shortname'] === 'auth') {
            continue;
        }

        // Field doesn't exist so create it under the global profile field category.
        custom_create_profilefieldid(
            $field['shortname'],
            $field['name'],
            (int)$field['unique'],
            'text',
            USERPROFILEFIELDCATEGORY,
            $tenantid
        );

        // Add to existing fields so we don't try to create it again later.
        $existingfields[$field['shortname']] = (object)['shortname' => $field['shortname']];
    }

    // Now loop through tenants again to create any missing tenant-specific fields.
    foreach ($tenants as $tenant) {
        $tenantid = (int)$tenant->get('id');
        // Get fields specific to this tenant.
        $tenantregistry = custom_field_registry_for_tenant($tenantid);
        // If this tenant has no specific fields, skip it.
        if (empty($tenantregistry)) {
            continue;
        }

        // Get or build the category name for this tenant.
        $tenantcategory = custom_tenant_category_name((string)$tenant->get('name'));
        // Ensure the tenant category exists in the DB, creating it if not.
        custom_get_or_create_categoryid($tenantcategory, $tenantid);

        // Loop through tenant fields and create any that don't already exist.
        foreach ($tenantregistry as $field) {
            // If this field already exists in the DB, skip it.
            if (!empty($existingfields[$field['shortname']])) {
                continue;
            }

            // Field doesn't exist so create it under the tenant's category.
            custom_create_profilefieldid(
                $field['shortname'],
                $field['name'],
                (int)$field['unique'],
                'text',
                $tenantcategory,
                $tenantid
            );

            // Add to existing fields so we don't try to create it again later.
            $existingfields[$field['shortname']] = (object)['shortname' => $field['shortname']];
        }
    }
}

/**
 * Check if profile field value already exists.
 *
 * Used for uniqueness validation where a custom profile field has been
 * mark as only accepting unique values.
 *
 * @param string $value
 * @param int $fieldid
 * @param int|null $excludeuserid Prevents false positives on updates where the same user already has that value.
 * @return bool
 * @package local_user_provisioning
 */
function custom_profilefield_value_exists(
    string $value,
    int $fieldid,
    ?int $excludeuserid = null
): bool {
    global $DB;

    if ($value === '') {
        return false;
    }

    $sql = "SELECT 1
              FROM {user_info_data} uid
             WHERE uid.fieldid = :fieldid
               AND uid.data = :value";
    $params = [
        'fieldid' => $fieldid,
        'value' => $value,
    ];

    if (!is_null($excludeuserid)) {
        $sql .= ' AND uid.userid <> :userid';
        $params['userid'] = $excludeuserid;
    }

    return $DB->record_exists_sql($sql, $params);
}
