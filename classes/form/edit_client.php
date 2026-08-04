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
 * @copyright  Catalyst IT Australia 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Peter Burnett <peterburnett@catalyst-au.net>
 */

namespace local_user_provisioning\form;

use moodleform;

/**
 * Form for adding/editing a client.
 */
class edit_client extends moodleform {

    /**
     * Defines the form elements.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'client_id', get_string('client_id', 'local_user_provisioning'));
        $mform->setType('client_id', PARAM_TEXT);
        $mform->addRule('client_id', null, 'required', null, 'client');

        $mform->addElement('text', 'additionalscopes', get_string('additionalscopes', 'local_user_provisioning'));
        $mform->setType('additionalscopes', PARAM_TEXT);

        $this->add_action_buttons();
    }

    /**
     * Validates the form data.
     *
     * @param array $data The form data.
     * @param array $files The uploaded files.
     * @return array An array of errors, if any.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['client_id'])) {
            $errors['client_id'] = get_string('required');
        }

        return $errors;
    }
}
