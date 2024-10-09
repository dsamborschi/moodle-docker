<?php

/**
  * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_assign_functions extends external_api {

    public static function create_assignment_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'assignment name'),
                'section' => new external_value(PARAM_INT, 'section'),
                'allowsubmissiondate' => new external_value(PARAM_INT, 'submission allowed date'),
                'duedate' => new external_value(PARAM_INT, 'submission due date'),
                'activityid' => new external_value(PARAM_INT, 'activity id'),
                'grade' => new external_value(PARAM_INT, 'grade')
                )
        );       
    }

    public static function create_assignment($courseid,$name,$section,$allowsubmissiondate,$duedate,$activityid,$grade)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        require_once($CFG->dirroot . "/mod/assign/locallib.php");
        require_once($CFG->dirroot . "/mod/assign/assignmentplugin.php");
        require_once($CFG->dirroot . "/lib/modinfolib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_assignment_parameters(),
                array('courseid' => $courseid,'name' => $name,'section' => $section,
                'allowsubmissiondate' => $allowsubmissiondate,'duedate' => $duedate,'activityid' => $activityid,'grade' => $grade)
            );

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        

            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'assign';
            $newresource->course = $course->id;
            $newresource->section = $section;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;
            $newresource->groupingid = 0;
            $newresource->completion = 1;

            $newresource->introeditor = array('text' => $name, 'format' => FORMAT_HTML);
            $newresource->intro = $name;

            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->nosubmission = false;
            $newresource->submissiondrafts = true;
            $newresource->sendnotifications = true;
            $newresource->sendlatenotifications = true;
            $newresource->duedate = $duedate;
            $newresource->gradingduedate = time() + (7 * 24 * 3600);
            $newresource->allowsubmissionsfromdate = $allowsubmissiondate;

            $newresource->grade = $grade;
            $newresource->requiresubmissionstatement = true;
            $newresource->completionsubmit = true;
            $newresource->teamsubmission = false;
            $newresource->requireallteammemberssubmit = false;
            $newresource->teamsubmissiongroupingid = false;

            $newresource->blindmarking = false;
            $newresource->revealidentities = false;
            $newresource->attemptreopenmethod = "none";
            $newresource->maxattempts = 1;
            $newresource->markingworkflow = false;
            $newresource->markingallocation = false;

            $savedcoursemodule = create_module($newresource);

            //set the submission type to file for the created assignment
            $assign = $DB->get_record('assign', array('id' => $savedcoursemodule->instance));
            list($course, $cm) = get_course_and_cm_from_instance($assign, 'assign');
            $context = context_module::instance($cm->id);
            $createdassign = new assign($context, $cm, $course);
            $submissionplugin = $createdassign->get_submission_plugin_by_type('file');
            if($submissionplugin != null) {
                $submissionplugin->enable();
                $submissionplugin->set_config('maxfilesubmissions', 1);
            }

            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id,'module' => $activityid));
            
            rebuild_course_cache($course->id, true);            
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
        $result = array();
        $result['id'] = $moduleid;
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        $result['error'] = $error;
        return $result;
    }

    public static function create_assignment_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }   

    public static function update_assignment_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'name' => new external_value(PARAM_TEXT, 'assignment name'),
                'section' => new external_value(PARAM_INT, 'section'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence'),
                'allowsubmissiondate' => new external_value(PARAM_INT, 'submission allowed date'),
                'duedate' => new external_value(PARAM_INT, 'submission due date'),
                'grade' => new external_value(PARAM_INT, 'grade')
                )
        );       
    }

    public static function update_assignment($courseid,$moduleid,$name,$section,
    $sequence,$oldsequence,$allowsubmissiondate,$duedate,$grade)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $error = "";
        try{
            //validate parameter
            $params = self::validate_parameters(
                self::update_assignment_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'section' => $section,
                'sequence' => $sequence, 'oldsequence' => $oldsequence, 'allowsubmissiondate' => $allowsubmissiondate,
                'duedate' => $duedate, 'grade' => $grade)
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

            $moduleinfo = new stdClass();
            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->duedate = $duedate;
            $moduleinfo->allowsubmissionsfromdate = $allowsubmissiondate;
            $moduleinfo->grade = $grade;

            $DB->update_record('assign', $moduleinfo);

            if ($sequence !== "") {            
                // $secid = $DB->get_field('course_sections', 'id', array('section' => $sectionid, 'course' => $courseid));
                
                // $DB->set_field('course_sections', 'sequence',$oldsequence, array('section' => $cm->section, 'course' => $courseid));
                // $DB->set_field('course_sections', 'sequence',$sequence, array('section' => $sectionid, 'course' => $courseid));
                // $DB->set_field('course_modules', 'section',$secid, array('id' => $moduleid));
            }

            rebuild_course_cache($courseid, true);            

        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
        $result = array();
        $result['id'] = $moduleid;
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        $result['error'] = $error;
        return $result;    
    }

    public static function update_assignment_returns()
    {        
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }  
}