<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_user_functions extends external_api {

    public static function get_user_from_username_parameters()
    {
        return new external_function_parameters(
            array(
                'name' => new external_value(PARAM_RAW, 'user name'),
                )
        );       
    }

    public static function get_user_from_username($name)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/user/lib.php");
        $returnid = 123;
        $error = "";

        try{
            $paramtype = core_user::get_property_type('username');
            $cleanedvalues = array();
            $cleanedvalue = clean_param($name, $paramtype);
            $cleanedvalues[] = $cleanedvalue;

            $users = $DB->get_records_list('user', 'username', $cleanedvalues, 'id');
        
            $returnid = 0;
            foreach ($users as $user) {
                returnid = $user -> id;
                break;
            }
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

    public static function get_user_from_username_returns()
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