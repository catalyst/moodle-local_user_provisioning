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
 * Unit tests for lib functions.
 *
 * @package   local_user_provisioning
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_provisioning;

use stdClass;

/**
 * Unit testing class for lib.php.
 */
final class lib_test extends \advanced_testcase {
    /**
     * Ensure lib.php is loaded for direct function calls.
     */
    private function ensure_lib_loaded(): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/user_provisioning/lib.php');
    }

    /**
     * Build a minimal valid create/update payload.
     *
     * @return array
     */
    private function get_minimal_valid_payload(): array {
        return [
            'schemas' => [
                scimresponse::SCIM2_USER_URN,
                scimresponse::SCIM2_CUSTOM_USER_URN,
            ],
            'userName' => 'scimtester',
            'name' => [
                'givenName' => 'Scim',
                'familyName' => 'Tester',
            ],
            'emails' => [
                [
                    'value' => 'scimtester@example.com',
                    'type' => 'work',
                    'primary' => true,
                ],
            ],
            'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User' => [
                'auth' => 'manual',
            ],
        ];
    }

    /**
     * Read-only SCIM fields should be ignored during create/update validation.
     */
    public function test_validate_data_ignores_readonly_fields(): void {
        $this->resetAfterTest(true);
        $this->ensure_lib_loaded();

        $payload = $this->get_minimal_valid_payload();
        $payload['id'] = 'server-generated-id';
        $payload['externalId'] = 'client-external-id';
        $payload['meta'] = ['resourceType' => 'User'];
        $payload['groups'] = [['value' => 'group1']];

        $user = validate_data($payload, 'add', new stdClass());

        $this->assertFalse(property_exists($user, 'errors'));
    }

    /**
     * Unknown top-level fields should still hard-fail.
     */
    public function test_validate_data_rejects_unknown_top_level_field(): void {
        $this->resetAfterTest(true);
        $this->ensure_lib_loaded();

        $payload = $this->get_minimal_valid_payload();
        $payload['totallyUnknownField'] = 'x';

        $user = validate_data($payload, 'add', new stdClass());

        $this->assertTrue(property_exists($user, 'errors'));
        $errors = implode("\n", $user->errors);
        $this->assertStringContainsString('Unsupported field in payload: totallyUnknownField', $errors);
    }

    /**
     * Invalid schema URI in create/update payload should be rejected.
     */
    public function test_validate_data_rejects_invalid_schema_uri(): void {
        $this->resetAfterTest(true);
        $this->ensure_lib_loaded();

        $payload = $this->get_minimal_valid_payload();
        $payload['schemas'] = ['urn:example:invalid'];

        $user = validate_data($payload, 'add', new stdClass());

        $this->assertTrue(property_exists($user, 'errors'));
        $errors = implode("\n", $user->errors);
        $this->assertStringContainsString('Invalid schema URI: urn:example:invalid', $errors);
    }

    /**
     * Read-only PATCH paths should be ignored, but unknown paths must fail.
     */
    public function test_validate_datafields_ignores_readonly_paths_and_rejects_unknown(): void {
        $this->resetAfterTest(true);
        $this->ensure_lib_loaded();

        $user = new stdClass();

        $payload = [
            'schemas' => [SCIM2_PATCHOP_URN],
            'Operations' => [
                [
                    'op' => 'replace',
                    'path' => 'meta.version',
                    'value' => 'v2',
                ],
                [
                    'op' => 'replace',
                    'path' => 'groups[value eq "x"].display',
                    'value' => 'Group X',
                ],
                [
                    'op' => 'replace',
                    'path' => 'completely.unknown.path',
                    'value' => 'x',
                ],
            ],
        ];

        $validated = validate_datafields($payload, $user);

        $this->assertTrue(property_exists($validated, 'errors'));
        $errors = implode("\n", $validated->errors);
        $this->assertStringNotContainsString('Unsupported PATCH path: meta.version', $errors);
        $this->assertStringNotContainsString('Unsupported PATCH path: groups[value eq "x"].display', $errors);
        $this->assertStringContainsString('Unsupported PATCH path: completely.unknown.path', $errors);
    }

    /**
     * PATCH schemas must contain PatchOp URN.
     */
    public function test_validate_datafields_rejects_invalid_patch_schema_uri(): void {
        $this->resetAfterTest(true);
        $this->ensure_lib_loaded();

        $user = new stdClass();

        $payload = [
            'schemas' => ['urn:example:invalid'],
            'Operations' => [
                [
                    'op' => 'replace',
                    'path' => 'displayName',
                    'value' => 'Valid Name',
                ],
            ],
        ];

        $validated = validate_datafields($payload, $user);

        $this->assertTrue(property_exists($validated, 'errors'));
        $errors = implode("\n", $validated->errors);
        $this->assertStringContainsString('Invalid schema URI: urn:example:invalid', $errors);
    }
}
