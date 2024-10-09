<?php

/**
 * External Web Service Template
 *
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_role_functions extends external_api {

    public static function create_role_parameters()
    {
        return new external_function_parameters(
            array(
                'name' => new external_value(PARAM_TEXT, 'role name'),
                'shortname' => new external_value(PARAM_TEXT, 'short name'),
                'description' => new external_value(PARAM_TEXT, 'description'),
                'roletype' => new external_value(PARAM_TEXT, 'role type')
                )
        );       
    }

    public static function create_role($name,$shortname,$description,$roletype)
    {
        global $CFG, $USER, $DB; 
        //$syscontext = context_system::instance();

        //require_once($CFG->dirroot . "/lib/accesslib.php");
        $returnid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_role_parameters(),
                array('name' => $name,'shortname' => $shortname,'description' => $description,
                'roletype' => $roletype)
            );

            $name = $params['name'];
            $shortname = $params['shortname'];
            $description = $params['description'];
            $roletype = $params['roletype'];
            $userid = empty($USER->id) ? 0 : $USER->id;
            $roleid = create_role($name,$shortname,$description,$roletype);
            $returnid = $roleid;
            iic_service_role_functions::set_role_contextlevels($roleid, array(CONTEXT_COURSE, CONTEXT_MODULE));
            // $contextid = $syscontext->id;
            // iic_service_role_functions::assign_role_capability('moodle/competency:competencymanage', CAP_ALLOW, $roleid, $contextid, $userid);
            // iic_service_role_functions::assign_role_capability('moodle/competency:competencyview', CAP_ALLOW, $roleid, $contextid, $userid);
            // iic_service_role_functions::assign_role_capability('moodle/course:create', CAP_ALLOW, $roleid, $contextid, $userid);
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

    public static function set_role_contextlevels($roleid, array $contextlevels) {        
        global $DB;
        $DB->delete_records('role_context_levels', array('roleid' => $roleid));
        $rcl = new stdClass();
        $rcl->roleid = $roleid;
        $contextlevels = array_unique($contextlevels);
        foreach ($contextlevels as $level) {
            $rcl->contextlevel = $level;
            $DB->insert_record('role_context_levels', $rcl, false, true);
        }
    }

    public static function create_role_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }

    public static function delete_role_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'id')
                )
        );       
    }

    public static function delete_role($id)
    {
        global $CFG, $USER, $DB; 
        $returnid = 1;
        $error = "";
        try{
            //validate parameter
            $params = self::validate_parameters(
                self::delete_role_parameters(),
                array('id' => $id)
            );

            $id = $params['id'];
            if(delete_role($id))
            {
                $returnid = 1;
            }
            else
            {
                $returnid = 0;
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

    public static function delete_role_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }

    public static function add_role_capabilities_parameters()
    {
        return new external_function_parameters(
            array(
                'capabilities' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'capabilitydata' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'name'=> new external_value(PARAM_TEXT, 'name')                                        
                                )),'capability information', VALUE_OPTIONAL)
                        )
                    ), 'capabilities'
                ),
                'roleid' => new external_value(PARAM_INT, 'role id')                
            )
        );          
    }

    public static function add_role_capabilities($capabilities,$roleid)
    {
        global $CFG, $USER, $DB; 

        $returnid = 1;
        $error = "";

        try{

            $syscontext = context_system::instance();

            $userid = empty($USER->id) ? 0 : $USER->id;
            $contextid = $syscontext->id;
            foreach ($capabilities as $capability) {
                foreach ($capability['capabilitydata'] as $capabilitydata) {
                    try
                    {
                        iic_service_role_functions::assign_role_capability($capabilitydata['name'], CAP_ALLOW, $roleid, $contextid, $userid);
                    }
                    catch (Exception $e) {
                        return $e->getMessage();
                    } 
                }
            }
            $returnid = 1;
        }
        catch (Exception $e) {
            $returnid = 0;
            $error = $e->getMessage();
        } 

        $result = array();
        $result['id'] = $returnid;  
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        $result['error'] = $error;
        return $result;         
    }
    
    public static function assign_role_capability($capability, $permission, $roleid, $contextid, $userid) {        
        global $DB; 
        $cap = new stdClass();
        $cap->contextid    = $contextid;
        $cap->roleid       = $roleid;
        $cap->capability   = $capability;
        $cap->permission   = $permission;
        $cap->timemodified = time();
        $cap->modifierid   = $userid; 
        $DB->insert_record('role_capabilities', $cap);
    }

    public static function add_role_capabilities_returns()
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