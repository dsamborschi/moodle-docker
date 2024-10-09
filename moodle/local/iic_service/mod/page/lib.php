<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_page_functions extends external_api {

    public static function create_page_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'page name'),
                'pagecontent' => new external_value(PARAM_RAW, 'page content'),
                'activityid' => new external_value(PARAM_INT, 'activity id')         
                )
        );       
    }

    public static function create_page($courseid,$section,$name,$pagecontent,$activityid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_page_parameters(),
                array('courseid' => $courseid, 'section' => $section, 'name' => $name, 'pagecontent' => $pagecontent,'activityid' => $activityid)
            );

            $course = $DB->get_record('course', array('id' =>  $courseid), '*', MUST_EXIST); 
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'page';
            $newresource->course = $course->id;
            $newresource->section = $section;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => $name, 'format' => FORMAT_HTML);
            $newresource->alwaysshowdescription = true;
            $newresource->intro = $name;
            $newresource->introformat = true;
            
            $newresource->content = $pagecontent;  
            $newresource->contentformat = true;
            
            $savedcoursemodule = create_module($newresource);

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

    public static function create_page_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }      

    public static function update_page_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'page name'),
                'pagecontent' => new external_value(PARAM_RAW, 'page content'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence')
            )
        );       
    }

    public static function update_page($courseid,$moduleid,$section,$name,$pagecontent,
    $sequence,$oldsequence)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");

        $error = "";
        try
        {
            //validate parameter
            $params = self::validate_parameters(
                self::update_page_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'section' => $section,
                'sequence' => $sequence, 'oldsequence' => $oldsequence, 'pagecontent' => $pagecontent)
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

            $moduleinfo = new stdClass();
            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->content = $pagecontent;   

            $DB->update_record('page', $moduleinfo);

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

    public static function update_page_returns()
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