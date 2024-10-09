<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);

// // Ensure that the user is logged in and has access to the course.
// require_login($courseid);

// // Set up the page.
// $course = get_course($courseid);

// $PAGE->set_course($course);


// $url = new moodle_url('/local/gradereport_submit/submit.php', ['id' => $report->id]);
// $button = new single_button($url, get_string('submitgrades', 'local_gradereport_submit'), 'post');



//submit for approval
$response = \local_gradereport_submit\report::submit_report_for_approval($courseid, $USER->id);


// echo $renderer->render($button);

// echo $OUTPUT->footer();
