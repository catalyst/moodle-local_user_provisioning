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

$string['pluginname'] = 'SCIM User Provisioning - Bearer token';

$string['oauth2bearer'] = 'OAuth Bearer Token';
$string['oauth2bearer_desc'] = 'Authentication scheme using the OAuth Bearer Token Standard 2.0';

// Error...
$string['error:badrequest'] = 'Bad Request'; // Error code 400.
$string['error:badrequest_help'] = 'Request is unparsable, syntactically incorrect, or violates schema.';
$string['error:unauthorized'] = 'Unauthorized'; // Error code 401.
$string['error:unauthorized_help'] = 'Authorization failure.  The authorization header is invalid or missing.';
$string['error:forbidden'] = 'Forbidden'; // Error code 403.
$string['error:forbidden_help'] = 'Operation is not permitted based on the supplied authorization.';
$string['error:notfound'] = 'Not Found'; // Error code 404.
$string['error:notfound_help'] = 'Specified resource or endpoint does not exist.';
$string['error:userexists'] = 'User already exists.';
$string['error:invaliduri'] = 'Invalid request uri.';
$string['error:invalifilter'] = 'invalidFilter';
$string['error:invalifilter_help'] = 'The specified filter syntax was invalid or the specified attribute and filter comparison combination is not supported.';
$string['error:usernotfound'] = 'User {$a} not found';
$string['nocontent'] = 'nocontent';
$string['nocontent_help'] = 'No content';
