<?php

/**
 * @package    iic_service
  */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_survey_functions extends external_api {

    public static function create_survey_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'wiki name'),
                'type' => new external_value(PARAM_INT, 'survey type')
                )
        );       
    }

    public static function create_survey($courseid,$name,$type)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_survey_parameters(),
                array('courseid' => $courseid,'name' => $name,'type' => $type)
            );
            
            $surveyname = $params['name'];
            $type = $params['type'];

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $surveyname;
            $newresource->modulename =  'survey';
            $newresource->course = $course->id;
            $newresource->section = 0;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => 'This is your survey', 'format' => FORMAT_HTML);

            $newresource->intro = "This is your " . $surveyname;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->template = $type;       
            // 1- SURVEY_COLLES_ACTUAL.. 2 - SURVEY_COLLES_PREFERRED.. 3 - SURVEY_COLLES_PREFERRED_ACTUAL.. 4 - SURVEY_ATTLS.. 5 - SURVEY_CIQ
            $savedcoursemodule = create_module($newresource);
            $moduleid = $savedcoursemodule->instance;
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

    public static function create_survey_returns()
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