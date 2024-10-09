<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_file_functions extends external_api {

    public static function create_attachment_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'modtype' => new external_value(PARAM_TEXT, 'Module Type'),
                'filename' => new external_value(PARAM_RAW, 'file name'),
                'content' => new external_value(PARAM_RAW, 'file content')
                )
        );       
    }

    public static function create_attachment($courseid,$moduleid,$modtype,$filename,$content)
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
                self::create_attachment_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'modtype' => $modtype,
                'filename' => $filename,'content' => $content)
            );

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);

            $context = context_user::instance($USER->id);
            $usercontextid = $context->id;
            $component = "user";
            $filearea = "draft";
            $itemid = 0;
            $filepath = "/";
            $filename = $filename;
            $filecontent = $content;
            $contextlevel = null;
            $instanceid = null;           

            // Call the api to create a file.
            $fileinfo = core_files_external::upload($usercontextid, $component, $filearea, $itemid, $filepath,
            $filename, $filecontent, $contextlevel, $instanceid);
            $fileinfo = external_api::clean_returnvalue(core_files_external::upload_returns(), $fileinfo);
            // Get the created draft item id.

            $draftitemid = $fileinfo['itemid'];

            $moduledata = new stdClass();
            $moduledata->course = $course;
            $moduledata->draftitemid = $draftitemid;        
            $moduledata->coursemodule = $moduleid;
            $moduledata->displayname = $filename;

            $context = context_module::instance($moduleid);
            //file_save_draft_area_files($moduledata->draftitemid, $context->id,'' , 'attachment', 0, array('subdirs'=>true));

            $fs = get_file_storage();

            $draftfiles = $fs->get_area_files($usercontextid, 'user', 'draft', $draftitemid, 'id');

            if($modtype == "mod_folder" || $modtype == "mod_resource")
            {
                $filearea = 'content';
            }
            else
            {
                $filearea = 'introattachment';
            }
            $newhashes = array();

            foreach ($draftfiles as $file) {            
                $newhash = $fs->get_pathname_hash($context->id, $modtype, $filearea, 0, $file->get_filepath(), $file->get_filename());
                $newhashes[$newhash] = $file;
            }

            $fileid = 0;
            foreach ($newhashes as $file) {
                $uploadfilename = $file->get_filename();
                if($uploadfilename == $filename || $uploadfilename = ".")
                {
                    $file_record = array('contextid'=>$context->id, 'component'=>$modtype, 'filearea'=>$filearea, 
                    'itemid'=>0, 'timemodified'=>time());
                    if ($source = @unserialize($file->get_source())) {
                        // Field files.source for draftarea files contains serialised object with source and original information.
                        // We only store the source part of it for non-draft file area.
                        $file_record['source'] = $source->source;
                    }

                    if ($file->is_external_file()) {
                        $repoid = $file->get_repository_id();
                        if (!empty($repoid)) {
                            $file_record['repositoryid'] = $repoid;
                            $file_record['reference'] = $file->get_reference();
                        }
                    }
                    $fileid = $file->get_id();
                    $fs->create_file_from_storedfile($file_record, $file);
                }
            }
            //$files = $fs->get_area_files($context->id, $modtype, 'introattachment', 0, 'sortorder', false);
            // // Only ever one file - extract the contents.
            //$file = reset($files);

            // foreach ($files as $file) {
            //     $file->delete();
            // }
        
            //$success = $file->extract_to_storage(new zip_packer(), $context->id, $modtype, 'introattachment', 0, '/', $USER->id);
            //$fs->delete_area_files($context->id, 'mod_folder', 'temp', 0);        
            //$instanceid = folder_dndupload_handle($moduledata);
            $returnid = $fileid;
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

    public static function create_attachment_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }
    
    //Product Backlog Item 27808: Deleting file from Instructor Portal does not work.
     public static function delete_attachment_parameters()
     {
         return new external_function_parameters(
             array(
                 'contextid' => new external_value(PARAM_INT, 'course id'),
                 'component' => new external_value(PARAM_TEXT, 'module id'),
                 'filearea' => new external_value(PARAM_TEXT, 'Module Type'),
                 'itemid' => new external_value(PARAM_INT, 'item id'),
                 'filepath' => new external_value(PARAM_TEXT, 'file path'),
                 'filename' => new external_value(PARAM_TEXT, 'file name')
                 )
         );       
     }

     public static function delete_attachment($contextid,$component,$filearea,$itemid,$filepath,$filename)
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
         $returnid = 0;
         $error = "";

         try{
             //validate parameter
             $params = self::validate_parameters(
                 self::delete_attachment_parameters(),
                 array('contextid' => $contextid,'component' => $component,'filearea' => $filearea,
                 'itemid' => $itemid, 'filepath' => $filepath, 'filename' => $filename)
             );

             $fs = get_file_storage();

             if($filearea === null || trim($filearea) === '') {
                $deleteResult = $fs->delete_area_files($contextid, $component, false, $itemid);
                if(!$deleteResult) {
                    $error = "delete area files failed.";
                }
             }
             else {
                $file_to_delete = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

                 if($file_to_delete){
                      $file_to_delete->delete();
                 }
                 else {
                      $error = "file not found";
                 }
             }
             
             

             $returnid = -1;
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

     public static function delete_attachment_returns()
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