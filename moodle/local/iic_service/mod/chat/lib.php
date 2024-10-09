<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_chat_functions extends external_api {

    
    public static function create_chat_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'chat name')
                )
        );       
    }

    public static function create_chat($courseid,$name)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_chat_parameters(),
                array('courseid' => $courseid,'name' => $chatname)
            );
            
            $chatname = $params['name'];

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $chatname;
            $newresource->modulename =  'chat';
            $newresource->course = $course->id;
            $newresource->section = 2;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => 'This is your chat', 'format' => FORMAT_HTML);

            $newresource->intro = "This is your " . $chatname;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->keepdays = 180;
            $newresource->schedule = 2;
            $newresource->chattime = time() + (7 * 24 * 3600);

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

    public static function create_chat_returns()
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