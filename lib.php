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
use \stdClass;
defined('MOODLE_INTERNAL') || die();

if (!defined('SCIM2_FILTERMAXRECORDS')) {
    define('SCIM2_FILTERMAXRECORDS', 25);
}

ini_set('html_errors', false);

/**
 * Prints out a scim error message
 *
 * @param string $message       A text representation of what the message is
 * @param string $type          The scimType of the error message
 * @param int    $responsecode  The http status code - defaults to `500`
 * @return void
 */
function local_user_provisioning_scim_error_msg(string $message, string $type, int $responsecode=500) : void {
    $resp = new scimerrorresponse($message, $type, $responsecode);
    $resp->send_response($responsecode);
    die; // Stop processing the request.
}

/**
 * OAuth2 server
 *
 * @return object OAuth2 server object
 */
function local_user_provisioning_server() : object {
    global $CFG;

    // Autoloading (composer is preferred, but for this example let's just do this).
    require_once($CFG->dirroot . '/local/oauth/OAuth2/Autoloader.php');
    \OAuth2\Autoloader::register();

    $storage = new \OAuth2\Storage\Moodle(array());
    // Pass a storage object or array of storage objects to the OAuth2 server class.
    $server = new \OAuth2\Server($storage);
    $server->setConfig('enforce_state', true);
    $server->setConfig('access_lifetime', 3155760000); // A century - Long-lived expiry time.

    // Add the "Client Credentials" grant type (it is the simplest of the grant types).
    $server->addGrantType(new \OAuth2\GrantType\ClientCredentials($storage));

    // Add the "Authorization Code" grant type (this is where the oauth magic happens).
    $server->addGrantType(new \OAuth2\GrantType\AuthorizationCode($storage));

    return $server;
}

/**
 * Token endpoint - validate OAuth2 client_credentials authorization and issue token.
 *
 * @return void
 */
function local_user_provisioning_token() : void {

    // Existing session (if any started) needs to be closed to generate a new token.
    \core\session\manager::write_close();
    $server = local_user_provisioning_server();
    $server->handleTokenRequest(\OAuth2\Request::createFromGlobals())->send();

}

/**
 * Validate Bearer token.
 *
 * @return void
 */
function local_user_provisioning_validatetoken() : void {

    // Existing session (if any started) needs to be closed to validate the token.
    \core\session\manager::write_close();

    $server = local_user_provisioning_server();

    if (!$server->verifyResourceRequest(\OAuth2\Request::createFromGlobals())) {
        $verificationerror = $server->getResponse();
        if ($verificationerror) {
            $validationmessage = $verificationerror->getParameter('error_description');
        }
        if (!isset($validationmessage)) {
            $validationmessage = get_string('error:unauthorized_help', 'local_user_provisioning');
        }
        local_user_provisioning_scim_error_msg($validationmessage,
            get_string('error:unauthorized', 'local_user_provisioning'), 401);
    }

}

/**
 * SCIM Schemas.
 * @return void
 */
function local_user_provisioning_get_schemas() : void {
    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    $resp = new scimschemaconfigresponse('Schemas');
    $resp->send_response(200);
}

/**
 * SCIM User Schema.
 * @return JSON
 */
function local_user_provisioning_get_entuserschema() : void {
    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    $resp = new scimentschemaconfigresponse('UserEnterpriseSchema');
    $resp->send_response(200);
}

/**
 * SCIM User Entreprise Schema.
 * @return JSON
 */
function local_user_provisioning_get_userschema() : void {
    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    $resp = new scimuserschemaconfigresponse('UserSchema');
    $resp->send_response(200);
}

/**
 * SCIM Custom User Extention Schema.
 * @return JSON
 */
function local_user_provisioning_get_custuserschema() : void {
    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    $resp = new scimcustschemaconfigresponse('UserCustomSchema');
    $resp->send_response(200);
}

/**
 * SCIM Service Provider Configuration.
 * @return void
 */
function local_user_provisioning_get_serviceproviderconfig() : void {
    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    $resp = new scimserviceconfigresponse('ServiceConfig');
    $resp->send_response(200);
}
