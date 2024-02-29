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

/*
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;

/**
 * Class to handle SCIM response for User Schema.
 */
class scimuserschemaconfigresponse extends scimresponse {

    /**
     * User Schema constructor
     *
     * @param $type string
     **/
    public function __construct(string $type) {
        $this->set_response_type($type);
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    public function extra_data() : array {
        return self::get_extra_data();
    }

    /**
     * Gets the custom fields used in the response,
     *
     * @return array
     */
    public static function get_extra_data() : array {
        global $CFG;

        $locationurl = $CFG->wwwroot . SCIM2_BASE_URL . '/' . static::SCIM2_VERSION . '/Schemas/';

        return [
                "id" => static::SCIM2_USER_URN,
                "name" => 'User',
                "description" => 'User Schema',
                "attributes" => static::get_userattributes(),
                "meta" => ["resourceType" => "Schema", "location" => $locationurl . static::SCIM2_USER_URN],
        ];
    }

    /**
     * Return user attributes as part of SCIM response.
     *
     * @return array
     */
    public static function get_userattributes() : array {

        return [
            [
                "name" => "userName",
                "type" => "string",
                "multiValued" => false,
                "required" => true,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "server",
                "description" => get_string('attr-username', 'local_user_provisioning'),
            ],
            [
                "name" => "name",
                "type" => "complex",
                "multiValued" => false,
                "required" => true,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-name', 'local_user_provisioning'),
                "subAttributes" => [
                    [
                        "name" => "familyName",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-name-familyname', 'local_user_provisioning'),
                    ],
                    [
                        "name" => "givenName",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-name-givenname', 'local_user_provisioning'),
                    ],
                ],
            ],
            [
                "name" => "displayName",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-displayname', 'local_user_provisioning'),
            ],
            [
                "name" => "title",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-displayname', 'local_user_provisioning'),
            ],
            [
                "name" => "preferredLanguage",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-preferredlanguage', 'local_user_provisioning'),
            ],
            [
                "name" => "active",
                "type" => "boolean",
                "multiValued" => false,
                "required" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-active', 'local_user_provisioning'),
            ],
            [
                "name" => "department",
                "type" => "string",
                "multiValued" => false,
                "required" => false,
                "caseExact" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-active', 'local_user_provisioning'),
            ],
            [
                "name" => "emails",
                "type" => "complex",
                "multiValued" => false,
                "required" => true,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-emails', 'local_user_provisioning'),
                "subAttributes" => [
                    [
                        "name" => "value",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => true,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-emails-value', 'local_user_provisioning'),
                    ],
                    [
                        "name" => "type",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "canonicalValues" => ["work", "home", "other"],
                        "description" => get_string('attr-emails-type', 'local_user_provisioning'),
                    ],
                    [
                        "name" => "primary",
                        "type" => "boolean",
                        "multiValued" => false,
                        "required" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-emails-primary', 'local_user_provisioning'),
                    ],
                ],
            ],
            [
                "name" => "addresses",
                "type" => "complex",
                "multiValued" => false,
                "required" => false,
                "mutability" => "readWrite",
                "returned" => "default",
                "uniqueness" => "none",
                "description" => get_string('attr-addresses', 'local_user_provisioning'),
                "subAttributes" => [
                    [
                        "name" => "locality",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-addresses-locality', 'local_user_provisioning'),
                    ],
                    [
                        "name" => "country",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "description" => get_string('attr-addresses-country', 'local_user_provisioning'),
                    ],
                    [
                        "name" => "type",
                        "type" => "string",
                        "multiValued" => false,
                        "required" => false,
                        "caseExact" => false,
                        "mutability" => "readWrite",
                        "returned" => "default",
                        "uniqueness" => "none",
                        "canonicalValues" => ["work", "home", "other"],
                        "description" => get_string('attr-addresses-type', 'local_user_provisioning'),
                    ],
                ],
            ],
        ];
    }
}
