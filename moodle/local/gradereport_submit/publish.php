<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);

//submit for approval
$response = \local_gradereport_submit\report::submit_grade_report($courseid);

