<?php
// approval.php
require_once('../../config.php');


require_login();

$PAGE->set_url('/local/gradereport_submit/approval.php');
$PAGE->set_title(get_string('approvereport', 'local_gradereport_submit'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('approvereport', 'local_gradereport_submit'));

$renderer = $PAGE->get_renderer('core');

$pending_reports = \local_gradereport_submit\report::get_pending_reports();

// Set the button properties.
if ($pending_reports) {
    foreach ($pending_reports as $rpt) {

        $courseid = $rpt->courseid; 
        $course = $DB->get_record('course', array('id' => $courseid));
        
        echo html_writer::div("Course Name: {$course->fullname}");

        $grader_url = new moodle_url('/grade/report/grader/index.php', ['id' => $courseid]);
        // Display the link in your custom plugin
        echo html_writer::link($grader_url, get_string('viewgrades', 'local_gradereport_submit'));

        $url = new moodle_url('/local/gradereport_submit/publish.php', ['courseid' => $course->id]);
        $button = new single_button($url, get_string('publishgrades', 'local_gradereport_submit'), 'post');

        echo $renderer->render($button);
    }
} else {
    echo html_writer::div("No pending reports.");
}


echo $OUTPUT->footer();
