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
 * Swagger UI for local_user_provisioning
 *
 * @package   local_user_provisioning
 * @copyright Catalyst IT Europe Ltd 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_admin();

global $CFG;

require_once($CFG->dirroot . '/local/user_provisioning/extensions/bootstrap.php');

header('Content-Type: text/yaml');

/**
 * Escape YAML single-quoted scalar value.
 *
 * @param string $value
 * @return string
 */
function openapi_yaml_quote(string $value): string {
    return "'" . str_replace("'", "''", $value) . "'";
}

/**
 * Build YAML block for dynamically registered custom field properties.
 *
 * @return string
 */
function openapi_custom_fields_yaml(): string {
    $registry = \local_user_provisioning\custom_field_registry();

    if (empty($registry)) {
        return "      additionalProperties: false\n";
    }

    $yaml = "      properties:\n";
    foreach ($registry as $name => $field) {
        $label = $field['name'] ?? $name;
        $yaml .= "        {$name}:\n";
        $yaml .= "          type: string\n";
        $yaml .= "          description: " . openapi_yaml_quote((string)$label) . "\n";
        $yaml .= "          example: " . openapi_yaml_quote($name) . "\n";
    }

    $yaml .= "      example:\n";
    foreach ($registry as $name => $field) {
        $yaml .= "        {$name}: " . openapi_yaml_quote($name) . "\n";
    }

    return $yaml;
}

/**
 * Build YAML lines for custom extension example values in POST payload.
 *
 * @return string
 */
function openapi_custom_fields_post_example_yaml(): string {
    $registry = \local_user_provisioning\custom_field_registry_all();
    $yaml = '';

    foreach ($registry as $name => $field) {
        if ($name === 'auth') {
            continue;
        }
        $yaml .= "                {$name}: " . openapi_yaml_quote($name) . "\n";
    }

    $yaml .= "                tenantSpecificAttribute: tenant-specific-value\n";

    return $yaml;
}

/**
 * Convert SCIM type to OpenAPI type.
 *
 * @param string $scimtype
 * @return string
 */
function openapi_type(string $scimtype): string {
    if ($scimtype === 'boolean') {
        return 'boolean';
    }
    if ($scimtype === 'complex') {
        return 'object';
    }
    return 'string';
}

/**
 * Build YAML block for core UserBase schema from runtime SCIM core attributes.
 *
 * @param bool $isworkplace
 * @return string
 */
