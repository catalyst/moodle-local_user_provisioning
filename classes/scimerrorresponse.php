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
 * SCIM error response class.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;

/**
 * Class to handle SCIM error response.
 * @package local_user_provisioning
 */
class scimerrorresponse extends scimresponse {
    /** @var string Error message. */
    private $message = '';

    /** @var string Error type. */
    private $type = '';

    /** @var int Error status code. */
    private $status = 0;

    /**
     * Error response constructor.
     *
     * @param string $message
     * @param string $type
     * @param int $status
     **/
    public function __construct(string $message, string $type, int $status) {
        $this->set_response_type('Error');
        $this->message = $message;
        $this->type = $type;
        $this->status = $status;
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data(): array {
        return [
            'status' => $this->status,
            'scimType' => $this->type,
            'detail' => $this->message,
        ];
    }
}
