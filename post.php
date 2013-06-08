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
 * Post adding for the reflection submission plugin.
 *
 * @package assignsubmission_reflection
 * @copyright 2013 Department of Computer and System Sciences,
 *                  Stockholm University  {@link http://dsv.su.se}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');
require_once($CFG->dirroot.'/mod/assign/submission/reflection/locallib.php');
 
global $CFG, $DB, $PAGE, $COURSE, $USER;

$id      = required_param('id', PARAM_INT);
$cm      = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course);
require_capability('mod/assign:view', $context);

$PAGE->set_url('/mod/assign/submission/reflection/post.php', array('id' => $id));
$PAGE->set_title(get_string('pluginname', 'assignsubmission_reflection'));
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);

$assigninstance = new assign($context, $cm, $course);
$plugininstance = new assign_submission_reflection($assigninstance, 'reflection');

$waitinggroup = $DB->get_record('groups', array('id' => $plugininstance->get_config('waitingid')));
$forumid = $plugininstance->get_config('forumid');
$groupingid = $plugininstance->get_config('groupingid');

require_once($CFG->dirroot.'/mod/assign/submission/reflection/post_form.php');
$mform = new post_form(null, array('moduleID'=>$id));

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    groups_remove_member($waitinggroup, $USER);
    redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $cm->id, get_string('reflectioncancelled', 'assignsubmission_reflection'), 1);
} else if ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    if ($mform->is_validated()) {

        // Get who is in waiting list
        $waitingusers = groups_get_members($waitinggroup->id);

        // Create a discussion
        $discussion = new stdClass();
        $discussion->course = $cm->course;
        $discussion->forum = $forumid;
        $discussion->groupid = 0;
        $discussion->name = get_string('pluginname', 'assignsubmission_reflection') . ' ' . (count($waitingusers)+1);
        $discussion->firstpost = 0;
        $discussion->message = $fromform->post['text'];
        $discussion->messageformat = $fromform->post['format']+0;
        $discussion->messagetrust = 0;
        $discussion->mailnow = 0;

        forum_add_discussion($discussion);

        $plugininstance->update_user_submission($USER->id);

        /*
        // Lock the submission for this user to prevent editing
        $grade = $assigninstance->get_user_grade($USER->id, true);
        $grade->locked = 1;
        $grade->grader = $USER->id;
        $assigninstance->update_grade($grade);
        */

        if ((count($waitingusers)+1) == $plugininstance->get_config('students')) {
            // Create a new reflection group within the grouping
            $timenow = time();
            $group = new stdClass();
            $group->name = get_string('pluginname', 'assignsubmission_reflection').get_string('group','group').date("ymdHis", $timenow);
            $group->courseid = $COURSE->id;
            $group->description = "Reflection activity group";
            $group->id = groups_create_group($group);
            groups_assign_grouping($groupingid, $group->id);
            $justcreatedgroup = $DB->get_record('groups', array('id' => $group->id));

            // Adding current user to users who wait for group
            $waitingusers[] = $USER;

            // Move all students to new reflection group
            foreach ($waitingusers as $user) {
                $DB->set_field('forum_discussions', 'groupid', $justcreatedgroup->id, array('userid' => $user->id, 'forum' => $forumid));
                groups_add_member($justcreatedgroup, $user);
                groups_remove_member($waitinggroup, $user);
                /*
                // Unlock submissions
                $grade = $assigninstance->get_user_grade($user->id, true);
                $grade->locked = 0;
                $grade->grader = $user->id;
                $assigninstance->update_grade($grade);
                */
            }

        } else {
            // Add this student to a waiting group
            groups_add_member($waitinggroup, $USER);
        }
    
        redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $cm->id, get_string('reflectionadded', 'assignsubmission_reflection'), 1);
    }

} else {
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
    echo $OUTPUT->header();
    if ($plugininstance->user_have_registered_submission($USER->id, $cm->instance)) {
        print_error(get_string('cannotaddreflection', 'assignsubmission_reflection'));
    }
    // Display the form.
    $mform->display();
}

echo $OUTPUT->footer();
