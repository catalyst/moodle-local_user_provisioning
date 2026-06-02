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
 * Swagger UI for local_user_provisioning
 *
 * @package   local_user_provisioning
 * @copyright Catalyst IT Europe Ltd 2026
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

global $PAGE, $OUTPUT;

$swaggerversion = '5.17.14';

$swaggercssurl = new moodle_url("https://unpkg.com/swagger-ui-dist@{$swaggerversion}/swagger-ui.css");
$swaggerbundleurl = new moodle_url("https://unpkg.com/swagger-ui-dist@{$swaggerversion}/swagger-ui-bundle.js");
$swaggertagspluginurl = new moodle_url('https://unpkg.com/swagger-ui-plugin-hierarchical-tags');

$swaggercsssri = 'sha384-wxLW6kwyHktdDGr6Pv1zgm/VGJh99lfUbzSn6HNHBENZlCN7W602k9VkGdxuFvPn';
$swaggerbundlesri = 'sha384-wmyclcVGX/WhUkdkATwhaK1X1JtiNrr2EoYJ+diV3vj4v6OC5yCeSu+yW13SYJep';
$swaggertagspluginsri = 'sha384-rvitX4iURy1lN2ApXeXTgMMmwYJx9E9uIPW0HrThb+hr1JS8l2UHoPC594DK6Eyk';

$PAGE->set_url('/admin/swaggerui.php');

admin_externalpage_setup('swaggerui');

echo $OUTPUT->header();

echo html_writer::empty_tag('link', [
    'rel' => 'stylesheet',
    'href' => $swaggercssurl,
    'integrity' => $swaggercsssri,
    'crossorigin' => 'anonymous',
]);

// These have to be manually added for now because they must be made cross-origin. The `js` method does not yet support this.
echo html_writer::tag(
    tagname: 'script',
    contents: '',
    attributes: [
        'src' => $swaggerbundleurl,
        'integrity' => $swaggerbundlesri,
        'crossorigin' => 'anonymous',
    ],
);
echo html_writer::tag(
    tagname: 'script',
    contents: '',
    attributes: [
        'src' => $swaggertagspluginurl,
        'integrity' => $swaggertagspluginsri,
        'crossorigin' => 'anonymous',
    ],
);

$openapipath = (new moodle_url(
    '/local/user_provisioning/docs/openapi.php'
))->out();

$swaggerinit = <<<JS
    window.ui = SwaggerUIBundle({
        url: "{$openapipath}",
        dom_id: '#swagger-ui',

        // Enable the "Try it out" button by default.
        tryItOutEnabled: true,

        // Show snippets different OS options.
        requestSnippetsEnabled: true,

        deepLinking: true,

        plugins: [
            HierarchicalTagsPlugin,
        ],

        hierarchicalTagSeparator: /[_]/
    });
JS;

$PAGE->requires->js_init_code(
    jscode: $swaggerinit,
    ondomready: true,
);

echo html_writer::div('', '', [
    'id' => 'swagger-ui',
]);

echo $OUTPUT->footer();
