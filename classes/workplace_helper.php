<?php
// This file is part of Moodle - https://moodle.org/
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

namespace local_user_provisioning;

use stdClass;
use tool_organisation\helper;
use tool_organisation\job;
use tool_organisation\local\helpers\user_manager;
use tool_organisation\department;
use tool_organisation\organisation;
use tool_organisation\position;
use tool_tenant\tenant;

/**
 * Helper class for moodle workplace type functions needed by the API.
 *
 * @package     local_user_provisioning
 * @copyright   2025 Simon Adams <simon.adams@catalyst-eu.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workplace_helper {
    /**
     * @var tenant $tenant attached to the oauth configuration client_id.
     */
    public tenant $tenant;

    /**
     * @var string token associated with the client_id.
     */
    private string $bearertoken;

    /**
     * Initialise the service with the tenant attached to the oauth config.
     *
     * @param string $bearertoken Calling code must verify authentication.
     */
    public function __construct(string $bearertoken) {
        $this->bearertoken = $bearertoken;
        $this->tenant = $this->get_tenant();
    }

    /**
     * Get the tenant associated with the bearertoken configuration.
     *
     * @return tenant
     */
    public function get_tenant(bool $sendscimerror = true): tenant {
        global $DB;

        if (!empty($this->tenant)) {
            return $this->tenant;
        }

        $tenantidnumber = $DB->get_field(
            'oauth_access_tokens',
            'client_id',
            ['access_token' => $this->bearertoken, 'scope' => SCIM_VERSION]
        );

        if (!$tenant = tenant::get_record(['idnumber' => $tenantidnumber])) {
            if (!$sendscimerror) {
                throw new \coding_exception('Tenant resolution failed.');
            }
            local_user_provisioning_scim_error_msg(get_string('error:notenant', 'local_user_provisioning'), '');
        }

        $this->tenant = $tenant;
        return $this->tenant;
    }

    /**
     * Add/move user to the tenant.
     *
     * @param int $userid user id.
     * @return void
     */
    public function update_user_tenant(int $userid, bool $sendscimerror = true): void {
        $tenantmanager = new \tool_tenant\manager();
        $tenantmanager->allocate_user($userid, $this->get_tenant($sendscimerror)->get('id'), 'local_user_provisioning', SCIM_VERSION);
    }

    /**
     * Add/update/delete manager for a user.
     * MWP supports more than 1 manager, but we are going to limit this over the API to 1 manager per user.
     *
     * If the managerid argument is null then remove all managers.
     * If the managerid argument is not null then keep exactly one assignment for that manager.
     *
     * @param int $userid
     * @param int|null $managerid
     * @return void
     */
    public function update_user_manager(int $userid, ?int $managerid = null, bool $sendscimerror = true) {

        // Define octal permissions.
        $permissions = organisation::PERM_ALLOCATE_PROGRAMS
            + organisation::PERM_VIEW_REPORTS
            + organisation::PERM_RECEIVE_NOTIFICATIONS;

        // Get all assigned managers for this user in this tenant.
        $usermanagerrecords = \tool_organisation\local\persistent\user_manager::get_records(
            filters: [
                'userid' => $userid,
                'tenantid' => $this->get_tenant($sendscimerror)->get('id'),
            ],
            order: 'id DESC'
        );

        // If the user already has exactly this manager assigned, nothing to do.
        if ($managerid && count($usermanagerrecords) === 1
                && $usermanagerrecords[0]->get('managerid') === $managerid) {
            return;
        }

        // The API exposes per-manager delete only; manager count is intentionally bounded to small sets here.
        // Always clear existing assignments first so we end up with zero or one manager only.
        foreach ($usermanagerrecords as $usermanagerrecord) {
            user_manager::delete_assigned_manager($userid, $usermanagerrecord->get('managerid'));
        }

        if (!$managerid) {
            return;
        }

        // Add exactly one manager assignment.
        $manager = new stdClass();
        $manager->id = $managerid;
        $manager->tenantid = $this->tenant->get('id');
        $manager->permissions = $permissions;
        user_manager::add_assigned_manager($userid, $manager);
    }

    /**
     * Assigns a tenant-scoped job to a user.
     * The position must exist in the configured framework and is matched on name.
     * The department is derived from the tenant idnumber and matched in the configured
     * department framework.
     *
     * @param int $userid User id.
     * @param string $posname of the position to match on and assign.
     * @return void
     */
    public function assign_job(int $userid, string $posname = '', bool $sendscimerror = true): void {

        $currenttime = time();
        $tenantid = $this->get_tenant($sendscimerror)->get('id');

        $jobmanager = new \tool_organisation\job_manager();
        $newjob = new stdClass();
        $newjob->tenantid = $tenantid;
        $newjob->userid = $userid;
        $newjob->startdate = helper::round_time($currenttime);

        $deptframeid = $this->get_department_framework_id($sendscimerror);
        $posframeid = $this->get_position_framework_id($sendscimerror);

        // Find the most recent position in the configured framework by name.
        $positions = position::get_records(['name' => $posname, 'parentid' => $posframeid], order: 'id DESC');
        $pos = reset($positions);
        if (!$pos) {
            if (!$sendscimerror) {
                throw new \coding_exception('Position resolution failed.');
            }
            local_user_provisioning_scim_error_msg(
                get_string(
                    'error:nopositionfound',
                    'local_user_provisioning',
                    ['frameid' => $posframeid, 'name' => $posname]
                ),
                'invalidValue',
                400
            );
        }

        // Find the department in the framework that matches the tenant idnumber.
        $tenant = tenant::get_record(['id' => $tenantid], MUST_EXIST);
        if (!$dept = \tool_organisation\department::get_record(['idnumber' => $tenant->get('idnumber')])) {
            if (!$sendscimerror) {
                throw new \coding_exception('Department resolution failed.');
            }
            local_user_provisioning_scim_error_msg(
                get_string(
                    'error:nodeptfound',
                    'local_user_provisioning',
                    ['frameid' => $deptframeid, 'idnumber' => $tenant->get('idnumber')]
                ),
                'invalidValue',
                400
            );
        }

        // Keep the most recent job using same pos and dept and set the enddate for any other jobs if we have any.
        $mostrecentjobfound = false;
        $allcurrentjobs = job::get_records(['enddate' => 0, 'tenantid' => $tenantid, 'userid' => $userid], order: 'id DESC');
        // Job counts per user are expected to be small; close non-current jobs one by one via persistent API.
        foreach ($allcurrentjobs as $currentjob) {
            $samejobcombo = (int)$currentjob->get('positionid') === (int)$pos->get('id') &&
                (int)$currentjob->get('departmentid') === (int)$dept->get('id');

            if (!$mostrecentjobfound && $samejobcombo) {
                $mostrecentjobfound = true;
                continue;
            }
            $currentjob->set('enddate', helper::round_time($currenttime));
            $currentjob->save();
        }

        if ($mostrecentjobfound) {
            return;
        }

        // User does not have this pos, dept combo so create the new job.
        $newjob->positionid = $pos->get('id');
        $newjob->departmentid = $dept->get('id');
        $jobmanager->create_job($newjob, false);
    }

    /**
     * Get the frameworkid that is being used for department matching.
     *
     * @return int
     */
    public function get_department_framework_id(bool $sendscimerror = true): int {
        return $this->get_framework_id('department', $sendscimerror);
    }

    /**
     * Get the frameworkid that is being used for position matching.
     *
     * @return int
     */
    public function get_position_framework_id(bool $sendscimerror = true): int {
        return $this->get_framework_id('position', $sendscimerror);
    }

    /**
     * Get the frameworkid that is being used for department and position matching.
     *
     * @param string $type
     * @return int
     */
    protected function get_framework_id(string $type, bool $sendscimerror = true): int {

        $tenantid = $this->get_tenant($sendscimerror)->get('id');

        if (!$configframeid = get_config('local_user_provisioning', "tenant_{$tenantid}_{$type}")) {
            if (!$sendscimerror) {
                throw new \coding_exception('Framework configuration missing.');
            }
            local_user_provisioning_scim_error_msg(
                get_string("error:no{$type}frameset", 'local_user_provisioning'),
                'invalidValue',
                400
            );
        }
        $class = "\\tool_organisation\\{$type}";

        if (!class_exists($class)) {
            if (!$sendscimerror) {
                throw new \coding_exception('Organisation class missing.');
            }
            local_user_provisioning_scim_error_msg(
                get_string("error:orgclass", 'local_user_provisioning', $class),
                'invalidValue',
                400
            );
        }

        if (!$class::record_exists($configframeid)) {
            if (!$sendscimerror) {
                throw new \coding_exception('Framework record missing.');
            }
            local_user_provisioning_scim_error_msg(
                get_string("error:no{$type}frame", 'local_user_provisioning', $configframeid),
                'invalidValue',
                400
            );
        }
        return $configframeid;
    }
}
