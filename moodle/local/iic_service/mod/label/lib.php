<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_label_functions extends external_api {

    public static function create_label_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_RAW, 'label name'),
                'section' => new external_value(PARAM_INT, 'section'),
                'activityid' => new external_value(PARAM_INT, 'activity id')
                )
        );       
    }

    public static function create_label($courseid,$name,$section,$activityid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");

        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_label_parameters(),
                array('courseid' => $courseid,'name' => $name,'section' => $section,'activityid' => $activityid)
            );

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'label';
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

    public static function create_label_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function update_label_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'label name'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),                
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence')
                )
        );       
    }

    public static function update_label($courseid,$moduleid,$section,$name,$sequence,$oldsequence)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $error = "";
        try
        {
            //validate parameter
            $params = self::validate_parameters(
                self::update_label_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'section' => $section,
                'sequence' => $sequence, 'oldsequence' => $oldsequence)
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

            $moduleinfo = new stdClass();
            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->intro = $name;

            $DB->update_record('label', $moduleinfo);

            
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

    public static function update_label_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    } 

    public static function get_label_backup_parameters()
    {
        return new external_function_parameters(
            array(
                'labelid' => new external_value(PARAM_INT, 'label id')                
                )
        );       
    }

    public static function get_label_backup($labelid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/xml/output/memory_xml_output.class.php');
        require_once($CFG->dirroot . '/mod/label/backup/moodle2/backup_label_stepslib.php');

        $backupid = 'Backup ID for label ' . $labelid;

        $label = define_structure();

        //$activity = prepare_activity_structure($label);
        // // Create the backup_ids_temp table
        backup_controller_dbops::create_backup_ids_temp_table($backupid);

        // // Instantiate in memory xml output
        $xo = new memory_xml_output();

        // Instantiate xml_writer and start it
        $xw = new xml_writer($xo);
        $xw->start();

        // // Instantiate the backup processor
        $processor = new backup_structure_processor($xw);

        // //hardcoding context id for now
        $contextid = 155;

        // // Set some variables
        $processor->set_var(backup::VAR_ACTIVITYID, $labelid);
        $processor->set_var(backup::VAR_BACKUPID, $backupid);
        $processor->set_var(backup::VAR_CONTEXTID,$contextid);

        // Process the backup structure with the backup processor
        $label->process($processor);

        // // Stop the xml_writer
        $xw->stop();

        $dom = new DomDocument();
        $dom->loadXML($xo->get_allcontents());

        $xml = $dom->saveHTML();

        return $xml;
    }

    public static function get_label_backup_returns()
    {
        return new external_value(PARAM_RAW, 'label');        
    }
}