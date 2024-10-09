<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_folder_functions extends external_api {
    
    public static function create_folder_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'folder name'),
                'activityid' => new external_value(PARAM_INT, 'activity id')
                )
        );       
    }

    public static function create_folder($courseid,$section,$name,$activityid)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_folder_parameters(),
                array('courseid' => $courseid, 'section' => $section, 'name' => $name,'activityid' => $activityid)
            );

            $course = $DB->get_record('course', array('id' =>  $courseid), '*', MUST_EXIST); 
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'folder';
            $newresource->course = $course->id;
            $newresource->section = $section;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => 'This is your folder', 'format' => FORMAT_HTML);
            $newresource->alwaysshowdescription = true;
            $newresource->intro = "This is your " . $name;
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

    public static function create_folder_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }  

    public static function update_folder_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'name' => new external_value(PARAM_TEXT, 'folder name'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence')
                )
        );       
    }

    public static function update_folder($courseid,$moduleid,$section,$name,
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
                    self::update_folder_parameters(),
                    array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'section' => $section,
                    'sequence' => $sequence, 'oldsequence' => $oldsequence)
                );

                $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

                $moduleinfo = new stdClass();
                
                $moduleinfo->id =  $cm->instance;
                $moduleinfo->name =  $name;
                    
                $DB->update_record('folder', $moduleinfo);

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

    public static function update_folder_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    } 


    public static function uploadfiletofolder_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'filename' => new external_value(PARAM_TEXT, 'item name'),
                'foldername' => new external_value(PARAM_TEXT, 'folder name')
                )
        );       
    }

    public static function uploadfiletofolder($courseid,$filename,$foldername)
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
        
        $returnid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::uploadfiletofolder_parameters(),
                array('courseid' => $courseid,'filename' => $filename,'foldername' => $foldername)
            );
            
            $courseid = $params['courseid'];
            $fname  = $params['filename'];
            $foldername = $params['foldername'];

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST); 
        
            $context = context_user::instance($USER->id);
            $contextid = $context->id;
            $component = "user";
            $filearea = "draft";
            $itemid = 0;
            $filepath = "/";
            $filename = $fname;
            $filecontent = base64_encode("Creating simple file for folder - " . $fname);
            $contextlevel = null;
            $instanceid = null;           

            // Call the api to create a file.
            $fileinfo = core_files_external::upload($contextid, $component, $filearea, $itemid, $filepath,
                        $filename, $filecontent, $contextlevel, $instanceid);
            $fileinfo = external_api::clean_returnvalue(core_files_external::upload_returns(), $fileinfo);
            // Get the created draft item id.

            $draftitemid = $fileinfo['itemid'];
        
            $newresource = new stdClass();
            $newresource->name = $foldername;
            $newresource->modulename =  'folder';
            $newresource->course = $course->id;
            $newresource->section = 0;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;        

            $newresource->introeditor = array('text' => 'This is your folder', 'format' => FORMAT_HTML);
            $newresource->alwaysshowdescription = true;
            $newresource->intro = "This is your " . $foldername;
            $newresource->introformat = true;
            $newresource->showexpanded = true;
            $newresource->showdownloadfolder = true;  
            $newresource->showexpanded = true;
            $newresource->revision = 1;
            $newresource->display = 0;

            $savedcoursemodule = create_module($newresource);

            //$moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id));
            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id,'module' => 72));

            $moduledata = new stdClass();
            $moduledata->course = $course;
            $moduledata->draftitemid = $draftitemid;        
            $moduledata->coursemodule = $moduleid;
            $moduledata->displayname = $fname;

            $context = context_module::instance($moduleid);
            file_save_draft_area_files($moduledata->draftitemid, $context->id, 'mod_folder', 'content', 0, array('subdirs'=>true));
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'mod_folder', 'content', 0, 'sortorder', false);
            // // Only ever one file - extract the contents.
            $file = reset($files);
        
            $success = $file->extract_to_storage(new zip_packer(), $context->id, 'mod_folder', 'content', 0, '/', $USER->id);
            //$fs->delete_area_files($context->id, 'mod_folder', 'temp', 0);
            
            //$instanceid = folder_dndupload_handle($moduledata);
            $returnid = $context->id;
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

    public static function uploadfiletofolder_returns()
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
