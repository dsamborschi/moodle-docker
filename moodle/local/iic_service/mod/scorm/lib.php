<?php

/**
  * @package    iic_service
  */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_scorm_functions extends external_api {

    public static function create_scormpackage_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'scorm name'),
                'filename' => new external_value(PARAM_TEXT, 'file name'),
                'filecontent' => new external_value(PARAM_TEXT, 'file content')
                )
        );       
    }

    public static function create_scormpackage($courseid,$name,$filename,$filecontent)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/files/externallib.php");
        require_once($CFG->dirroot . "/lib/filelib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        require_once($CFG->dirroot . "/mod/folder/lib.php");
        require_once($CFG->dirroot.'/repository/lib.php');
        require_once($CFG->dirroot.'/repository/upload/lib.php');
        require_once($CFG->dirroot . "/course/dnduploadlib.php");
        require_once($CFG->dirroot . "/mod/scorm/locallib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_scormpackage_parameters(),
                array('courseid' => $courseid,'name' => $sname,'filename' => $filename,'filecontent' => $filecontent)
            );

            $scormname = $params['name'];
            $fname  = $params['filename'];
            $fcontent  = $params['filecontent'];

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        

            $context = context_user::instance($USER->id);
            $contextid = $context->id;
            $component = "user";
            $filearea = "draft";
            $itemid = 0;
            $filepath = "/";
            $filename = $fname;
            $filecontent = $fcontent;
            $contextlevel = null;
            $instanceid = null;           

            // Call the api to create a file.
            $fileinfo = core_files_external::upload($contextid, $component, $filearea, $itemid, $filepath,
                        $filename, $filecontent, $contextlevel, $instanceid);
            $fileinfo = external_api::clean_returnvalue(core_files_external::upload_returns(), $fileinfo);
            // Get the created draft item id.

            $draftitemid = $fileinfo['itemid'];
        
            $newresource = new stdClass();
            $newresource->name = $scormname;
            $newresource->modulename =  'scorm';
            $newresource->course = $course->id;
            $newresource->section = 0;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;
            $newresource->groupingid = 0;
            $newresource->completion = 1;

            $newresource->introeditor = array('text' => 'This is your scorm', 'format' => FORMAT_HTML);

            $newresource->intro = "This is your " . $scormname;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->scormtype = "local";
            $newresource->reference = $fname;
            $newresource->version = "SCORM_1.2";
            $newresource->maxgrade = 99;
            $newresource->grademethod = 1;
            $newresource->whatgrade = 0;
            $newresource->source=$fname;
            $newresource->skipview=0;
            $newresource->width=100;
            $newresource->height=50;

            $newresource->popup=1;
            $newresource->options="scrollbars=0,directories=0,location=0,menubar=0,toolbar=0,status=0";

            //$newresource->timeopen=time();

            $savedcoursemodule = create_module($newresource);

            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id,'module' => 18));

            //function not available in refined data
            scorm_update_calendar($newresource, $moduleid);

            $moduledata = new stdClass();
            $moduledata->course = $course;
            $moduledata->draftitemid = $draftitemid;        
            $moduledata->coursemodule = $moduleid;
            $moduledata->displayname = $fname;

            $context = context_module::instance($moduleid);
            file_save_draft_area_files($moduledata->draftitemid, $context->id, 'mod_scorm', 'package', 0, array('subdirs'=>true));
            //$fs = get_file_storage();
            //$files = $fs->get_area_files($context->id, 'mod_scorm', 'package', 0, 'sortorder', false);
            // // Only ever one file - extract the contents.
            //$file = reset($files);
        
            //$success = $file->extract_to_storage(new zip_packer(), $context->id, 'mod_scorm', 'content', 0, '/', $USER->id);
            //$fs->delete_area_files($context->id, 'mod_folder', 'temp', 0);
            
            //$instanceid = folder_dndupload_handle($moduledata);


            $scorm = $DB->get_record('scorm', array('course' =>  $course->id, 'reference' => $fname), '*', MUST_EXIST);

            scorm_parse($scorm, true);
            $scormnew = $DB->get_record('scorm', array('course' =>  $course->id, 'reference' => $fname), '*', MUST_EXIST);
            $moduleid = $scormnew->id;
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

    public static function create_scormpackage_returns()
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