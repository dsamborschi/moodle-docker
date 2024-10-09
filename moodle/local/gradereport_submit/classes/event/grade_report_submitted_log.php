<?php
namespace local_gradereport_submit\event;

defined('MOODLE_INTERNAL') || die();

class grade_report_submitted_log extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c'; // 'c' for create, 'r' for read, 'u' for update, 'd' for delete
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }

    public static function get_name() {
        return get_string('coursesubmittedlog', 'local_gradereport_submit');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' submiited grade report on course '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/course/view.php', array('id' => $this->objectid));
    }
}
