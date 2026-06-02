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
 * Local user provisioning extension bootstrap.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_provisioning;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/workplace.php');
require_once(__DIR__ . '/registry.php');
require_once(__DIR__ . '/profile_fields.php');
require_once(__DIR__ . '/hooks.php');
