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
function local_user_provisioning_custom_tenant_category_name(string $tenantname): string {
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
 * @return int
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_get_or_create_categoryid(string $categoryname): int {
    global $DB;

    if ($categoryid = $DB->get_field('user_info_category', 'id', ['name' => $categoryname])) {
        return (int)$categoryid;
    }

    $catsortorder = (int)$DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_category}', []) + 1;
    $categoryrecord = new stdClass();
    $categoryrecord->name = $categoryname;
    $categoryrecord->sortorder = $catsortorder;

    return (int)$DB->insert_record('user_info_category', $categoryrecord);
}

/**
 * Get profile field ID by shortname.
 *
 * @param string $shortname
 * @return int|null
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_get_or_create_profilefieldid(
    string $shortname
): ?int {
    global $DB;

    if ($fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname])) {
        return (int)$fieldid;
    }

    return null;
}

/**
 * Check if a profile field is configured as unique in Moodle UI.
 *
 * @param int $fieldid
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_profilefield_is_unique(int $fieldid): bool {
    global $DB;

    $forceunique = $DB->get_field('user_info_field', 'forceunique', ['id' => $fieldid], IGNORE_MISSING);
    return !empty($forceunique);
}

/**
 * Create profile field by shortname if missing.
 *
 * @param string $shortname
 * @param string|null $name
 * @param int $forceunique
 * @param string $datatype
 * @return int|null
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_create_profilefieldid(
    string $shortname,
    ?string $name = null,
    int $forceunique = 0,
    string $datatype = 'text',
    ?string $categoryname = null
): ?int {
    global $DB;

    if ($fieldid = local_user_provisioning_custom_get_or_create_profilefieldid($shortname)) {
        return (int)$fieldid;
    }

    if ($categoryname === null || trim($categoryname) === '') {
        $categoryname = USERPROFILEFIELDCATEGORY;
    }

    $categoryid = local_user_provisioning_custom_get_or_create_categoryid($categoryname);

    $fieldsortorder = (int)$DB->get_field_sql(
        'SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_field} WHERE categoryid = :categoryid',
        ['categoryid' => $categoryid]
    ) + 1;

    $fieldrecord = new stdClass();
    $fieldrecord->shortname = $shortname;
    $fieldrecord->name = $name ?? strtoupper($shortname);
    $fieldrecord->datatype = $datatype;
    $fieldrecord->categoryid = $categoryid;
    $fieldrecord->sortorder = $fieldsortorder;
    $fieldrecord->required = 0;
    $fieldrecord->locked = 0;
    $fieldrecord->visible = 0;
    $fieldrecord->forceunique = $forceunique;
    $fieldrecord->signup = 0;

    return (int)$DB->insert_record('user_info_field', $fieldrecord);
}

/**
 * Ensure every registered custom field has a backing Moodle profile field.
 *
 * @return void
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_ensure_profile_fields_exist(): void {
    $globalregistry = local_user_provisioning_custom_field_registry();
    foreach ($globalregistry as $field) {
        local_user_provisioning_custom_create_profilefieldid(
            $field['shortname'],
            $field['name'],
            (int)$field['unique'],
            'text',
            USERPROFILEFIELDCATEGORY
        );
    }

    if (
        !\local_user_provisioning\local_user_provisioning_is_moodleworkplace()
    ) {
        return;
    }

    $tenants = \tool_tenant\tenant::get_records(['archived' => 0]);
    foreach ($tenants as $tenant) {
        $tenantid = (int)$tenant->get('id');
        $tenantregistry = local_user_provisioning_custom_field_registry_for_tenant($tenantid);
        if (empty($tenantregistry)) {
            continue;
        }

        $tenantcategory = local_user_provisioning_custom_tenant_category_name((string)$tenant->get('name'));
        local_user_provisioning_custom_get_or_create_categoryid($tenantcategory);

        foreach ($tenantregistry as $field) {
            local_user_provisioning_custom_create_profilefieldid(
                $field['shortname'],
                $field['name'],
                (int)$field['unique'],
                'text',
                $tenantcategory
            );
        }
    }
}

/**
 * Set profile field value.
 *
 * @param int $userid
 * @param int $fieldid
 * @param string|null $value
 * @return void
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_set_profilefield_value(int $userid, int $fieldid, ?string $value): void {
    global $DB;

    if (is_null($value)) {
        $value = '';
    }

    if ($record = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $fieldid])) {
        $DB->set_field('user_info_data', 'data', $value, ['userid' => $userid, 'fieldid' => $fieldid]);
    } else {
        if ($value === '') {
            return;
        }
        $thisrecord = new stdClass();
        $thisrecord->userid = $userid;
        $thisrecord->fieldid = $fieldid;
        $thisrecord->data = $value;
        $DB->insert_record('user_info_data', $thisrecord);
    }
}

/**
 * Check if profile field value already exists.
 *
 * @param string $value
 * @param int $fieldid
 * @param int|null $excludeuserid
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_profilefield_value_exists(
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
