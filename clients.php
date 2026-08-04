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
 * Manage clients.
 *
 * @package     local_user_provisioning
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   Catalyst IT Australia 2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('tool_mfa_resetfactor');

$clientid = core\param::INT->optional_param('id', null);
$action = core\param::ALPHANUMEXT->optional_param('action', null);

if ($action === 'delete' && $clientid) {
    require_sesskey();
    $DB->delete_records('local_user_provisioning_clients', ['id' => $clientid]);
    redirect(new moodle_url('/local/user_provisioning/clients.php'));
}
$form = new \local_user_provisioning\form\edit_client();

// Process form actions if there is a submission.
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/user_provisioning/clients.php'));
} else if ($form->is_submitted() && !$form->is_validated()) {
    // Form is submitted but not validated, display the form with errors.
    $form->display();
} else if ($data = $form->get_data()) {
    if ($data->id) {
        $DB->update_record('local_user_provisioning_clients', (object) [
            'id' => $data->id,
            'client_id' => $data->client_id,
            'additionalscopes' => $data->additionalscopes
        ]);
    } else {
        $secret = bin2hex(random_bytes(32));
        $record = (object) [
            'client_id' => $data->client_id,
            'client_secret' => $secret,
            'additionalscopes' => $data->additionalscopes
        ];
        $DB->insert_record('local_user_provisioning_clients', $record);
    }
    redirect(new moodle_url('/local/user_provisioning/clients.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageclients', 'local_user_provisioning'));

if ($action === 'edit' && $clientid) {
    $client = $DB->get_record('local_user_provisioning_clients', ['id' => $clientid], '*', MUST_EXIST);
    $form->set_data($client);
    $form->display();
} else if ($action === 'add') {
    $form->display();
} else {
    $table = new \local_user_provisioning\output\clients_table('clients');

    echo $OUTPUT->single_button(
        new core\url('/local/user_provisioning/clients.php', ['action' => 'add']),
        get_string('addclient', 'local_user_provisioning'),
        'post',
        ['class' => 'btn mb-2']
    );
    $table->out(20, true);
}

echo $OUTPUT->footer();
