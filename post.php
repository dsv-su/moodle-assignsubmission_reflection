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
$gid     = required_param('gid', PARAM_INT);
$forumid = required_param('forumid', PARAM_INT);
$cm      = get_coursemodule_from_id('assign', $id, 0, false, MUST_EXIST);
$course  = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course);
require_capability('mod/assign:view', $context);

$PAGE->set_url('/mod/assign/submission/reflection/post.php', array('id' => $id, 'gid' => $gid, 'forumid' => $forumid));
$PAGE->set_title('New post');
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_cm($cm);

$assigninstance = new assign($context, $cm, $course);
$plugininstance = new assign_submission_reflection($assigninstance, 'reflection');

$waitinggroup = $DB->get_record('groups', array('idnumber' => $forumid, 'name' => 'Waiting group for '. $cm->name));

/* TODO
1. Create a group 'Waiting for group members';
2. Fill in the post creation form ();
3. Create discussion / post;
4. Add a student to this group if the post has been added;
5. If the number of group members equals to settings, create a new group within a given grouping and add them to it;
6. After all redirect to assignment main page;
*/

require_once($CFG->dirroot.'/mod/assign/submission/reflection/post_form.php');
$mform = new post_form(null, array('moduleID'=>$id, 'gid' => $gid, 'forumid' => $forumid));

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    groups_remove_member($waitinggroup, $USER);
    redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $cm->id, 'Back to assign', 1);
} else if ($fromform = $mform->get_data()) {
    // In this case you process validated data. $mform->get_data() returns data posted in form.
    if ($mform->is_validated()) {

        // Get who is in waiting list
        $waitingusers = groups_get_members($waitinggroup->id);

        // Create a discussion
        $discussion = new stdClass();
        $discussion->course = $cm->course;
        $discussion->forum = $fromform->forumid;
        $discussion->groupid = 0;
        $discussion->name = 'Reflection ' . (count($waitingusers)+1);
        $discussion->firstpost = 0;
        $discussion->message = $fromform->post['text'];
        $discussion->messageformat = $fromform->post['format']+0;
        $discussion->messagetrust = 0;
        $discussion->mailnow = 0;

        forum_add_discussion($discussion);

        $plugininstance->update_user_submission($USER->id);

        if ((count($waitingusers)+1) == $plugininstance->get_config('students')) {
            // Create a new reflection group within the grouping
            $timenow = time();
            $group = new stdClass();
            $group->name = get_string('pluginname', 'assignsubmission_reflection').get_string('group','group').date("ymdHis", $timenow);
            $group->courseid = $COURSE->id;
            $group->description = "Reflection activity group";
            $group->id = groups_create_group($group);
            groups_assign_grouping($gid, $group->id);
            $justcreatedgroup = $DB->get_record('groups', array('id' => $group->id));

            // Move all waiting students to new reflection group
            foreach ($waitingusers as $user) {
                $DB->set_field('forum_discussions', 'groupid', $justcreatedgroup->id, array('userid' => $user->id, 'forum' => $fromform->forumid));
                groups_add_member($justcreatedgroup, $user);
                groups_remove_member($waitinggroup, $user);
            }
            // Add this student to new reflection group
            groups_add_member($justcreatedgroup, $USER);
            $DB->set_field('forum_discussions', 'groupid', $justcreatedgroup->id, array('userid' => $USER->id, 'forum' => $fromform->forumid));

        } else {
            // Add this student to a waiting group
            groups_add_member($waitinggroup, $USER);
        }
    
        redirect($CFG->wwwroot . '/mod/assign/view.php?id=' . $cm->id);
    }

} else {
    // This branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    // Get criteria from database.
    /*
    if ($contactlist = $DB->get_records_list('assignsubmission_mail_cntct', 'assignment', array($cm->instance))) {
        // Fill form with data.
        $toform = new stdClass;
        $toform->contactid = array();
        $toform->firstname = array();
        $toform->lastname = array();
        $toform->email = array();
        foreach ($contactlist as $i => $contact) {
            $toform->contactid[] = (int) ($contact->id);
            $toform->firstname[] = $contact->firstname;
            $toform->lastname[] = $contact->lastname;
            $toform->email[] = $contact->email;
        }
    }
    */
    //$mailboxinstance->print_tabs('addcontacts');
    /*
    if (!$DB->record_exists('assignsubmission_mail_cntct', array('assignment' => $cm->instance))) {
        echo $OUTPUT->notification(get_string('addonecontact', 'assignsubmission_mailsimulator'));
    }
    */
    //echo $OUTPUT->notification(get_string('deletecontact', 'assignsubmission_mailsimulator'));
    // Display the form.
    /*
    if (isset($toform)) {
        $mform->set_data($toform);
    }
    */
    echo $OUTPUT->header();
    $mform->display();
}

echo $OUTPUT->footer();
