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
 * This file contains the event hooks for the reflection submission plugin.
 *
 * @package assignsubmission_reflection
 * @copyright 2013 Department of Computer and System Sciences,
 *          Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

define('ASSIGNSUBMISSION_REFLECTION_MAXSTUDENTS', 10);

/** 
* Adds a link to navigation settings block.
*
* @param settings_navigation $settings
* @param navigation_node $navref
* @return void
*/
function assignsubmission_reflection_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    $id = optional_param('id', 0, PARAM_INT);
    $link = new moodle_url('/mod/assign/submission/mailsimulator/mailbox.php', array('id' => $id));
    $node = $navref->add(get_string('mailadmin', 'assignsubmission_mailsimulator'), $link, navigation_node::TYPE_SETTING);
} 
