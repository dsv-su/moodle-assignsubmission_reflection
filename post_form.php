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
 * Contact editing form class for the mailsimulator submission plugin.
 *
 * @package assignsubmission_mailsimulator
 * @copyright 2013 Department of Computer and System Sciences,
 *                  Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/validateurlsyntax.php');

class post_form extends moodleform {

    function definition() {
        global $CFG, $DB;

        $id = optional_param('id', 0, PARAM_INT);
        $cm = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
        $mform =& $this->_form;

        // hidden params
        $mform->addElement('hidden', 'id', $this->_customdata['moduleID']);
        $mform->setType('id', PARAM_INT);

        // visible elements
        $mform->addElement('editor', 'post', get_string('pluginname', 'assignsubmission_reflection'),
                array('cols' => 83, 'rows' => 20));
        $mform->setType('post', PARAM_RAW); // To be cleaned before display.

        $this->add_action_buttons(true, 'Submit');
    }

        // Form validation for errors is done here.
    function validation($data, $files) {
        $errors = array();

        if (strlen(ltrim($data['post']['text'])) < 1) {
            $errors['post'] = get_string('erroremptymessage', 'forum');
        }

        return $errors;
    }
}