function openapi_userbase_yaml(bool $isworkplace): string {
    $attributes = \local_user_provisioning\scimuserschemaconfigresponse::get_userattributes();

    $overrides = [
      'userName' => 'Unique login identifier. Commonly an email address, but treated as username by this API.',
      'title' => $isworkplace
        ? 'Job position name. Used to create/update Workplace job assignment position; ' .
          'not a personal salutation (Mr/Mrs/Miss).'
        : 'Not used for job assignment on core Moodle. Not a personal salutation (Mr/Mrs/Miss).',
      'preferredLanguage' => 'Maps to Moodle user.lang. Language-country values are normalized to language code ' .
        'and may resolve to *_wp locale when available (for example en-GB -> en or en_wp).',
      'department' => 'Moodle user profile department text field.',
      'organization' => 'Moodle user profile institution field.',
    ];

    $yaml = "    UserBase:\n";
    $yaml .= "      type: object\n";
    $yaml .= "      properties:\n";
    $required = [];

    foreach ($attributes as $attribute) {
        $name = $attribute['name'];
        $type = openapi_type($attribute['type']);
        $description = $overrides[$name] ?? ($attribute['description'] ?? '');

        if (!empty($attribute['required'])) {
            $required[] = $name;
        }

        $yaml .= "        {$name}:\n";

        if (($type === 'object') && !empty($attribute['multiValued'])) {
            $yaml .= "          type: array\n";
            $yaml .= "          items:\n";
            $yaml .= "            type: object\n";
            $yaml .= "            properties:\n";
            foreach (($attribute['subAttributes'] ?? []) as $subattr) {
                $subname = $subattr['name'];
                $subtype = openapi_type($subattr['type']);
                $yaml .= "              {$subname}:\n";
                $yaml .= "                type: {$subtype}\n";

                if (isset($subattr['canonicalValues']) && is_array($subattr['canonicalValues'])) {
                    $enumvalues = implode(', ', $subattr['canonicalValues']);
                    $yaml .= "                enum: [{$enumvalues}]\n";
                }

                if ($name === 'addresses' && $subname === 'locality') {
                    $yaml .= "                description: Maps to Moodle City/town.\n";
                    $yaml .= "                example: London\n";
                } else if ($name === 'addresses' && $subname === 'streetAddress') {
                    $yaml .= "                description: Maps to Moodle Address.\n";
                    $yaml .= "                example: 12 Example Street\n";
                } else if ($name === 'addresses' && $subname === 'country') {
                    $yaml .= "                description: Two-letter ISO 3166-1 alpha-2 country code expected " .
                    "(for example GB, US).\n";
                    $yaml .= "                minLength: 2\n";
                    $yaml .= "                maxLength: 2\n";
                    $yaml .= "                example: GB\n";
                } else if ($name === 'emails' && $subname === 'type') {
                    $yaml .= "                description: Canonical email type.\n";
                } else if ($name === 'phoneNumbers' && $subname === 'value') {
                    $yaml .= "                description: Work maps to Moodle Phone; mobile maps to Moodle Mobile phone.\n";
                    $yaml .= "                example: '+44 20 7946 0958'\n";
                } else if ($name === 'phoneNumbers' && $subname === 'type') {
                    $yaml .= "                description: Canonical phone type.\n";
                }
            }
            continue;
        }

        if ($type === 'object') {
            $yaml .= "          type: object\n";
            $yaml .= "          properties:\n";
            foreach (($attribute['subAttributes'] ?? []) as $subattr) {
                $subname = $subattr['name'];
                $subtype = openapi_type($subattr['type']);
                $yaml .= "            {$subname}:\n";
                $yaml .= "              type: {$subtype}\n";
            }
        } else {
            $yaml .= "          type: {$type}\n";
        }

        if ($description !== '') {
            $yaml .= "          description: " . openapi_yaml_quote($description) . "\n";
        }

        if ($isworkplace && $name === 'title') {
            $yaml .= "          example: Programme Officer\n";
        } else if ($name === 'preferredLanguage') {
            $yaml .= "          example: en-GB\n";
        } else if ($name === 'department') {
            $yaml .= "          example: Supply Division\n";
        } else if ($name === 'organization') {
            $yaml .= "          example: UNICEF\n";
        }
    }

    if (!empty($required)) {
        $yaml .= "      required:\n";
        foreach ($required as $requiredfield) {
            $yaml .= "        - {$requiredfield}\n";
        }
    }

    return $yaml;
}

$scimbase = $CFG->wwwroot .
    '/local/user_provisioning/scim/rest.php/v2';

$customfieldsyaml = openapi_custom_fields_yaml();
$customfieldsexampleyaml = openapi_custom_fields_post_example_yaml();
$isworkplace = \local_user_provisioning\is_moodleworkplace();
$userbaseyaml = openapi_userbase_yaml($isworkplace);
$titlefield = $isworkplace ? "              title: Programme Officer\n" : '';
$enterpriseschemaurn = $isworkplace
  ? "                - urn:ietf:params:scim:schemas:extension:enterprise:2.0:User\n"
  : '';
$enterpriseexampleblock = $isworkplace
  ? "              urn:ietf:params:scim:schemas:extension:enterprise:2.0:User:\n"
    . "                department: Supply Division\n"
    . "                manager:\n"
    . "                  value: ''\n"
  : '';

$workplaceinfoblock = '';
if ($isworkplace) {
    $workplaceinfoblock = "\n    Workplace-specific behavior:\n"
      . "    - title is treated as a job position name (not a salutation such as Mr/Mrs/Miss).\n"
    . "    - there is no separate job object in request payload; job assignment is derived from mapped fields.\n"
    . "    - title maps to position and is used to create/update the user's Workplace job assignment.\n"
      . "    - department (enterprise) maps to the Workplace job assignment department.\n"
    . "    - when title is provided, job-assignment prerequisites must be resolvable "
    . "(frameworks, position, department) or the API returns 400 invalidValue.\n"
    . "    - create requests are handled transactionally across user/profile/workplace DB writes; "
    . "if any step fails, the API fails and DB changes are rolled back.\n"
    . "    - non-DB side effects (for example event observers or outbound notifications triggered by core APIs) "
    . "may still occur and are not rolled back by the DB transaction.";
}

$customfieldshapeblock = "\n    Custom fields payload shape:\n"
  . "    - custom fields belong to 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User'.\n"
  . "    - tenant-specific attributes are sent inside this custom extension object.\n"
  . "    - top-level custom fields are still accepted for backward compatibility.";

echo <<<YAML
openapi: 3.0.3

info:
  title: SCIM User Provisioning API
  version: 1.0.0
  description: |
    SCIM v2 provisioning API for Totara.{$workplaceinfoblock}{$customfieldshapeblock}

