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
 * Check if URL is secure or not.
 *
 * @param string $url URL.
 * @return array|string
 */
function local_user_provisioning_no_ssl_url($url) {
    return preg_replace("/^https:/i", "http:", $url);
}

/**
 * Generate secret.
 *
 * @return array|string
 */
function local_user_provisioning_generate_secret() {
    // Get a whole bunch of random characters from the OS.
    $fp = fopen('/dev/urandom', 'rb');
    $entropy = fread($fp, 32);
    fclose($fp);

    // Takes our binary entropy, and concatenates a string which represents the current time to the microsecond.
    $entropy .= uniqid(mt_rand(), true);

    // Hash the binary entropy.
    $hash = hash('sha512', $entropy);

    // Chop and send the first 80 characters back to the client.
    return substr($hash, 0, 48);
}
