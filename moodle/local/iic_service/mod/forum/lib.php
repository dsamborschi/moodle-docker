<?php

  /**
  * @package    iic_service
  */

  require_once($CFG->libdir . "/externallib.php");
  require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_forum_functions extends external_api {


    public static function create_forum_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'forum name'),
                'intro' => new external_value(PARAM_RAW, 'forum intro'),
                'type' => new external_value(PARAM_TEXT, 'forum type'),
                'section' => new external_value(PARAM_INT, 'section'),
                'activityid' => new external_value(PARAM_INT, 'activity id')
                )
        );       
    }

    public static function create_forum($courseid,$name,$intro,$type,$section,$activityid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_forum_parameters(),
                array('courseid' => $courseid,'name' => $name,'intro' => $intro,'type' => $type,'section' => $section,'activityid' => $activityid)
            );

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'forum';
            $newresource->course = $course->id;
            $newresource->section = $section;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;
            $newresource->groupmode = 0;
            $newresource->introfiles = [];

            $newresource->groupingid = 0;
            $newresource->completion = 1;
            $newresource->introeditor = array('text' => $name, 'format' => FORMAT_HTML);
            $newresource->intro = $name;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;

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


    public static function create_forum_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function update_forum_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'forum name'),
                'intro' => new external_value(PARAM_RAW, 'forum intro'),
                'type' => new external_value(PARAM_TEXT, 'forum type'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),                
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence')
                )
        );       
    }

    public static function update_forum($courseid,$moduleid,$section,$name,$intro,$type,$sequence,$oldsequence)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $error = "";
        try
        {
            //validate parameter
            $params = self::validate_parameters(
                self::update_forum_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'section' => $section,'intro' => $intro,'type' => $type,
                'sequence' => $sequence, 'oldsequence' => $oldsequence)
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);
            $moduleinfo = new stdClass();            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->intro = $intro;
            $moduleinfo->type = $type;

            $DB->update_record('forum', $moduleinfo);
            
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

    public static function update_forum_returns()
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