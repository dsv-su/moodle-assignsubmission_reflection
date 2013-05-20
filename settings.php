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
 * This file defines the admin settings for the reflection submission plugin.
 *
 * @package assignsubmission_reflection
 * @copyright 2013 Department of Computer and System Sciences,
 *        Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/assign/submission/reflection/lib.php');

$settings->add(new admin_setting_configcheckbox('assignsubmission_reflection/default',
        new lang_string('default', 'assignsubmission_reflection'),
        new lang_string('default_help', 'assignsubmission_reflection'), 0));

$maxstudents = array();
for ($i=1; $i <= ASSIGNSUBMISSION_REFLECTION_MAXSTUDENTS; $i++) {
    $maxstudents[$i] = $i;
}
$settings->add(new admin_setting_configselect('assignsubmission_reflection/students',
                                               get_string('students', 'assignsubmission_reflection'),
                                               get_string('students_help', 'assignsubmission_reflection'), 4,
                                               $maxstudents));
