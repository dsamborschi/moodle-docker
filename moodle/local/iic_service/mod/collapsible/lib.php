<?php

  /**
  * @package    iic_service
  */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_collapsible_functions extends external_api {

    public static function get_collapsible_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id')
                )
        );       
    }

    public static function get_collapsible($id)
    {
        global $CFG, $USER, $DB; 

        //validate parameter
        $params = self::validate_parameters(
            self::get_collapsible_parameters(),
            array('id' => $id)
        );

        $collapsibles = $DB->get_records('collapsible', array('id' => $params['id']));        

        $results = array();

        foreach($collapsibles as $collapsible) {
            $result = array();

            $result["id"] = $collapsible->id;
            $result["name"] = $collapsible->name;
            $result["intro"] = $collapsible->intro;

            $results[] = $result;
        }

        return $results;        
    }

    public static function get_collapsible_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'=> new external_value(PARAM_INT, 'module id'),
                    'name'=> new external_value(PARAM_TEXT, 'name'),
                    'intro'=> new external_value(PARAM_TEXT, 'intro'),
                    'error'   => new external_value(PARAM_TEXT, 'error'),
                    'version'   => new external_value(PARAM_TEXT, 'version')                           
                )
            ));       
    }

    public static function create_collapsible_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'label name'),
                'intro' => new external_value(PARAM_TEXT, 'intro'),
                'activityid' => new external_value(PARAM_INT, 'activity id')
                )
        );       
    }

    public static function create_collapsible($courseid,$section,$name,$intro,$activityid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $moduleid = 123;
        $error = "";

        try{
            $course = $DB->get_record('course', array('id' =>  $courseid), '*', MUST_EXIST); 
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'collapsible';
            $newresource->course = $course->id;
            $newresource->section = $section;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        
            $newresource->groupingid = 0;
            $newresource->completion = 1;

            $newresource->introeditor = array('text' => '', 'format' => FORMAT_HTML);
            $newresource->alwaysshowdescription = true;
            $newresource->1 = $intro;
            $newresource->summary = $intro;
            $newresource->description = $intro;
            $newresource->intro = $intro;
            $newresource->introformat = true;
            $newresource->showexpanded = true;
            $newresource->showdownloadfolder = true;  
            $newresource->showexpanded = true;
            $newresource->revision = 1;
            $newresource->display = 0;

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

    public static function create_collapsible_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    } 

    public static function update_collapsible_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'label name'),
                'intro' => new external_value(PARAM_RAW, 'intro'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence')
                )
        );       
    }

    public static function update_collapsible($courseid,$moduleid,$section,$name,$intro,
    $sequence,$oldsequence)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::update_collapsible_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'intro' => $intro,'section' => $section,
                'sequence' => $sequence, 'oldsequence' => $oldsequence)
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

            $moduleinfo = new stdClass();
            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->intro =  $intro;

            $DB->update_record('collapsible', $moduleinfo);

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

    public static function update_collapsible_returns()
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