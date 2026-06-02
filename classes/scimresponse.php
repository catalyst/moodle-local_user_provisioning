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
 * Base SCIM response class.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;

/**
 *
 * Abstract class to handle SCIM response.
 *
 * @package local_user_provisioning
 */
abstract class scimresponse {
    /** Schema prefix. */
    const SCIM2_URN = 'urn:ietf:params:scim:api:messages:2.0:';
    /** Service Provider Configuration schema. */
    const SCIM2_CONFIG_URN = 'urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig';
    /** User resource schema. */
    const SCIM2_USER_URN = 'urn:ietf:params:scim:schemas:core:2.0:User';
    /** Enterprise User extension schema. */
    const SCIM2_ENTERPRISE_USER_EXT = 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User';
    /** Custom user extension schema. */
    const SCIM2_CUSTOM_USER_URN = 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User';
    /** List/query response schema. */
    const SCIM2_LISTRESPONSE_URN = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';

    /** Schemas returned in user responses when Workplace is enabled. */
    const SCIM2_SCHEMAS_WORKPLACE = [self::SCIM2_USER_URN, self::SCIM2_ENTERPRISE_USER_EXT, self::SCIM2_CUSTOM_USER_URN];
    /** Schemas returned in user responses for standard Moodle. */
    const SCIM2_SCHEMAS_MOODLE = [self::SCIM2_USER_URN, self::SCIM2_CUSTOM_USER_URN];
    /** SCIM API version segment. */
    const SCIM2_VERSION = 'v2';
    /** Schema creation timestamp. */
    const TIMECREATED = '2021-01-27T12:00:00Z';
    /** Schema modified timestamp. */
    const TIMEMODIFIED = '2021-01-27T12:00:00Z';

    /** @var string[] SCIM schema URNs used in the JSON response. */
    private $schemas = [];

    /**
     * Set the scim response type of this object.
     *
     * Subclasses should call this as part of their constructor
     *
     * @param string $type A valid scim response type
     * @return void
     */
    protected function set_response_type(string $type): void {

        switch ($type) {
            case 'ServiceConfig':
                $this->schemas[] = static::SCIM2_CONFIG_URN;
                break;
            case 'Schemas':
                $this->schemas[] = static::SCIM2_LISTRESPONSE_URN;
                break;
            case 'User':
                $this->schemas = static::get_user_schemas();
                break;
            case 'UserSchema':
                $this->schemas = static::SCIM2_USER_URN;
                break;
            case 'UserEnterpriseSchema':
                $this->schemas = static::SCIM2_ENTERPRISE_USER_EXT;
                break;
            case 'UserCustomSchema':
                $this->schemas = static::SCIM2_CUSTOM_USER_URN;
                break;
            default:
                $this->schemas[] = static::SCIM2_URN . $type;
        }
    }

    /**
     * Return user schemas appropriate for this environment.
     *
     * @return array
     */
    protected static function get_user_schemas(): array {
        return local_user_provisioning_is_moodleworkplace() ? static::SCIM2_SCHEMAS_WORKPLACE : static::SCIM2_SCHEMAS_MOODLE;
    }

    /**
     * Return a scim compatible array ready to be converted into json
     *
     * @return array An array ready to be converted into a textual json object
     */
    public function to_json(): array {
        $base = ["schemas" => $this->schemas];
        return array_merge($base, $this->extra_data());
    }

    /**
     * Returns SCIM response.
     *
     * @param int $responsecode
     */
    public function send_response(int $responsecode): void {
        static::scim_headers($responsecode);
        print(json_encode($this->to_json(), JSON_UNESCAPED_SLASHES));
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data(): array {
        return [];
    }

    /**
     * Sets appropriate headers for a scim response.
     *
     * Call directly if there is no response data to send back
     *
     * @param int     $responsecode The http status code to use
     * @param string  $message      The http status message to use.
     * @return void
     */
    public static function scim_headers(int $responsecode, ?string $message = null): void {

        http_response_code($responsecode);
        header('Content-Type: application/scim+json');
    }
}
