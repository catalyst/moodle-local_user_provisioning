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
if (!defined('USERPROFILEFIELDTEAM')) {
    define('USERPROFILEFIELDTEAM', 'team');
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

/**
 * Get header Authorization.
 *
 * @return string Returns Authorization header.
 */
function local_user_provisioning_get_authorizationheader() : string {

    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI.
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (function_exists('apache_request_headers')) {
        $apacherequestheaders = (array) apache_request_headers();
        /*
            Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't
            care about capitalization for Authorization).
        */
        $apacherequestheaders = array_combine(
                                    array_map('ucwords', array_keys($apacherequestheaders)), array_values($apacherequestheaders)
                                );

        if (isset($apacherequestheaders['Authorization'])) {
            $headers = trim($apacherequestheaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * Get access token from header.
 *
 * @return null|string
 */
function local_user_provisioning_get_bearertoken() : ? string {

    $headers = local_user_provisioning_get_authorizationheader();

    // HEADER: Get the access token from the header.
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

/**
 * Returns what org the user should be under based on the Bearer token.
 *
 * @return object $orgdetails contains the organisation's id and shortname.
 */
function local_user_provisioning_get_org_details() : object {
    global $DB;

    $bearertoken = local_user_provisioning_get_bearertoken();
    if (!$bearertoken) {
        local_user_provisioning_scim_error_msg(get_string('error:unauthorized_help', 'local_user_provisioning'),
            get_string('error:unauthorized', 'local_user_provisioning'), 401);
    }

    $sql = 'SELECT org.id, org.shortname
              FROM {org} org
              JOIN {oauth_access_tokens} oat ON org.shortname = oat.client_id
             WHERE oat.access_token = :bearertoken
               AND oat.scope = :scope';
    $params = [
        'bearertoken' => $bearertoken,
        'scope' => 'SCIMv2'
    ];
    $orgdetails = $DB->get_record_sql($sql, $params);

    if ($orgdetails) {
        return $orgdetails;
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:forbidden_help', 'local_user_provisioning'),
            get_string('error:forbidden', 'local_user_provisioning'), 403);
    }
}

/**
 * Change int value to true or false.
 *
 * @param ini Suspended
 * @return bool
 */
function local_user_provisioning_isactive(int $suspended) : bool {
    if ($suspended == 1) {
        return false;
    } else {
        return true;
    }
}

/**
 * Common SQL for fetching user record(s). Will be used to get a given user's record or in a user filter query.
 *
 * @return string SQL Script.
 */
function local_user_provisioning_get_userquerysql() : string {

    return "SELECT distinct u.id, u.idnumber, u.firstname, u.lastname, u.email, u.username, u.lang, u.auth, u.department,
                        u.country, u.suspended, u.timecreated, u.timemodified, uid.data AS team, p.fullname AS title,
                        um.id AS managerid, um.firstname AS managerfirstname, um.lastname AS managerlastname
              FROM {user} u
              JOIN (SELECT userid,
                           organisationid,
                           managerjaid,
                           positionid
                      FROM (SELECT DISTINCT ON (userid) *
                              FROM {job_assignment}
                          ORDER BY userid, sortorder ASC
                            ) t
                  ORDER BY sortorder ASC) AS tt ON u.id = tt.userid AND tt.organisationid = :organisationid
              LEFT JOIN {job_assignment} ja ON tt.managerjaid = ja.id
              LEFT JOIN {pos} p ON tt.positionid = p.id
              LEFT JOIN {user} um ON ja.userid = um.id
              LEFT JOIN {user_info_data} uid ON u.id = uid.userid AND fieldid = :fieldid";
}

/**
 * Filter users
 * @return void
 */
function local_user_provisioning_get_users() : void {
    global $DB;

    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    // Get Organisation details.
    $orgdetails = local_user_provisioning_get_org_details();

    $extrasql = '';
    $invalidfilter = false;
    $resources = array();
    $filter = $_GET['filter'];

    if ($filter != "") {
        $filter = str_getcsv($_GET['filter'], " ", '"');

        switch ($filter[0]) {
            case 'userName':
                $extrasql = 'WHERE u.username ';
            break;
            case 'name.familyName':
                $extrasql = 'WHERE u.lastname ';
            break;
            case 'name.givenName':
                $extrasql = 'WHERE u.firstname ';
            break;
            case 'emails':
                $extrasql = 'WHERE u.email ';
            break;
            default:
                $invalidfilter = true;
            break;
        }

        if ($extrasql) {
            switch ($filter[1]) {
                case 'sw':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = $filter[2] . '%';
                break;
                case 'ew':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = '%' . $filter[2];
                break;
                case 'co':
                    $extrasql .= 'LIKE :filter';
                    $params['filter'] = '%' . $filter[2] . '%';
                break;
                case 'eq':
                    $extrasql .= '= :filter';
                    $params['filter'] = $filter[2];
                break;
                case 'neq':
                    $extrasql .= '!= :filter';
                    $params['filter'] = $filter[2];
                break;
                default:
                    $invalidfilter = true;
                break;
            }
        }

        if ($invalidfilter) {
            local_user_provisioning_scim_error_msg(get_string('error:invalifilter_help', 'local_user_provisioning'),
                get_string('error:invalifilter', 'local_user_provisioning'), 400);
        }

        // Custom user profile field - team.
        $params['fieldid'] = 0;
        if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
            $params['fieldid'] = $fieldid;
        }

        $sql = local_user_provisioning_get_userquerysql();
        $sql .= $extrasql . " ORDER BY u.id LIMIT :rowcount";

        $params['organisationid'] = $orgdetails->id;
        $params['rowcount'] = SCIM2_FILTERMAXRECORDS;

        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            $resources[] = new scimuserresponse($record, local_user_provisioning_isactive($record->suspended), false);
        }
    }

    $resp = new scimlistresponse($resources);
    $resp->send_response(200);
}

/**
 * Get given user details.
 *
 * @param array $json this isn't used but must be here because the idnumber is passed as the second parameter.
 * @param int $idnumber User idnumber of the requested user.
 * @return void
 */
function local_user_provisioning_get_user($json, $idnumber) : void {
    global $DB;

    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    // Get Organisation details.
    $orgdetails = local_user_provisioning_get_org_details();

    // Custom user profile field - team.
    $params['fieldid'] = 0;
    if ($fieldid = $DB->get_field('user_info_field', 'id', array('shortname' => USERPROFILEFIELDTEAM))) {
        $params['fieldid'] = $fieldid;
    }

    $sql = local_user_provisioning_get_userquerysql();
    $sql .= " WHERE u.suspended = :suspended
                AND u.idnumber = :idnumber";

    $params['organisationid'] = $orgdetails->id;
    $params['suspended'] = 0;
    $params['idnumber'] = $idnumber;

    if ($record = $DB->get_record_sql($sql, $params)) {
        $resp = new scimuserresponse($record, local_user_provisioning_isactive($record->suspended), true);
        $resp->send_response(200);
    } else {
        local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber),
                get_string('error:notfound', 'local_user_provisioning'), 404);
    }

}

/**
 * Suspend given user.
 *
 * @param array $json this isn't used but must be here because the idnumber is passed as the second parameter.
 * @param string $idnumber User idnumber of the requested user.
 * @return void
 */
function local_user_provisioning_suspend_user(array $json, string $idnumber) : void {
    global $DB;

    // Validate the Bearer token.
    local_user_provisioning_validatetoken();

    // Get Organisation details.
    $orgdetails = local_user_provisioning_get_org_details();

    $sql = "SELECT u.id
              FROM {user} u
              JOIN (SELECT userid,
                           organisationid,
                           managerjaid,
                           positionid
                      FROM (SELECT DISTINCT ON (userid) *
                              FROM {job_assignment}
                          ORDER BY userid, sortorder ASC
                            ) t
                  ORDER BY sortorder ASC) AS tt ON u.id = tt.userid AND tt.organisationid = :organisationid
             WHERE idnumber = :idnumber";
    $params['organisationid'] = $orgdetails->id;
    $params['idnumber'] = $idnumber;

    if ($userid = $DB->get_field_sql($sql, $params)) {
        $DB->set_field('user', 'suspended', 1, array('id' => $userid));
        local_user_provisioning_scim_error_msg(get_string('nocontent_help', 'local_user_provisioning', $idnumber),
                    get_string('nocontent', 'local_user_provisioning'), 204);
    }

    local_user_provisioning_scim_error_msg(get_string('error:usernotfound', 'local_user_provisioning', $idnumber), '?', 404);
}
