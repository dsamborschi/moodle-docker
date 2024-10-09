<?php


// require_once('../../config.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the global navigation to add a custom menu item.
 *
 * @param global_navigation $navigation
 */
// function local_gradereport_submit_extend_navigation_course($navigation, $course, $context) {
//     // Create a new node in the navigation.
//     $customnode = navigation_node::create(
//         'Submit Grades', // The text for the menu.
//         new moodle_url('/local/gradereport_submit/submit.php', ['courseid' => $course->id]), // Link for the menu.
//         navigation_node::TYPE_CUSTOM,
//         null,
//         'custommenu',
//         new pix_icon('i/navigationitem', '')
//     );

//     // Add the node to the course navigation.
//     $navigation->add_node($customnode);
// }


// Hook into the grading report page
function local_gradereport_submit_extend_navigation_course($navigation, $course, $context) {
    global $PAGE, $OUTPUT;

    // Check if the current page is the grade report grader page
    if ($PAGE->pagelayout == 'report' && $PAGE->pagetype == 'grade-report-grader-index' && $PAGE->user_is_editing() === false) {

       $PAGE->requires->css('/local/gradereport_submit/styles.css');

       $templatecontext = (object)[
        'link' => '/local/gradereport_submit/submit.php?courseid=',
        'courseid' => $course->id
      ];

      // Load the Mustache template and render it with the provided context.
      echo $OUTPUT->render_from_template('local_gradereport_submit/submit_grades_button', $templatecontext);

    }
}




