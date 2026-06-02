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
 * moodle-local_user_provisioning settings.
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jonathan Hatton <jonathan.hatton@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/user_provisioning/extensions/bootstrap.php');

if ($hassiteconfig) {
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_user_provisioning_settings',
            new lang_string('pluginname', 'local_user_provisioning')
        )
    );

    $settingspage = new admin_settingpage('local_user_provisioning', new lang_string('pluginname', 'local_user_provisioning'));

    if ($ADMIN->fulltree) {
        $settingspage->add(new admin_setting_configcheckbox(
            'local_user_provisioning/enabled_debug',
            new lang_string('enabled_debug', 'local_user_provisioning'),
            new lang_string('enabled_desc', 'local_user_provisioning'),
            0
        ));

        $settingspage->add(new admin_setting_configtext(
            'local_user_provisioning/debug_email',
            new lang_string('debug_email_address', 'local_user_provisioning'),
            new lang_string('debug_email_desc', 'local_user_provisioning'),
            new lang_string('debug_email_address_default', 'local_user_provisioning'),
            core\param::EMAIL->value
        ));

        $settingspage->add(new admin_setting_heading(
            'local_user_provisioning_docs',
            'SCIM API Documentation',
            html_writer::link(
                new moodle_url('/local/user_provisioning/docs/scim.php'),
                'View Swagger documentation'
            )
        ));

        $customfieldssetting = new admin_setting_configtextarea(
            'local_user_provisioning/custom_fields',
            new lang_string('custom_fields', 'local_user_provisioning'),
            new lang_string('custom_fields_desc', 'local_user_provisioning'),
            '',
            PARAM_RAW,
            80,
            12
        );
        $customfieldssetting->set_updatedcallback('\\local_user_provisioning\\local_user_provisioning_custom_fields_updated');
        $settingspage->add($customfieldssetting);

        if (\local_user_provisioning\local_user_provisioning_is_moodleworkplace()) {
            $tenants = \tool_tenant\tenant::get_records(['archived' => 0]);

            foreach ($tenants as $tenant) {
                $tenantid = $tenant->get('id');
                $settingspage->add(
                    new admin_setting_heading(
                        "local_user_provisioning/tenant_{$tenantid}_header",
                        get_string('tenant', 'local_user_provisioning', $tenant->get('name')),
                        get_string('tenant_desc', 'local_user_provisioning')
                    )
                );

                $tenantcustomfieldssetting = new admin_setting_configtextarea(
                    "local_user_provisioning/tenant_{$tenantid}_custom_fields",
                    new lang_string('tenant_custom_fields', 'local_user_provisioning'),
                    new lang_string('tenant_custom_fields_desc', 'local_user_provisioning'),
                    '',
                    PARAM_RAW,
                    80,
                    8
                );
                $tenantcustomfieldssetting->set_updatedcallback(
                    '\\local_user_provisioning\\local_user_provisioning_custom_fields_updated'
                );
                $settingspage->add($tenantcustomfieldssetting);

                $posframeworks = \tool_organisation\position::get_records(
                    ['archived' => 0, 'parentid' => null, 'tenantid' => $tenant->get('id')]
                );

                $deptframeworks = \tool_organisation\department::get_records(
                    ['archived' => 0, 'parentid' => null, 'tenantid' => $tenant->get('id')]
                );

                if ($posframeworks) {
                    $options = [];
                    foreach ($posframeworks as $posframework) {
                        $options[$posframework->get('id')] = $posframework->get('name');
                        $settingspage->add(
                            new admin_setting_configselect(
                                "local_user_provisioning/tenant_{$tenantid}_position",
                                new lang_string('positionframework', 'local_user_provisioning'),
                                new lang_string('positionframework_help', 'local_user_provisioning'),
                                0,
                                $options
                            )
                        );
                    }
                } else {
                    $settingspage->add(
                        new admin_setting_description(
                            "positionframework{$tenant->get('id')}",
                            get_string('positionframework', 'local_user_provisioning'),
                            new lang_string('nopositionframework', 'local_user_provisioning', $tenant->get('name'))
                        )
                    );
                }

                if ($deptframeworks) {
                    $options = [];
                    foreach ($deptframeworks as $deptframework) {
                        $options[$deptframework->get('id')] = $deptframework->get('name');
                        $settingspage->add(
                            new admin_setting_configselect(
                                "local_user_provisioning/tenant_{$tenantid}_department",
                                new lang_string('departmentframework', 'local_user_provisioning'),
                                new lang_string('departmentframework_help', 'local_user_provisioning'),
                                0,
                                $options
                            )
                        );
                    }
                } else {
                    $settingspage->add(
                        new admin_setting_description(
                            "departmentframework{$tenant->get('id')}",
                            get_string('departmentframework', 'local_user_provisioning'),
                            new lang_string('nodepartmentframework', 'local_user_provisioning', $tenant->get('name'))
                        )
                    );
                }
            }
        }
    }

    $ADMIN->add('localplugins', $settingspage);
}
