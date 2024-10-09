<?php

/**
 * External Web Service Template
 *
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');

class iic_service_course_functions extends external_api {

    public static function update_coursesection_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'section name'),
                'section' => new external_value(PARAM_INT, 'section') ,
                'summary' => new external_value(PARAM_RAW, 'summary')
                )
        );     
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function update_coursesection($courseid, $name, $section, $summary)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/format/lib.php');
        require_once($CFG->dirroot . "/course/lib.php");
        $moduleid = 0;
        $error = "";

        try{
            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
            rebuild_course_cache($courseid, true);
            $record = $DB->get_record('course_sections', array('course' =>  $courseid,'section' => $section), '*');
            if($record == false) {
                $lastsectionnumber = course_get_format($course)->get_course()->numsections;
                course_create_sections_if_missing((object)$course, range(0, $lastsectionnumber));
                $record = $DB->get_record('course_sections', array('course' =>  $courseid,'section' => $section), '*', MUST_EXIST);
            }

            $coursesection = new stdClass();
            $coursesection->id = $record->id;        
            $coursesection->name = $name;

            if ($summary != "placeholder") {
                $coursesection->summary = format_text($summary, FORMAT_HTML);
                $coursesection->summaryformat = FORMAT_HTML;
            }
            $savedcoursesection = $DB->update_record('course_sections', $coursesection);
            $moduleid = $record->id;
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

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function update_coursesection_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }

    public static function uploadfile_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'section' => new external_value(PARAM_INT, 'section'),
                'filename' => new external_value(PARAM_TEXT, 'item name'),
                'filecontent' => new external_value(PARAM_RAW, 'file content'),
                'activityid' => new external_value(PARAM_INT, 'activity id'),
                'resourcename' => new external_value(PARAM_TEXT, 'resource name')
                )
        );       
    }

    public static function uploadfile($courseid,$section,$filename,$filecontent,$activityid,$resourcename)
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
            $moduleid = 123;
            $error = "";

            try{

                //validate parameter
                $params = self::validate_parameters(
                    self::uploadfile_parameters(),
                    array('courseid' => $courseid,'section' => $section,'filename' => $filename, 'filecontent' => $filecontent, 
                    'activityid' => $activityid, 'resourcename' => $resourcename)
                );
                
                $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);            
                $context = context_user::instance($USER->id);
                $coursecontext = context_course::instance($course->id); 

                $usercontextid = $context->id;
                
                // //upload file
                $component = "user";
                $filearea = "draft";
                $itemid = 0;
                $filepath = "/";
                $filecontent = $filecontent;
                $contextlevel = null;
                $instanceid = null;    
            
                // Call the api to create a file.
                $fileinfo = core_files_external::upload($usercontextid, $component, $filearea, $itemid, $filepath,
                $filename, $filecontent, $contextlevel, $instanceid);
                $fileinfo = external_api::clean_returnvalue(core_files_external::upload_returns(), $fileinfo);
                // Get the created draft item id.    
                $draftitemid = $fileinfo['itemid'];

                // $moduledata = new stdClass();
                // $moduledata->course = $course;
                // $moduledata->draftitemid = $draftitemid;        
                // $moduledata->coursemodule = $moduleid;
                // $moduledata->displayname = $filename;

                // $context = context_module::instance($moduleid);
                // //file_save_draft_area_files($moduledata->draftitemid, $context->id,'' , 'attachment', 0, array('subdirs'=>true));
                // $modtype = 'mod_resource';
                // $filerecord = array('component' => 'user', 'filearea' => 'draft', 'contextid' => $usercontextid,
                // 'itemid' => $draftitemid, 'filename' => $filename, 'filepath' => '/');
                // $fs = get_file_storage();
                // $fs->create_file_from_string($filerecord, 'Test');
                
                //create new newsource activity
                $newresource = new stdClass();
                $newresource->name = $resourcename;
                $newresource->modulename =  'resource';
                $newresource->course = $course->id;
                $newresource->files = $draftitemid;
                $newresource->section = $section;   
                $newresource->visible = true;
                $newresource->visibleoncoursepage = true;
                $newresource->groupingid = 0;
                $newresource->completion = 1;
                $newresource->introeditor = array('text' => 'This is a module', 'format' => FORMAT_HTML, 'itemid' => $draftitemid);
                $savedcoursemodule = create_module($newresource);
                $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id,'module' => $activityid));

                // $draftfiles = $fs->get_area_files($usercontextid, 'user', 'draft', $draftitemid, 'id');
                // $filearea = 'introattachment';
                // $newhashes = array();           

                // foreach ($draftfiles as $file) {            
                //     $newhash = $fs->get_pathname_hash($context->id, $modtype, $filearea, 0, $file->get_filepath(), $file->get_filename());
                //     $newhashes[$newhash] = $file;
                // }

                // $fileid = 0;
                // foreach ($newhashes as $file) {
                //     $uploadfilename = $file->get_filename();
                //     if($uploadfilename == $filename || $uploadfilename = ".")
                //     {
                //         $file_record = array('contextid'=>$usercontextid, 'component'=>$modtype, 'filearea'=>$filearea, 
                //         'itemid'=>0, 'timemodified'=>time());

                //         $source = @unserialize($file->get_source());

                //         $file_record['source'] = $source->source;
                //         $repoid = $file->get_repository_id();

                //         if (!empty($repoid)) {
                //             $file_record['repositoryid'] = $repoid;
                //             $file_record['reference'] = $file->get_reference();
                //         }

                //         // if ($source = @unserialize($file->get_source())) {
                //         //     // Field files.source for draftarea files contains serialised object with source and original information.
                //         //     // We only store the source part of it for non-draft file area.
                //         //     $file_record['source'] = $source->source;
                //         // }

                //         // if ($file->is_external_file()) {
                //         //     if (!empty($repoid)) {
                //         //         $file_record['repositoryid'] = $repoid;
                //         //         $file_record['reference'] = $file->get_reference();
                //         //     }
                //         // }
                //         $fileid = $file->get_id();
                //         $fs->create_file_from_storedfile($file_record, $file);
                //     }
                // }        
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

    public static function uploadfile_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_RAW, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );    
    }  

    public static function get_course_format_options_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid')
                )
        );       
    }

    public static function get_course_format_options($courseid)
    {
        global $CFG, $USER, $DB;          

        try
        {  
            $params = self::validate_parameters(
                self::get_course_format_options_parameters(),
                array('courseid' => $courseid)
            );
    
            $courseid = $params['courseid'];

            $formatOptions = $DB->get_records('course_format_options', array('courseid' =>  $courseid));

            $fresults = array();

            foreach($formatOptions as $key => $option) {

                $fresult = array();            
                $fresult['format'] = $option->format;
                $fresult['name'] = $option->name;
                $fresult['value'] = $option->value;

                $fresults[] = $fresult;
            }

            return $fresults;

        }
        catch (Exception $e) {
            return $e->getMessage();
        }  
    }

    public static function get_course_format_options_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                        'format'=> new external_value(PARAM_RAW, 'format'),
                        'name'=> new external_value(PARAM_RAW, 'name'),
                        'value'=> new external_value(PARAM_RAW, 'value')
                    )           
            ));
    } 

    public static function backup_course_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'includeuser' => new external_value(PARAM_INT, 'include user')
                )
        );       
    }

    public static function backup_course($courseid, $includeuser)
    {
        global $CFG, $DB;
        
        $pluginmanager = core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('local_iic_service');       
        $backupPath = $plugininfo->rootdir . "/backup/backup_course_" . $courseid;
        $admin = get_admin();

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $bc = new backup_controller(backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id); 
        $bc->get_plan();
        $bc->get_plan()->get_setting('users')->set_value($includeuser);
        $bc->execute_plan();
        $result = $bc->get_results();

        $file = $result['backup_destination'];        

        //$file->copy_content_to($dir);
        $fp = get_file_packer('application/vnd.moodle.backup'); 
        $file->extract_to_pathname($fp, $backupPath);

        $dirname = "";

        if(!empty($backupPath) && is_dir($backupPath) ){
            $dir  = new RecursiveDirectoryIterator($backupPath, RecursiveDirectoryIterator::SKIP_DOTS); //upper dirs are not included,otherwise DISASTER HAPPENS :)
            $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

            foreach($files as $filename) {
                //$xml=readfile($filename);
                $backupresult = array();
                $backupresult["name"] = basename($filename);
                $dirname = dirname($filename);
                $backupresult["basepath"] = basename($dirname);

                if (is_file($filename))
                {
                    $backupresult["isfile"] = 1;
                }
                else
                {
                    $backupresult["isfile"] = 0;
                }

                if (strpos($dirname,"files") !== false) {
                    $backupresult["backupdata"] = base64_encode(file_get_contents($filename));
                    $backupresult["encoded"] = 1;
                }
                else
                {
                    $backupresult["backupdata"] = file_get_contents($filename, FILE_TEXT);
                    $backupresult["encoded"] = 0;
                }

                $backupresults[] = $backupresult;
            }            
            foreach ($files as $f) {if (is_file($f)) {unlink($f);} else {$empty_dirs[] = $f;} } if (!empty($empty_dirs)) {foreach ($empty_dirs as $eachDir) {rmdir($eachDir);}} rmdir($backupPath);
        }
        
        $bc->destroy();        
        return $backupresults;
    }

    public static function backup_course_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                        'name'=> new external_value(PARAM_TEXT, 'name'),
                        'isfile'=> new external_value(PARAM_INT, 'isfile'),
                        'basepath'=> new external_value(PARAM_TEXT, 'basepath'),
                        'encoded'=> new external_value(PARAM_INT, 'encoded'),
                        'backupdata'=> new external_value(PARAM_RAW, 'backup data')                                              
                    )           
            ));     
    }   

    public static function restore_course_parameters()
    {
        return new external_function_parameters(
            array(
                'backups' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'backupdata' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'name'=> new external_value(PARAM_TEXT, 'name'),
                                        'basepath'=> new external_value(PARAM_TEXT, 'basepath'),
                                        'isfile'=> new external_value(PARAM_INT, 'isfile'),
                                        'encoded'=> new external_value(PARAM_INT, 'encoded'),
                                        'backupdata'=> new external_value(PARAM_RAW, 'backup data')       
                                )),'backup information', VALUE_OPTIONAL)
                        )
                    ), 'backup to restore'
                ),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'targetcourseid' => new external_value(PARAM_INT, 'target course id'),
                'includeuser' => new external_value(PARAM_INT, 'include user')
                )
        );      
    }

    public static function restore_course($backups, $courseid, $targetcourseid, $includeuser)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');       
        
        $rootdir = $CFG->tempdir . '/backup/';
        mkdir($rootdir . "backup_course_" . $courseid, 0700);        

        // $backupdir = $CFG->backuptempdir . "/backup_course_" . $courseid;
        // mkdir($backupdir, 0700);

        foreach ($backups as $backup) {
            foreach ($backup['backupdata'] as $backupdata) {
                $path = $rootdir . "/" . $backupdata['basepath']. "/" . $backupdata['name'];
                if($backupdata['isfile'] == 0)
                {
                    mkdir($path, 0700);
                }
                else
                {
                    $data = $backupdata['backupdata'];
                    if($backupdata['encoded'] == 1)
                    {
                        $data = base64_decode($data);
                    }
                    $fp = fopen($path,"wb");
                    fwrite($fp,$data);
                    fclose($fp);
                }
            }
        }

        // // $backupPath =  $CFG->tempdir . "/backup/backup_course_" . $courseid;

        // // if(!empty($backupPath) && is_dir($backupPath) ){
        // //     $dir  = new RecursiveDirectoryIterator($backupPath, RecursiveDirectoryIterator::SKIP_DOTS); //upper dirs are not included,otherwise DISASTER HAPPENS :)
        // //     $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);

        // //     foreach($files as $filename) {
        // //         //$xml=readfile($filename);
        // //         $backupresult = array();
        // //         $backupresult["name"] = basename($filename);
        // //         $dirname = dirname($filename);
        // //         $backupresult["basepath"] = basename($dirname);

        // //         if (is_file($filename))
        // //         {
        // //             $backupresult["isfile"] = 1;
        // //         }
        // //         else
        // //         {
        // //             $backupresult["isfile"] = 0;
        // //         }

        // //         if (strpos($dirname,"files") !== false) {
        // //             $backupresult["backupdata"] = base64_encode(file_get_contents($filename, FILE_TEXT));
        // //             $backupresult["encoded"] = 1;
        // //         }
        // //         else
        // //         {
        // //             $backupresult["backupdata"] = file_get_contents($filename, FILE_TEXT);
        // //             $backupresult["encoded"] = 0;
        // //         }

        // //         $backupresults[] = $backupresult;
        // //     }            
        // //     foreach ($files as $f) {if (is_file($f)) {unlink($f);} else {$empty_dirs[] = $f;} } if (!empty($empty_dirs)) {foreach ($empty_dirs as $eachDir) {rmdir($eachDir);}} rmdir($backupPath);
        // // }
        $admin = get_admin();
        $rc = new restore_controller("backup_course_" . $courseid, $targetcourseid,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id,
        backup::TARGET_NEW_COURSE);

        // if (!$rc->execute_precheck()) {
        //     $results = $rc->get_precheck_results();
        //     if (!empty($results['errors'])) {
        //         fulldelete($rootdir . "backup_course_" . $courseid);
        //         return "Error during restore";
        //     }
        // }
        restore_dbops::delete_course_content($targetcourseid);

        $rc->set_status(backup::STATUS_AWAITING);
        $rc->get_plan()->get_setting('users')->set_value($includeuser);
        $rc->execute_plan();
        $rc->destroy();

        return $rootdir;
    }

    public static function restore_course_returns()
    {
        return new external_value(PARAM_RAW, 'Restore status');  
    } 

    public static function Get_Plugin_Version_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'course id')                
                )
        );   
    }

    public static function Get_Plugin_Version($id)
    {
        //validate parameter
        $params = self::validate_parameters(
            self::Get_Plugin_Version_parameters(),
            array('id' => $id)
        );

        $result = array();
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        return $result;    
    }

    public static function Get_Plugin_Version_returns()
    {
        return new external_single_structure(
            array(
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    } 
}