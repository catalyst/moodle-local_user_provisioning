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
 * Workplace helper functions shared across extension and runtime scripts.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_provisioning;

defined('MOODLE_INTERNAL') || die();

/**
 * Whether Moodle Workplace organisation/tenant features are available.
 *
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_is_moodleworkplace(): bool {
    return class_exists('\\tool_tenant\\tenancy') && class_exists('\\local_user_provisioning\\workplace_helper');
}

/**
 * Backward-compatible alias used by extension helpers.
 *
 * @return bool
 * @package local_user_provisioning
 */
function local_user_provisioning_custom_is_moodleworkplace(): bool {
    return local_user_provisioning_is_moodleworkplace();
}
