<?php

/**
 * @package    iic_service
 */
//defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_backup_functions extends external_api {

    public static function backup_module_parameters()
    {
        return new external_function_parameters(
            array(
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'includeuser' => new external_value(PARAM_INT, 'include user')
                )
        );       
    }

    public static function backup_module($moduleid, $includeuser)
    {
        global $CFG, $DB;
        $pluginmanager = core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('local_iic_service');       
        $backupPath = $plugininfo->rootdir . "/backup/backup_mod_" . $moduleid;
        $admin = get_admin();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $moduleid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id); //2 in moodle
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
                    $backupresult["backupdata"] = base64_encode(file_get_contents($filename, FILE_TEXT));
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

    public static function backup_module_returns()
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

    public static function deletebackup($dir)
    { 
        $files = array_diff(scandir($dir), array('.', '..')); 

        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? deletebackup("$dir/$file") : unlink("$dir/$file"); 
        }
        rmdir($dir); 

        $backupresults = array();
        $backupresult1 = array();

       $backupresult1["name"] = "backuppath";
       //$xml=file_get_contents($filename, FILE_TEXT);
       $backupresult1["backupdata"] = $dir;
       $backupresults[] = $backupresult1;

        return $backupresults;
    } 

    public static function backup_function()
    {
        global $CFG, $DB;

        //require_once($CFG->dirroot . '/mod/label/backup/moodle2/backup_label_stepslib.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');
        require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/label/backup/moodle2/backup_label_activity_task.class.php');
        require_once($CFG->dirroot . '/mod/label/backup/moodle2/backup_label_stepslib.php');

        //$dirpath = $CFG->dirroot . '/mod/label/backup/moodle2/backup_label_stepslib.php';
        // require_once($CFG->dirroot . "/lib/datalib.php");
        // //require_once($CFG->dirroot . "/local/iic_service/mod/backup/includes.php");
        // //require_once($CFG->dirroot . '/mod/label/backup/moodle2/backup_label_stepslib.php');
        // require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        // require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
        // // require_once($CFG->dirroot . '/backup/util/xml/output/memory_xml_output.class.php');

        $module = backup_factory::get_backup_activity_task("Moodle2", $moduleid);
        // $module->define_my_steps();

        // // Check moduleid exists
        // if (!$coursemodule = get_coursemodule_from_id(false, $moduleid)) {
        //      throw new backup_task_exception('activity_task_coursemodule_not_found', $moduleid);
        // }
        // $classname = 'backup_' . $coursemodule->modname . '_activity_structure_step';
        // //$classname = 'backup_' . $coursemodule->modname . '_activity_task';
        // //$moduleclass = new $classname($coursemodule->name, $moduleid);

        // $stepClass = new $classname($coursemodule->modname . '_structure', $coursemodule->modname . '_xml');

        $r = new ReflectionMethod($module, 'define_my_steps');
        $r->setAccessible(true);
        $moduleSteps = $r->invoke($module);

        // $module = $stepClass::define_structure();
    }

    public static function working_backup_module($moduleid)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $moduleid, backup::FORMAT_MOODLE,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2);
        $bc->execute_plan();
        $result = $bc->get_results();

        $file = $result['backup_destination']; 
        $pluginmanager = core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('local_iic_service');       
        $plugindir = $plugininfo->rootdir;

        $dir = "C:\Nazir\backup";
        //$file->copy_content_to($dir);
        $fp = get_file_packer('application/vnd.moodle.backup');
        $file->extract_to_pathname($fp, $dir);
        //$contents = $file->get_content();
        return $contents;
    }

    public static function restore_module_parameters()
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
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'targetcourseid' => new external_value(PARAM_INT, 'target course id'),
                'includeuser' => new external_value(PARAM_INT, 'include user')
                )
        );      
    }

    public static function restore_module($backups, $moduleid, $targetcourseid, $includeuser)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');       

        $rootdir = $CFG->tempdir . '/backup/';
        mkdir($rootdir . "backup_mod_" . $moduleid, 0700);      

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
        $admin = get_admin();
        $rc = new restore_controller("backup_mod_" . $moduleid, $targetcourseid,
        backup::INTERACTIVE_NO, backup::MODE_GENERAL, $admin->id,
        backup::TARGET_CURRENT_ADDING);

        $rc->set_status(backup::STATUS_AWAITING);
        $rc->get_plan()->get_setting('users')->set_value($includeuser);
        $rc->execute_plan();
        $rc->destroy();

        return $rootdir;

    }

    public static function restore_module_returns()
    {
        return new external_value(PARAM_RAW, 'Restore status');
    } 
}