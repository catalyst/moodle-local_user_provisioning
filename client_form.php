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
 * Add client form.
 *
 * @package     local_user_provisioning
 * @author      Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 * @copyright   2024 Catalyst IT Europe {@link http://www.catalyst-eu.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Add client form.
 *
 * @package     local_user_provisioning
 * @author      Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 * @copyright   2024 Catalyst IT Europe {@link http://www.catalyst-eu.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_user_provisioning_client_form extends moodleform {

    /**
     * Define the form for adding client.
     */
    public function definition() {
        global $CFG;
        $bform =& $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $bform->addElement('header', 'general', get_string('general', 'form'));

        $bform->addElement('text', 'client_id', get_string('client_id', 'local_user_provisioning'),
            ['maxlength' => 80, 'size' => 45]);
        $bform->addRule('client_id', null, 'required', null, 'client');
        $bform->setType('client_id', PARAM_TEXT);
        $bform->addHelpButton('client_id', 'client_id', 'local_user_provisioning');

        $bform->addElement('hidden', 'redirect_uri', '');
        $bform->setType('redirect_uri', PARAM_URL);
        $bform->addElement('hidden', 'grant_types', 'client_credentials');
        $bform->setType('grant_types', PARAM_TEXT);
        $bform->addElement('hidden', 'scope', 'SCIMv2');
        $bform->setType('scope', PARAM_TEXT);
        $bform->addElement('hidden', 'user_id', 0);
        $bform->setType('user_id', PARAM_INT);
        $bform->addElement('hidden', 'action', 'add');
        $bform->setType('action', PARAM_ACTION);

        $this->add_action_buttons();
    }

    /**
     * Validate incoming form data.
     * @param array $usernew
     * @param array $files
     * @return array
     */
    public function validation($data, $files) : array {
        global $DB;
        $errors = parent::validation($data, $files);
        if ($DB->record_exists('local_user_provisioning_clients', ['client_id' => $data['client_id']])) {
            $errors['client_id'] = get_string('client_id_existing_error', 'local_user_provisioning');
        }

        return $errors;
    }
}
