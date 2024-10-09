<?php

namespace local_gradereport_submit;

defined('MOODLE_INTERNAL') || die();

class report {

    public static function get_users_and_grades($courseid) {
        global $DB;
    
        // Load users enrolled in the course.
        $users = $DB->get_records_sql("
            SELECT u.id, u.username, u.firstname, u.lastname
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            WHERE e.courseid = ?
        ", [$courseid]);
    
        // Load grades for the enrolled users.
        $grades = $DB->get_records_sql("
            SELECT g.userid, g.finalgrade
            FROM {grade_items} gi
            JOIN {grade_grades} g ON g.itemid = gi.id
            WHERE gi.courseid = ?
        ", [$courseid]);
    
        // Combine users and grades.
        foreach ($users as &$user) {
            $user->grade = isset($grades[$user->id]) ? $grades[$user->id]->finalgrade : null;
        }
    
        return $users;
    }

    public static function submit_report_for_approval($courseid) {
        global $DB;
        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->submitted = 0; // Pending approval.
        $DB->insert_record('gradereport_submissions', $record);

    }


    public static function get_pending_reports() {
        global $DB;

        // Get all pending reports
        return $DB->get_records('gradereport_submissions', ['submitted' => 0]);
    }

    public static function submit_grade_reports($reportid) {
        global $DB;
    
        $report = $DB->get_record('gradereport_submissions', ['id' => $reportid, 'submitted' => 0], '*', MUST_EXIST);
        $courseid = $report->courseid;
        $reportdata = 'course grades report data goes here';
        $submitted_date = time();
        $submitted_by = $USER->id;
        $adminconfig = get_config('local_gradereport_submit');
        $externalurl = $adminconfig->externalurl;
    
        // use CURL to send report data to the external URL
        $curl = new curl();
        $options = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
        ];
    
        $postdata = json_encode([
            'courseid' => $report->courseid,
            'reportdata' => $reportdata
        ]);
    
        $response = $curl->post($externalurl, $postdata, $options);


        //mark as submitted
        $DB->set_field('gradereport_submissions', 'submitted', 1, ['id' => $reportid]);
        $DB->set_field('gradereport_submissions', 'submitted_date', $submitted_date, ['id' => $reportid]);
        $DB->set_field('gradereport_submissions', 'submitted_by', $submitted_by, ['id' => $reportid]);
        $DB->set_field('gradereport_submissions', 'report_data', $reportdata,  ['id' => $reportid]);


        //raise a log create event

        $event = grade_report_submitted_log::create(array(
            'objectid' => $courseid,
            'context' => context_course::instance($courseid),
            'relateduserid' => $submitted_by
        ));
        
        $event->trigger();

        return $response;
    }
}
