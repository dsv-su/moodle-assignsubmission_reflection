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
 * Library class for the reflection submission plugin.
 *
 * @package assignsubmission_reflection
 * @copyright 2013 Department of Computer and System Sciences,
 *					Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');
require_once($CFG->dirroot . '/mod/assign/submission/reflection/lib.php');

class assign_submission_reflection extends assign_submission_plugin {

    /**
     * Get the name of the submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('reflection', 'assignsubmission_reflection');
    }

    /**
     * Get the default settings for the submission plugin.
     *
     * @param MoodleQuickForm $mform The form to append the elements to.
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $DB, $COURSE, $OUTPUT;

        $cmid = optional_param('update', 0, PARAM_INT);

        $studentsdefault = $this->get_config('students');
        if ($studentsdefault === false) {
            $studentsdefault = get_config('assignsubmission_reflection', 'students');
        }

        $mform->setDefault('assignsubmission_file_enabled', 0);
        $mform->setDefault('assignsubmission_blog_enabled', 0);
        $mform->setDefault('assignsubmission_online_enabled', 0);
        $mform->disabledIf('assignsubmission_file_enabled', 'assignsubmission_reflection_enabled', 'eq', 1);
        $mform->disabledIf('assignsubmission_blog_enabled', 'assignsubmission_reflection_enabled', 'eq', 1);
        $mform->disabledIf('assignsubmission_onlinetext_enabled', 'assignsubmission_reflection_enabled', 'eq', 1);
        $mform->setDefault('submissiondrafts', 1);
        $mform->disabledIf('submissiondrafts', 'assignsubmission_reflection_enabled', 'eq', 1);
        $mform->setDefault('teamsubmission', 0);
        $mform->disabledIf('teamsubmission', 'assignsubmission_reflection_enabled', 'eq', 1);
        $mform->setDefault('assignsubmission_mailsimulator_enabled', 0);
        $mform->disabledIf('assignsubmission_mailsimulator_enabled', 'assignsubmission_reflection_enabled', 'eq', 1);

        // Select number of students in a group.
        $maxstudents = array();
        for ($i=1; $i <= ASSIGNSUBMISSION_REFLECTION_MAXSTUDENTS; $i++) {
            $maxstudents[$i] = $i;
        }
        $mform->addElement('select', 'assignsubmission_reflection_students',
            get_string('students', 'assignsubmission_reflection'),
            $maxstudents);
        $mform->setDefault('assignsubmission_reflection_students', $studentsdefault);
        $mform->addHelpButton('assignsubmission_reflection_students', 'students',
            'assignsubmission_reflection');
        // Moodle 2.5.
        // $mform->disabledIf('assignsubmission_mailsimulator_filesubmissions', 'assignsubmission_mailsimulator_enabled', 'notchecked');
        // Moodle 2.4.
        $mform->disabledIf('assignsubmission_reflection_students', 'assignsubmission_reflection_enabled', 'eq', 0);

    }

    /**
     * Save the settings for the plugin
     * 
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $updateid = optional_param('update', 0, PARAM_INT);
        $this->set_config('students', $data->assignsubmission_reflection_students);
        if ($data->assignsubmission_reflection_enabled && !$updateid) {
            $this->create_grouping_and_forum($data);
        }
        return true;
    }

    /**
     * Initialize the forum and grouping for the plugin
     * 
     * @param stdClass $data
     * @return bool
     */
    public function create_grouping_and_forum(stdClass $data) {
        global $CFG, $DB, $COURSE;
        require_once($CFG->dirroot.'/group/lib.php');
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        // First add a course module for a forum.
        $newcm = new stdClass();
        $newcm->course           = $COURSE->id;
        $newcm->module           = 9; // Forum
        $newcm->section          = $data->section;
        $newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
        $newcm->visible          = $data->visible;
        $newcm->visibleold       = $data->visible;
        $newcm->groupmode        = 1; // Separate groups.
        $newcm->groupingid       = 0; // Not known yet, will be updated later.
        $newcm->groupmembersonly = 1;
        if(!empty($CFG->enableavailability)) {
            $newcm->availablefrom             = $data->availablefrom;
            $newcm->availableuntil            = $data->availableuntil;
            $newcm->showavailability          = $data->showavailability;
        }

        $coursemodule = add_course_module($newcm);

        // Then add a forum.
        $forum = new stdClass();
        $forum->course = $COURSE->id;
        $forum->name = $data->name.' '.get_string('forum', 'forum');
        $forum->type = 'eachuser';
        $obj = new stdClass();
        $obj->name = $data->name;
        $obj->href = '<a href="'.$CFG->wwwroot.'/mod/assign/view.php?id='.$data->coursemodule.'">';
        $forum->intro = get_string('forumintro', 'assignsubmission_reflection', $obj);
        $forum->section = $data->section;
        $forum->coursemodule = $coursemodule;
        $forum->forcesubscribe = 2; // Auto subscription.
        $forum->maxbytes = $data->maxbytes;
        $forum->cmidnumber = $data->cmidnumber; // Not sure why it is needed, but this prevents errors.
        $forumid = forum_add_instance($forum);

        // Configure the newly created module to be assosicated with the newly created forum.
        $DB->set_field('course_modules', 'instance', $forumid, array('id'=>$coursemodule));

        // !!!remove as much as we can if forum has not been created.... - todo!!!

        // course_modules and course_sections each contain a reference
        // to each other, so we have to update one of them twice.
        $sectionid = course_add_cm_to_section($COURSE, $coursemodule, $data->section);
        // make sure visibility is set correctly (in particular in calendar)
        // note: allow them to set it even without moodle/course:activityvisibility
        set_coursemodule_visible($coursemodule, $data->visible);

        // Create a grouping. Control variable idnumber = forumid.
        $grouping = new stdClass();
        $grouping->name = $data->name.' '.get_string('grouping','group');
        $grouping->courseid = $COURSE->id;
        $grouping->idnumber = $forumid;
        $grouping->description = "Reflection grouping";
        $grouping->id = groups_create_grouping($grouping);

        // Configure the newly created forum to be associated with the newly created grouping.
        $DB->set_field('course_modules', 'groupingid', $grouping->id, array('id'=>$coursemodule));

        return true;
    }

