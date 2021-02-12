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

define('AJAX_SCRIPT', true);

require_once('../../../config.php');
require_once('lib/AltoRouter.php');
require_once('../lib.php');

$router = new \AltoRouter();
$router->setBasePath(scimserviceconfigresponse::SCIM2_BASE_URL);

// Routes.
// Generate Bearer token.
$router->map('POST', '/v2/token', 'local_user_provisioning_token');
// SCIM Service Provider Configuration.
$router->map('GET', '/v2/ServiceProviderConfig', 'local_user_provisioning_get_serviceproviderconfig');
// SCIM Schema.
$router->map('GET', '/v2/Schemas', 'local_user_provisioning_get_schemas');
// User Schema.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_USER_URN, 'local_user_provisioning_get_userschema');
// Enterprise User Schema.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_ENTERPRISE_USER_EXT,
                'local_user_provisioning_get_entuserschema');
// Custom Extention Schema - defines custom fields that are not part of SCIM v2.
$router->map('GET', '/v2/Schemas/' . scimserviceconfigresponse::SCIM2_CUSTOM_USER_URN,
                'local_user_provisioning_get_custuserschema');
// Filter users.
$router->map('GET', '/v2/Users', 'local_user_provisioning_get_users');

/*
 * Match the route and decode the json. All target functions should accept json associative array as the
 * first input followed by the appropriate number of arguments as defined in the routing table above.
 */
$match = $router->match();

$target = __NAMESPACE__ . '\\' . $match['target'];

if ($match && is_callable($target)) {
    if ($target !== 'local_user_provisioning\local_user_provisioning_token') {
        $body = file_get_contents('php://input');
        if ($body) {
            $data = json_decode($body, true);
            if (json_last_error()) {
                local_user_provisioning_scim_error_msg(json_last_error_msg(),
                    get_string('error:badrequest', 'local_user_provisioning'), 400);
            }
        } else {
            $data = array();
        }
    }
    call_user_func_array($target, array_merge(array($data), $match['params']));
} else {
    local_user_provisioning_scim_error_msg(get_string('error:invaliduri', 'local_user_provisioning'), '?', 404);
}