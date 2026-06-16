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
 * English language strings for local user provisioning.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

$string['addclient'] = 'Add client';
$string['client_id'] = 'Client identifier';
$string['client_id_existing_error'] = 'The Client identifier specified already exists, please choose another one';
$string['client_id_help'] = 'Client ID must match the idnumber of the tenant it will be targeting.';
$string['client_not_exists'] = 'Client does not exist';
$string['client_secret'] = 'Client secret Key';
$string['clients'] = 'Clients';
$string['confirmdeletestr'] = 'Are you sure you want to delete client {$a}?';
$string['custom_fields'] = 'Custom SCIM fields';
$string['custom_fields_desc'] = 'Define custom fields without code changes. One field per line using: shortname|Label|unique. The unique part is optional (accepted values: 1, true, yes, y, unique). Example: pernr|PERNR|unique';
$string['debug_email_address'] = 'Debug email address';
$string['debug_email_address_default'] = '';
$string['debug_email_desc'] = 'Recipient email address for debug information (mandatory if debugging enabled)';
$string['delete'] = 'Delete';
$string['delete_error'] = 'Error occurred deleting client';
$string['delok'] = 'Client successfully deleted';
$string['email_body_text1'] = 'PATH INFO = ';
$string['email_body_text2'] = 'REQUEST URI = ';
$string['email_body_text3'] = 'QUERY STRING = ';
$string['email_body_text4'] = 'REQUEST METHOD = ';
$string['email_body_text5'] = 'API JSON = ';
$string['email_subject'] = 'Local_User_Provisioning API debugging Email';
$string['enabled_debug'] = 'Enable debugging';
$string['enabled_desc'] = 'Enable Debugging for User Provisioning';
$string['grant_types'] = 'Grant Types';
$string['httpbasic'] = 'HTTP Basic';
$string['httpbasic_desc'] = 'Authentication scheme using the HTTP Basic Standard';
$string['insert_error'] = 'Error occurred creating client';
$string['nocontent'] = 'nocontent';
$string['nocontent_help'] = 'No content';
$string['oauth2bearer'] = 'OAuth Bearer Token';
$string['oauth2bearer_desc'] = 'Authentication scheme using the OAuth Bearer Token Standard 2.0';
$string['pluginname'] = 'SCIM User Provisioning';
$string['privacy:metadata'] = 'The local user provisioning plugin does not store any personal data.';
$string['redirect_uri'] = 'Redirect URL';
$string['saveok'] = 'Client successfully saved';
$string['scope'] = 'Scope';
$string['settings'] = 'Settings';
$string['tenant_custom_fields'] = 'Tenant custom SCIM fields';
$string['tenant_custom_fields_desc'] = 'Optional tenant-specific custom SCIM fields. These are added to global custom fields for requests authenticated to this tenant. One field per line using: shortname|Label|unique';
$string['update_error'] = 'Error occurred updating client data';
$string['user_id'] = 'User ID';



// SCIM attribute descriptions.
// Core user fields.
$string['attr-active'] = "User profile - Suspended account (inverted: active=true means not suspended).";
$string['attr-auth'] = "User profile - Authentication method.";
$string['attr-department'] = "User profile - Department.";
$string['attr-displayname'] = "User profile - Alternate name.";
$string['attr-organization'] = "User profile - Institution.";
$string['attr-preferredlanguage'] = "User preference - Preferred language (e.g., 'en_US').";
$string['attr-username'] = "User profile - Username. Must be unique.";

// Name component.
$string['attr-name'] = "SCIM name component.";
$string['attr-name-familyname'] = "User profile - Last name.";
$string['attr-name-givenname'] = "User profile - First name.";
$string['attr-name-middlename'] = "User profile - Middle name.";

// Email component.
$string['attr-emails'] = "SCIM emails component.";
$string['attr-emails-primary'] = "Only the primary email address is mapped to Moodle.";
$string['attr-emails-type'] = "Label indicating function (e.g., 'work' or 'home').";
$string['attr-emails-value'] = "User profile - Email address.";