    /**
     * Here the action is to be performed.
     * 
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $cmid = required_param('id', PARAM_INT);
        $mailboxurl = new moodle_url('/mod/assign/submission/mailsimulator/mailbox.php', array("id"=>$cmid));
        redirect($mailboxurl);
        return true;
    }


    /**
     * Displays all posts and reflection comments for this assignment from a specified student.
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        global $CFG, $DB, $OUTPUT;

        $id         = required_param('id', PARAM_INT);
        $sid        = optional_param('sid', $submission->id, PARAM_INT);
        $gid        = optional_param('gid', 0, PARAM_INT);
        $userid     = $DB->get_field('assign_submission', 'userid', array("id" => $sid));
        $cm         = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
        $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $context    = context_module::instance($cm->id);
        require_once($CFG->dirroot.'/mod/assign/submission/mailsimulator/mailbox_class.php');
        $mailboxinstance = new mailbox($context, $cm, $course);

        ob_start();
        if ($submission) {
            $mailboxinstance->view_grading_feedback($userid);
        } else {
            error(get_string('submissionstatus_', 'assign'));
        }
        $o = ob_get_contents();
        ob_end_clean();

        return $o;
    }

    /**
     * Displays the summary of the submission
     *
     * @param stdClass $submission The submission to show a summary of
     * @param bool $showviewlink Will be set to true to enable the view link
     * @return string
     */

    public function view_summary(stdClass $submission, & $showviewlink) {
        global $DB;

        $showviewlink = true;
        $cmid = required_param('id', PARAM_INT);
        $cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
        $userid = $submission->userid+0;

        $result = html_writer::start_tag('div');
        $result .= get_string('mailssent', 'assignsubmission_mailsimulator') . $mailssent;
        $result .= html_writer::empty_tag('br');
        $result .= get_string('weightgiven', 'assignsubmission_mailsimulator') . $weightgiven;
        $result .= html_writer::end_tag('div');

        return $result;

    }

    /**
     * Produce a list of files suitable for export that represents this submission
     * 
     * @param stdClass $submission
     * @param stdClass $user
     * @return array an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB, $CFG;
        $files = array();
        return $files;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        global $DB;
        $usermails = $DB->record_exists('assignsubmission_mail_mail', array('userid' => $submission->userid+0));
        return empty($usermails);
    }  

}
