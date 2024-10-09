<?php

/**
 * @package    iic_service
 */
//defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_backup_common_functions extends external_api {

    public function deletebackup($dir)
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
}