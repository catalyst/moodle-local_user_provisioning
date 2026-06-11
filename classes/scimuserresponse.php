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
 * SCIM user response class.
 *
 * @package    local_user_provisioning
 * @copyright  Catalyst IT Europe Ltd 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jackson D'Souza <jackson.dsouza@catalyst-eu.net>
 */

namespace local_user_provisioning;
use moodle_url;

/**
 * Class to handle SCIM User response.
 * @package local_user_provisioning
 */
class scimuserresponse extends scimresponse {
    /**
     * @var object User object.
     */
    private $user;

    /**
     * @var bool|null User active status.
     */
    private $active = null;

    /**
     * @var bool Whether to append user schema info.
     */
    private $appendschemainfo = false;

    /**
     * Returns SCIM User response constructor.
     *
     * @param object $userobject User object
     * @param bool $active User status
     * @param bool $appendschemainfo Append User schema info.
     */
    public function __construct(object $userobject, bool $active, bool $appendschemainfo) {
        $this->set_response_type('User');
        $this->user = $userobject;
        $this->active = $active;
        $this->appendschemainfo = $appendschemainfo;
    }

    /**
     * Return extra data as part of SCIM response.
     *
     * @return array
     */
    protected function extra_data(): array {

        $isworkplace = is_moodleworkplace();

        $manageridnumber = '';
        $manager = '';
        $refurl = '';
        if ($isworkplace && $this->user->managerid) {
            $manageridnumber = $this->user->manageridnumber;
            $manager = $this->user->managerfirstname . ' ' . $this->user->managerlastname;
            $refurl = (new moodle_url('/user/profile.php', ['id' => $this->user->managerid]))->out();
        }

        $phonenumbers = [];
        if (!empty($this->user->phone1)) {
            $phonenumbers[] = [
                'value' => $this->user->phone1,
                'type' => 'work',
            ];
        }
        if (!empty($this->user->phone2)) {
            $phonenumbers[] = [
                'value' => $this->user->phone2,
                'type' => 'mobile',
            ];
        }

        $returnvar = [
            'id' => $this->user->idnumber,
            'externalId' => $this->user->idnumber,
            'userName' => $this->user->username,
            'displayName' => $this->user->alternatename,
            'name' => [
                'givenName' => $this->user->firstname,
                'middleName' => $this->user->middlename,
                'familyName' => $this->user->lastname,
            ],
            'emails' => [
                            [
                                'value' => $this->user->email,
                                'type' => 'work',
                                'primary' => true,
                            ],
                        ],
            'phoneNumbers' => $phonenumbers,
            'preferredLanguage' => $this->user->lang,
            'organization' => $this->user->institution,
            'addresses' => [
                                [
                                    'streetAddress' => $this->user->address,
                                    'locality' => $this->user->city,
                                    'country' => $this->user->country,
                                    'type' => 'work',
                                    'primary' => true,
                                ],
                            ],
            'department' => $this->user->department,
            'active' => $this->active,
            static::SCIM2_CUSTOM_USER_URN => [
                                                'auth' => $this->user->auth ?? null,
                                            ],
            'meta' => $this->get_meta(),
        ];

        if ($isworkplace) {
            $returnvar['title'] = $this->user->title;
            $returnvar[static::SCIM2_ENTERPRISE_USER_EXT] = [
                'manager' => [
                    'value' => $manageridnumber,
                    '$ref' => $refurl,
                    'displayName' => $manager,
                ],
            ];
        }

        $returnvar = custom_append_response_fields($this->user, $returnvar);

        if ($this->appendschemainfo) {
            $returnvar = ['schemas' => static::get_user_schemas()] + $returnvar;
        }

        return $returnvar;
    }

    /**
     * Return metadata as part of this SCIM response.
     *
     * @return array
     */
    protected function get_meta(): array {
        global $CFG;
        return [
            'resourceType' => 'User',
            'created' => gmdate("Y-m-d\TH:i:s\Z", $this->user->timecreated),
            'lastModified' => gmdate("Y-m-d\TH:i:s\Z", $this->user->timemodified),
            'location' => $CFG->wwwroot . '/user/view.php?id=' . $this->user->id,
            'version' => static::SCIM2_VERSION,
        ];
    }
}
