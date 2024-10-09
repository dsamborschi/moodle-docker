<?php

/**
 * External Web Service Template
  * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");

class iic_service_module_functions extends external_api {

    public static function duplicate_module_parameters()
    {
        return new external_function_parameters(
            array(
                'origcourseid' => new external_value(PARAM_INT, 'original course id'),
                'moduleid' => new external_value(PARAM_TEXT, 'module id'),
                'newcourseid' => new external_value(PARAM_TEXT, 'new course id'),
                )
        );       
    }

    public static function duplicate_module($origcourseid,$moduleid,$newcourseid)
    {
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/lib/datalib.php");
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->libdir . '/filelib.php');
        //validate parameter
        $params = self::validate_parameters(
            self::duplicate_module_parameters(),
            array('origcourseid' => $origcourseid,'moduleid' => $moduleid,'newcourseid' => $newcourseid)
        );
        
        $origcourseid = $params['origcourseid'];
        $moduleid = $params['moduleid'];
        $newcourseid = $params['newcourseid'];

        $cm = get_coursemodule_from_id(null, $moduleid, 0, true, MUST_EXIST);
        $course = $DB->get_record('course', array('id' =>  $newcourseid), '*', MUST_EXIST);        

        //$newmodule = duplicate_module($course, $module);

        //Added code to eliminate ddltablenotexist error 
        $a          = new stdClass();
        $a->modtype = get_string('modulename', $cm->modname);
        $a->modname = format_string($cm->name);    

        // Backup the activity.    
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $cm->id, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
    
        $backupid       = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
    
        $bc->execute_plan();
    
        $bc->destroy();
    
        // Restore the backup immediately.
    
        $rc = new restore_controller($backupid, $course->id,
                backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
    
        // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
        $plan = $rc->get_plan();
        $groupsetting = $plan->get_setting('groups');
        if (empty($groupsetting->get_value())) {
            $groupsetting->set_value(true);
        }
    
        $cmcontext = context_module::instance($cm->id);
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }
    
        $rc->execute_plan();    
        // Now a bit hacky part follows - we try to get the cmid of the newly
        // restored copy of the module.
        $newcmid = null;
        $tasks = $rc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
        }
    
        $rc->destroy();
    
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

        if ($newcmid) {
            $newcm = get_fast_modinfo($newcourseid)->get_cm($newcmid);
            $event = \core\event\course_module_created::create_from_cm($newcm);
            $event->trigger();
        }

        return $newcm->id;
    }

    public static function duplicate_module_returns()
    {
        return new external_value(PARAM_INT, 'id');        
    }  

    public static function get_moduleid_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'cmid' => new external_value(PARAM_TEXT, 'module id')
                )
        );       
    }

    public static function get_moduleid($courseid,$cmid)
    {
        global $CFG, $USER, $DB; 

        //validate parameter
        $params = self::validate_parameters(
            self::get_moduleid_parameters(),
            array('courseid' => $courseid,'cmid' => $cmid)
        );

        $moduleid = $DB->get_field('course_modules', 'module', array('id' => $params['cmid'], 'course' => $params['courseid']));

        return $moduleid;
    }

    public static function get_moduleid_returns()
    {
        return new external_value(PARAM_INT, 'id');        
    }   

    public static function get_allmodules_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id')
                )
        );       
    }

    public static function get_allmodules($id)
    {
        global $CFG, $USER, $DB; 

        //validate parameter
        $params = self::validate_parameters(
            self::get_allmodules_parameters(),
            array('id' => $id)
        );

        $modules = $DB->get_records('modules');

        $results = array();
        foreach($modules as $module) {

            //$qtype = question_bank::get_qtype($question->qtype, false);           

            $resultdata = array();
            $resultdata['id'] = $module->id;
            $resultdata['name'] = $module->name;              

            $results[] = $resultdata;
        }

        return $results;        
    }

    public static function get_allmodules_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'module id'),
                                'name'=> new external_value(PARAM_TEXT, 'name')                                                                 
                )
            ));    
    }

}