<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// // Ensure that the user is logged in and has access to the course.
// require_login($courseid);

// // Set up the page.
// $course = get_course($courseid);

// $PAGE->set_course($course);

// $PAGE->set_title('My Custom Page');
// $PAGE->set_heading('Custom Menu Page');

// // Output starts here.
// echo $OUTPUT->header();
// echo $OUTPUT->heading('Are you sure you want to submit grades?');

// $renderer = $PAGE->get_renderer('core');

// $url = new moodle_url('/local/gradereport_submit/submit.php', ['id' => $report->id]);
// $button = new single_button($url, get_string('submitgrades', 'local_gradereport_submit'), 'post');



//submit for approval
$response = \local_gradereport_submit\report::submit_report_for_approval($courseid);


// // If the current user is an admin with grade editing capabilities
// if (has_capability('moodle/grade:edit', context_course::instance($COURSE->id))) {
//     // Remove the permission to edit grades
//     // You'll need to prevent grade editing by adjusting role capabilities
//     $roleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
//     if ($roleid) {
//         // Disable course grade editing for instructors
//         role_change_permission($roleid, context_course::instance($COURSE->id), 'moodle/grade:edit', CAP_PROHIBIT);
//     }
// }

// echo $renderer->render($button);

// echo $OUTPUT->footer();
