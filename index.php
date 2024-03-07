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
 * @copyright  Catalyst IT Europe Ltd 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once('client_form.php');
require_once('locallib.php');

require_login();
require_capability('local/user_provisioning:manageclients', context_system::instance());

admin_externalpage_setup('user_provisioning_clients');

$action = optional_param('action', '', PARAM_ALPHA);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('clients', 'local_user_provisioning'));

$viewtable = false;
switch ($action) {
    case 'edit':
        $id = required_param('id', PARAM_TEXT);
        if (!$clientedit = $DB->get_record('local_user_provisioning_clients', ['id' => $id])) {
            echo $OUTPUT->notification(get_string('client_not_exists', 'local_user_provisioning'));
            $viewtable = true;
            break;
        }
    case 'add':
        $mform = new local_user_provisioning_client_form();
        if ($mform->is_cancelled()) {
            $viewtable = true;
            break;
        } else if ($formdata = $mform->get_data()) {
            // Get values.
            $record = new stdClass();
            $record->redirect_uri = local_user_provisioning_no_ssl_url($formdata->redirect_uri);
            $record->grant_types = $formdata->grant_types;
            $record->scope = $formdata->scope;
            $record->user_id = $formdata->user_id ? $fromform->user_id : '';
            $record->client_id = $formdata->client_id;
            $record->client_secret = local_user_provisioning_generate_secret();
            if (!$DB->insert_record('local_user_provisioning_clients', $record)) {
                throw new \moodle_exception('insert_error', 'local_user_provisioning');
            }
            echo $OUTPUT->notification(get_string('saveok', 'local_user_provisioning'), 'notifysuccess');
            $viewtable = true;
            break;
        }
        $mform->display();

        break;
    case 'del':
        // Get values.
        $confirm = optional_param('confirm', 0, PARAM_INT);
        $id = required_param('id', PARAM_TEXT);

        // Do delete.
        if (empty($confirm)) {
            if (!$clientedit = $DB->get_record('local_user_provisioning_clients', ['id' => $id])) {
                echo $OUTPUT->notification(get_string('client_not_exists', 'local_user_provisioning'));
                $viewtable = true;
                break;
            }

            echo $OUTPUT->confirm(get_string('confirmdeletestr', 'local_user_provisioning'),
                new moodle_url($PAGE->url, ['confirm' => 1, 'action' => 'del', 'id' => $id]),
                new moodle_url($PAGE->url));

        } else {
            if (!$DB->delete_records('local_user_provisioning_clients', ['id' => $id])) {
                throw new \moodle_exception('delete_error', 'local_user_provisioning');
            }
            echo $OUTPUT->notification(get_string('delok', 'local_user_provisioning'), 'notifysuccess');
            $viewtable = true;
            break;
        }
        break;
    default:
        $viewtable = true;
        break;
}

if ($viewtable) {
    echo html_writer::link(new moodle_url('index.php', ['action' => 'add']),
        get_string('addclient', 'local_user_provisioning'));
    $clients = $DB->get_records('local_user_provisioning_clients');

    if (!empty($clients)) {
        $table = new html_table();
        $table->class = 'generaltable generalbox';
        $table->head = [
                            get_string('client_id', 'local_user_provisioning'),
                            get_string('client_secret', 'local_user_provisioning'),
                            get_string('actions'),
                        ];
        $table->align = ['left', 'left', 'center', 'center'];

        foreach ($clients as $client) {
            $deletelink = html_writer::link(new moodle_url('index.php',
                    ['action' => 'del', 'id' => $client->id]),
                get_string('delete', 'local_user_provisioning'));
            $row = [];
            $row[] = $client->client_id;
            $row[] = $client->client_secret;
            $row[] = html_writer::link(new moodle_url('index.php',
                            ['action' => 'del', 'id' => $client->id]),
                        get_string('delete', 'local_user_provisioning'));
            $table->data[] = $row;
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
