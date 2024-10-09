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

    public static function submit_report_for_approval($courseid, $userid) {
        global $DB;
        // Get the current timestamp
        $currenttimestamp = time();

        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->submitted_by = $userid; 
        $record->submitted_date = $currenttimestamp; 
        $DB->insert_record('gradereport_submissions', $record);
    }


    public static function get_pending_reports() {
        global $DB;

        // Get all pending reports
        return $DB->get_records('gradereport_submissions', ['published' => 0]);
    }

    public static function submit_grade_report($courseid) {
        global $DB;
    
        $report = $DB->get_record('gradereport_submissions', ['courseid' => $courseid, 'published' => 0], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($courseid);
        $reportdata = 'course grades report data goes here';
        $submitted_date = $report->submitted_date;
        $submitted_by = $report->submitted_by;
        $adminconfig = get_config('local_gradereport_submit');
        $externalurl = $adminconfig->externalurl;
    
        // use CURL to send report data to the external URL
        // $curl = new curl();
        // $options = [
        //     'CURLOPT_RETURNTRANSFER' => true,
        //     'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
        // ];
    
        // $postdata = json_encode([
        //     'courseid' => $report->courseid,
        //     'reportdata' => $reportdata
        // ]);
    
        // $response = $curl->post($externalurl, $postdata, $options);


        //mark as submitted
        $DB->set_field('gradereport_submissions', 'published', 1, ['id' => $reportid]);
        $DB->set_field('gradereport_submissions', 'report_data', $reportdata,  ['id' => $reportid]);
        $DB->set_field('gradereport_submissions', 'published_date', time(),  ['id' => $reportid]);


        // if the current user is an admin with grade editing capabilities
        if (has_capability('moodle/grade:edit', $coursecontext)) {
            // Remove the permission to edit grades
            // You'll need to prevent grade editing by adjusting role capabilities
            $roleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
            if ($roleid) {
                // Disable course grade editing for instructors
                role_change_permission($roleid, $coursecontext , 'moodle/grade:edit', CAP_PROHIBIT);
            }
        }


        //raise a log create event
        $event = \local_gradereport_submit\event\eventgrade_report_submitted_log::create(array(
            'objectid' => $courseid,
            'context' => $coursecontext,
            'relateduserid' => $submitted_by
        ));
        
        $event->trigger();

        return $response;
    }
}