servers:
  - url: {$scimbase}

tags:
  - name: Authentication
  - name: Schemas
  - name: ServiceProviderConfig
  - name: Users

paths:

  /token:
    post:
      tags:
        - Authentication

      summary: Generate bearer token

      requestBody:
        required: true

        content:
          application/x-www-form-urlencoded:

            schema:
              type: object

              properties:

                grant_type:
                  type: string
                  example: client_credentials

                client_id:
                  type: string
                  example: tenant-idnumber

                client_secret:
                  type: string

      responses:
        '200':
          description: Token generated successfully

  /ServiceProviderConfig:
    get:
      tags:
        - ServiceProviderConfig

      summary: Get SCIM service provider configuration

      security:
        - bearerAuth: []

      responses:
        '200':
          description: Successful response

  /Schemas:
    get:
      tags:
        - Schemas

      summary: Get supported SCIM schemas

      security:
        - bearerAuth: []

      responses:
        '200':
          description: Successful response

  /Users:
    get:
      tags:
        - Users
      summary: Search users
      security:
        - bearerAuth: []
      parameters:
        - name: filter
          in: query
          required: false
          schema:
            type: string
          description: |
            SCIM filter expression. Supported fields: userName, name.familyName, name.givenName, emails.
            Supported operators:
            - eq (equal) e.g. userName eq "joeb@job.com"
            - neq (not equal) e.g. userName neq "joeb@job.com"
            - co (contains) e.g. userName co "joeb@job.com"
            - sw (starts with) e.g. userName sw "joeb@job.com"
            - ew (ends with) e.g. userName ew "joeb@job.com"
      responses:
        '200':
          description: Successful response
    post:
      tags:
        - Users

      summary: Create user

      security:
        - bearerAuth: []

      requestBody:
        required: true
        content:
          application/json:
            schema:
              allOf:
                - \$ref: '#/components/schemas/UserBase'
                - \$ref: '#/components/schemas/CustomExtensionPayload'
            example:
              schemas:
                - urn:ietf:params:scim:schemas:core:2.0:User
{$enterpriseschemaurn}                - urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User
              userName: alex.doe@example.com
              displayName: Alex Doe
              name:
                givenName: Alex
                middleName: Robin
                familyName: Doe
              emails:
                - value: alex.doe@example.com
                  type: work
                  primary: true
              phoneNumbers:
                - value: '+44 20 7946 0958'
                  type: work
                - value: '+44 7700 900123'
                  type: mobile
              organization: UNICEF
              preferredLanguage: en-GB
              addresses:
                - streetAddress: 12 Example Street
                  locality: London
                  country: GB
                  type: work
                  primary: true
              active: true
              department: Supply Division
{$titlefield}
{$enterpriseexampleblock}              urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User:
                auth: saml2
{$customfieldsexampleyaml}

      responses:
        '201':
          description: User created

  /Users/{idnumber}:
    get:
      tags:
        - Users
      summary: Get user by ID
      security:
        - bearerAuth: []
      parameters:
        - name: idnumber
          in: path
          required: true
          schema:
            type: string
          description: SCIM user identifier mapped to Moodle user.idnumber
      responses:
        '200':
          description: Successful response

    patch:
      tags:
        - Users

      summary: Update user fields

      security:
        - bearerAuth: []

      parameters:
        - name: idnumber
          in: path
          required: true
          schema:
            type: string
          description: SCIM user identifier mapped to Moodle user.idnumber (not numeric user.id).

      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                schemas:
                  type: array
                  items:
                    type: string
                    example: urn:ietf:params:scim:api:messages:2.0:PatchOp
                Operations:
                  type: array
                  items:
                    type: object
                    properties:
                      op:
                        type: string
                        enum: [Add, Replace]
                      path:
                        type: string
                      value:
                        nullable: true
            example:
              schemas:
                - urn:ietf:params:scim:api:messages:2.0:PatchOp
              Operations:
                - op: Replace
                  path: phoneNumbers[type eq "mobile"].value
                  value: '+44 7700 900123'
                - op: Replace
                  path: phoneNumbers[type eq "work"].value
                  value: '+44 20 7946 0958'
                - op: Replace
                  path: name.middleName
                  value: Robin
                - op: Replace
                  path: organization
                  value: UNICEF

      responses:
        '200':
          description: User updated

components:

  schemas:

{$userbaseyaml}

    CustomFields:
      type: object
{$customfieldsyaml}

    CustomExtensionPayload:
      type: object
      properties:
        'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User':
          \$ref: '#/components/schemas/CustomFields'

  securitySchemes:

    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

YAML;
