<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_wiki_functions extends external_api {

    public static function create_wiki_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'wiki name')
                )
        );       
    }

    public static function create_wiki($courseid,$name)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");

        $returnid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_wiki_parameters(),
                array('courseid' => $courseid,'name' => $name)
            );
            
            $wikiname = $params['name'];
            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $wikiname;
            $newresource->modulename =  'wiki';
            $newresource->course = $course->id;
            $newresource->section = 0;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => 'This is your wiki', 'format' => FORMAT_HTML);

            $newresource->intro = "This is your " . $wikiname;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->firstpagetitle = "First Page";
            $newresource->wikimode = "collaborative";
            $newresource->defaultformat = "html";
            $newresource->forceformat = false;

            $savedcoursemodule = create_module($newresource);
            $returnid = $savedcoursemodule->instance;
            rebuild_course_cache($course->id, true);            
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        } 

        $result = array();
        $result['id'] = $returnid;  
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        $result['error'] = $error;
        return $result; 
    }

    public static function create_wiki_returns()
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