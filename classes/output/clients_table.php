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

namespace local_user_provisioning\output;

/**
 * Manage clients table.
 *
 * @package     local_user_provisioning
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   Catalyst IT Australia 2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clients_table extends \table_sql {

    /**
     * Constructor for the clients table.
     *
     * @param string $uniqueid The unique id for the table.
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_baseurl(new \core\url('/local/user_provisioning/clients.php'));
        $this->define_columns(['client_id', 'client_secret', 'additionalscopes', 'actions']);
        $this->define_headers([
            get_string('client_id', 'local_user_provisioning'),
            get_string('clientsecret', 'local_user_provisioning'),
            get_string('additionalscopes', 'local_user_provisioning'),
            get_string('actions')
        ]);

        $this->set_sql(
            'id, additionalscopes, client_id, client_secret',
            '{local_user_provisioning_clients}',
            '1=1',
            []
        );
        $this->set_attribute('class', 'generaltable generalbox margin-top');
    }

    /**
     * Returns the formatted additional scopes for the table.
     *
     * @param \stdClass $client The client record.
     * @return string The formatted additional scopes.
     */
    public function col_additionalscopes($client) {
        return format_string($client->additionalscopes);
    }

    /**
     * Returns the formatted client ID for the table.
     *
     * @param \stdClass $client The client record.
     * @return string The formatted client ID.
     */
    public function col_client_id($client) {
        return format_string($client->client_id);
    }

    /**
     * Returns an unmask HTML element for the client secret column.
     *
     * @param \stdClass $client The client record.
     * @return string An empty string.
     */
    public function col_client_secret($client) {
        global $OUTPUT;
        $context = (object) [
            'id' => 'clientsecret_' . $client->id,
            'name' => get_string('clientsecret', 'local_user_provisioning'),
            'size' => 80,
            'value' => $client->client_secret,
            'forceltr' => true,
            'readonly' => false,
        ];
        return $OUTPUT->render_from_template('local_user_provisioning/client_secret_unmask', $context);
    }

    /**
     * Returns the action links for the table.
     *
     * @param \stdClass $client The client record.
     * @return string The action links.
     */
    public function col_actions($client) {
        $editurl = new \core\url('/local/user_provisioning/clients.php', ['action' => 'edit', 'id' => $client->id]);
        $deleteurl = new \core\url('/local/user_provisioning/clients.php', ['action' => 'delete', 'id' => $client->id, 'sesskey' => sesskey()]);

        $editlink = \html_writer::link($editurl, get_string('edit'));
        $deletelink = \html_writer::link($deleteurl, get_string('delete'), ['onclick' => 'return confirm("' . get_string('confirmdelete', 'local_user_provisioning') . '");']);

        return $editlink . ' | ' . $deletelink;
    }
}
