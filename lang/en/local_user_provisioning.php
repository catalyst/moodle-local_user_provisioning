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
$string['enabled_debug'] = 'Debugging Enabled';
$string['enabled_desc'] = 'Enable Debugging for User Provisioning';
$string['debug_email_address'] = 'Debug email address';
$string['debug_email_address_default'] = 'jonathan';
$string['debug_email_desc'] = 'Recipient email address for debug information (mandatory if debugging enabled)';
$string['email_subject'] = 'Local_User_Provisioning API debugging Email';
$string['email_body_text1'] = 'PATH INFO = ';
$string['email_body_text2'] = 'REQUEST URI = ';
$string['email_body_text3'] = 'QUERY STRING = ';
$string['email_body_text4'] = 'REQUEST METHOD = ';
$string['email_body_text5'] = 'API JSON = ';
$string['oauth2bearer'] = 'OAuth Bearer Token';
$string['oauth2bearer_desc'] = 'Authentication scheme using the OAuth Bearer Token Standard 2.0';
$string['httpbasic'] = 'HTTP Basic';
$string['httpbasic_desc'] = 'Authentication scheme using the HTTP Basic Standard';

// SCIM attribute descriptions...
$string['attr-active'] = "A Boolean value indicating the User's administrative status. Moodle - Suspended";
$string['attr-addresses'] = "A physical mailing address for this User.";
$string['attr-addresses-country'] = "The country name component. Moodle - Country";
$string['attr-addresses-locality'] = "The city or locality component. Moodle - City/town";
$string['attr-addresses-type'] = "A label indicating the attribute's function, e.g., 'work' or 'home'.";
$string['attr-auth'] = "Auth - User Login Authentication. If not supplied, will default to manual. Values can be email, saml2, oidc or manual";
$string['attr-department'] = "Identifies the name of a department. Moodle - Department";
$string['attr-displayname'] = "The name of the User, suitable for display to end-users. The name SHOULD be the full name of the User being described, if known. Moodle - Alternate name";
$string['attr-emails'] = "Email addresses for the user. The value SHOULD be canonicalized by the service provider, e.g., 'bjensen@example.com' instead of 'bjensen@EXAMPLE.COM'.";
$string['attr-emails-primary'] = "A Boolean value indicating the 'primary' or preferred attribute value for this attribute, e.g., the preferred mailing address or primary email address. The primary attribute value 'true' MUST appear no more than once. PLEASE NOTE - Only Primary Email address will be added to Moodle.";
$string['attr-emails-type'] = "A label indicating the attribute's function, e.g., 'work' or 'home'.";
$string['attr-emails-value'] = "Email addresses for the user. The value SHOULD be canonicalized by the service provider, e.g., 'bjensen@example.com' instead of 'bjensen@EXAMPLE.COM'. Moodle - Email address";
$string['attr-manager'] = "The User's manager. A complex type that optionally allows service providers to represent organizational hierarchy by referencing the 'id' attribute of another User. Job assignments - Manager";
$string['attr-manager-value'] = "The URI of the SCIM resource representing the User's manager.";
$string['attr-name'] = "The components of the user's real name. Providers MAY return just the full name as a single string in the formatted sub-attribute, or they MAY return just the individual component attributes using the other sub-attributes, or they MAY return both. If both variants are returned, they SHOULD be describing the same name, with the formatted name indicating how the component attributes should be combined.";
$string['attr-name-familyname'] = "The family name of the User, or last name in most Western languages (e.g., 'Jensen' given the full name 'Ms. Barbara J Jensen, III'). Moodle - Surname";
$string['attr-name-givenname'] = "The given name of the User, or first name in most Western languages (e.g., 'Barbara' given the full name 'Ms. Barbara J Jensen, III'). Moodle - First name";
$string['attr-preferredlanguage'] = "Indicates the User's preferred written or spoken language. Generally used for selecting a localized user interface; e.g., 'en_US' specifies the language English and country US. Moodle - Preferred language";
$string['attr-team'] = "Custom User profile field - Team";
$string['attr-title'] = "The user's title, such as 'Vice President.' Moodle - Job assignments - Position";
$string['attr-username'] = "Unique identifier for the User, typically used by the user to directly authenticate to the service provider. This valus should be Users EMAIL ADDRESS. Each User MUST include a non-empty userName value. This identifier MUST be unique across the service provider's entire set of Users. Moodle - Username";

// Error...
$string['error:badrequest'] = 'Bad Request'; // Error code 400.
$string['error:badrequest_help'] = 'Request is unparsable, syntactically incorrect, or violates schema.';
$string['error:unauthorized'] = 'Unauthorized'; // Error code 401.
$string['error:unauthorized_help'] = 'Authorization failure. The authorization header is invalid or missing.';
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
$string['error:missingusername'] = 'userName is missing in the request body';
$string['error:missingfirstname'] = 'name.givenName is missing in the request body';
$string['error:missinglastname'] = 'name.familyName is missing in the request body';
$string['error:missingemail'] = 'emails.value is missing in the request body';
$string['error:invalidauth'] = 'urn:ietf:params:scim:schemas:extension:CustomExtension:2.0:User auth not supported';
$string['error:invalidmanager'] = 'Manager set for this user is invalid';