// Address component.
$string['attr-addresses'] = "SCIM addresses component.";
$string['attr-addresses-country'] = "User profile - Country.";
$string['attr-addresses-locality'] = "User profile - City/town.";
$string['attr-addresses-streetaddress'] = "User profile - Address.";
$string['attr-addresses-type'] = "Label indicating function (e.g., 'work' or 'home').";

// Phone component.
$string['attr-phonenumbers'] = "SCIM phone numbers component.";
$string['attr-phonenumbers-type'] = "Label indicating type (e.g., 'work' or 'mobile').";
$string['attr-phonenumbers-value'] = "Work maps to User profile - Phone, mobile maps to User profile - Mobile phone.";

// Workplace only.
$string['attr-division'] = "Workplace only: Job department";
$string['attr-manager'] = "SCIM resource URI referencing the user's manager.";
$string['attr-manager-value'] = "Workplace only: populates job assignment manager. Manager's Moodle idnumber.";
$string['attr-title'] = "Workplace only: Job assignment - Position.";

// Moodle Workplace.
$string['departmentframework'] = 'Department framework';
$string['departmentframework_help'] = 'This will be the department framework used to add new department that are for user that are created as part of user provisioning';
$string['nodepartmentframework'] = 'Please add framework to tenant "{$a}" to configure this';
$string['nopositionframework'] = 'Please add framework to tenant "{$a}" to configure this';
$string['positionframework'] = 'Position framework';
$string['positionframework_help'] = 'This will be the position framework used to add new positions that are for user that are created as part of user provisioning';
$string['tenant'] = 'Tenant: {$a}';
$string['tenant_desc'] = 'You must configure a position and department framework here to be able to use the API for that tenant.';

// Error.
$string['error:badrequest'] = 'Bad Request'; // Error code 400.
$string['error:badrequest_help'] = 'Request is unparsable, syntactically incorrect, or violates schema.';
$string['error:customfieldexists'] = '{$a->field} already exists.';
$string['error:customfieldnotunique'] = '{$a->field} must be set to Unique in the Moodle profile field configuration.';
$string['error:forbidden'] = 'Forbidden'; // Error code 403.
$string['error:forbidden_help'] = 'Operation is not permitted based on the supplied authorization.';
$string['error:invalidauth'] = 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User auth not supported';
$string['error:invalifilter'] = 'invalidFilter';
$string['error:invalifilter_help'] = 'The specified filter syntax was invalid or the specified attribute and filter comparison combination is not supported.';
$string['error:invalidmanager'] = 'Manager set for this user is invalid';
$string['error:invaliduri'] = 'Invalid request uri.';
$string['error:missingemail'] = 'emails.value is missing in the request body';
$string['error:missingfirstname'] = 'name.givenName is missing in the request body';
$string['error:missinglastname'] = 'name.familyName is missing in the request body';
$string['error:missingusername'] = 'userName is missing in the request body';
$string['error:nodepartmentframe'] = 'Job assignment failed: no department framework found with id: {$a}';
$string['error:nodepartmentframeset'] = 'Job assignment failed: no department framework set in site config. Please raise with Moodle site admin';
$string['error:nodeptfound'] = 'Job assignment failed: no department found in framework id: {$a->frameid} with idnumber: {$a->idnumber}';
$string['error:nopositionfound'] = 'Job assignment failed: no position found in framework id: {$a->frameid} with name: {$a->name}';
$string['error:nopositionframe'] = 'Job assignment failed: no position framework found with id: {$a}';
$string['error:nopositionframeset'] = 'Job assignment failed: no position framework set in site config. Please raise with Moodle site admin';
$string['error:notenant'] = 'No tenant found linked to this bearer token';
$string['error:notfound'] = 'Not Found'; // Error code 404.
$string['error:notfound_help'] = 'Specified resource or endpoint does not exist.';
$string['error:orgclass'] = 'No organisation class found for {$a}';
$string['error:unauthorized'] = 'Unauthorized'; // Error code 401.
$string['error:unauthorized_help'] = 'Authorization failure. The authorization header is invalid or missing.';
$string['error:userexists'] = 'User already exists.';
$string['error:usernotfound'] = 'User {$a} not found';
$string['warning:duplicatecustomfieldkeys'] = 'Duplicate custom field keys detected across global and tenant settings: {$a}. Separate tenant profile fields cannot be created for these keys without prefixes.';
