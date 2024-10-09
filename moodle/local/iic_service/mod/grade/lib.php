<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_grade_functions extends external_api {

    public static function add_gradecategory_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'category name'),
                'parentid' => new external_value(PARAM_INT, 'parent id'),
                'aggregation' => new external_value(PARAM_TEXT, 'aggregation for grade'),
                'weight' => new external_value(PARAM_FLOAT, 'weight of category'),
                'grademax' => new external_value(PARAM_FLOAT, 'grade max')
            )
        );
    }

    public static function add_gradecategory($courseid,$name,$parentid,$aggregation,$weight,$grademax)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_category.php");
        $moduleid = 123;
        $error = "";
        try{
            $params = self::validate_parameters(
                self::add_gradecategory_parameters(),
                array('courseid' => $courseid, 'name' => $name,  
                'parentid' => $parentid,  'aggregation' => $aggregation, 'weight' => $weight,
                'grademax' => $grademax)
            );

            // $courseid = $params['courseid'];
            // $name = $params['name'];

            if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                print_error('invalidcourseid');
            }

            $category = new stdClass();
            $category->courseid = $courseid;
            $category->fullname = $name;

            if($aggregation == "simpleweightedmean")
            {
                $category->aggregation = 11;
            }
            else if($aggregation == "weightedmean")
            {
                $category->aggregation = 10;
            }
            else if($aggregation == "lowest")
            {
                $category->aggregation = 4;
            }
            else if($aggregation == "highest")
            {
                $category->aggregation = 6;
            }
            else if($aggregation == "meanofgrades")
            {
                $category->aggregation = 0;
            }

            $category->aggregateonlygraded = 0;

            if($parentid > 0)
            {
                $category->parent = $parentid;
            }

            $gradecategory = new grade_category($category, false);
            $gradecategory->insert();

            //these fields are in the associated grade item, so fetch it and update the fields
            $createdGradeItem = $gradecategory->load_grade_item();
            $createdGradeItem->aggregationcoef = $weight;
            $createdGradeItem->grademax = $grademax;
            $createdGradeItem->update();

            $moduleid = $gradecategory->id;
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

    public static function add_gradecategory_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }


    public static function update_gradecategory_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'category name'),
                'id' => new external_value(PARAM_INT, 'id'),
                'aggregation' => new external_value(PARAM_TEXT, 'aggregation for grade'),
                'weight' => new external_value(PARAM_FLOAT, 'weight for grade category'),
                'grademax' => new external_value(PARAM_FLOAT, 'grade max')
            )
        );
    }

    public static function update_gradecategory($courseid,$name,$id,$aggregation,$weight,$grademax)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_category.php");
        $moduleid = 123;
        $error = "";
        try{
            $params = self::validate_parameters(
                self::update_gradecategory_parameters(),
                array('courseid' => $courseid, 'name' => $name,  
                'id' => $id,  'aggregation' => $aggregation, 'weight' => $weight,
                'grademax' => $grademax)
            );

            if (!$category = grade_category::fetch(array('id'=>$id, 'courseid'=>$courseid))) {
                print_error('invalidcategory');
            }

            // $courseid = $params['courseid'];
            // $name = $params['name'];

            if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                print_error('invalidcourseid');
            }

            //$category = new stdClass();
            //$category->id = $id;
            //$category->courseid = $courseid;
            $category->fullname = $name;

            if($aggregation == "simpleweightedmean")
            {
                $category->aggregation = 11;
            }
            else if($aggregation == "weightedmean")
            {
                $category->aggregation = 10;
            }
            else if($aggregation == "lowest")
            {
                $category->aggregation = 4;
            }
            else if($aggregation == "highest")
            {
                $category->aggregation = 6;
            }
            else if($aggregation == "meanofgrades")
            {
                $category->aggregation = 0;
            }

            //$category->aggregateonlygraded = 0;            
            //$category->timecreated = time();
            //$category->timemodified = time();
            //$gradecategory = new grade_category($category, false);
            $category->update();

            //the aggregationcoef/weight field is in the associated grade item, so fetch it and update the weight
            $associatedGradeItem = $category->load_grade_item();
            $associatedGradeItem->aggregationcoef = $weight;
            $associatedGradeItem->grademax = $grademax;
            $associatedGradeItem->update();

            $moduleid = $category->id;
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

    public static function update_gradecategory_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function delete_gradecategory_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'id' => new external_value(PARAM_INT, 'id'),
            )
        );
    }

    public static function delete_gradecategory($courseid,$id)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_category.php");
        $moduleid = 123;
        $error = "";
        try{
            $params = self::validate_parameters(
                self::delete_gradecategory_parameters(),
                array('courseid' => $courseid, 'id' => $id)
            );

            if (!$category = grade_category::fetch(array('id'=>$id, 'courseid'=>$courseid))) {
                print_error('invalidcategory');
            }            
            $category->delete();
            $moduleid = $id;
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

    public static function delete_gradecategory_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

	public static function add_gradeitem_parameters()
    {
        return new external_function_parameters(
            array(
                'catid' => new external_value(PARAM_INT, 'category id'),
                'name' => new external_value(PARAM_TEXT, 'item name'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'gradepass' => new external_value(PARAM_INT, 'grade pass'),
                'grademax' => new external_value(PARAM_INT, '$grade max'),
                'aggregationcoef' => new external_value(PARAM_FLOAT, 'aggregation coeficient')
            )
        );
    }

    public static function add_gradeitem($catid,$name,$courseid,$gradepass,$grademax,$aggregationcoef)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_item.php");
        $moduleid = 123;
        $error = "";
        try{
            // $params = self::validate_parameters(
            //     self::addgradeitem_parameters(),
            //     array('courseid' => $courseid, 'name' => $name)
            // );

            // $courseid = $params['courseid'];
            // $name = $params['name'];

            if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                print_error('invalidcourseid');
            }

            $gradeitem = new grade_item();
            $gradeitem->courseid = $courseid;
            $gradeitem->categoryid = $catid;
            $gradeitem->itemname = $name;
            $gradeitem->itemtype = 'manual';
            $gradeitem->itemnumber = 0;
            $gradeitem->needsupdate = false;
            $gradeitem->gradetype = GRADE_TYPE_VALUE;
            $gradeitem->gradepass = $gradepass;
            $gradeitem->grademax = $grademax;
            $gradeitem->aggregationcoef = $aggregationcoef;
            $gradeitem->iteminfo = 'Manual grade item';
            $gradeitem->timecreated = time();
            $gradeitem->timemodified = time();

            // $gradeitem->aggregationcoef = GRADE_AGGREGATE_SUM;

            $gradeitem->insert();
            $moduleid = $gradeitem->id;
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

    public static function add_gradeitem_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function update_gradeitem_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_value(PARAM_INT, 'item id'),
                'catid' => new external_value(PARAM_INT, 'category id'),
                'name' => new external_value(PARAM_TEXT, 'item name'),
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'gradepass' => new external_value(PARAM_INT, 'grade pass'),
                'grademax' => new external_value(PARAM_INT, '$grade max'),
                'aggregationcoef' => new external_value(PARAM_FLOAT, 'aggregation coeficient')
            )
        );
    }

    public static function update_gradeitem($id,$catid,$name,$courseid,$gradepass,$grademax,$aggregationcoef)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_item.php");     
        $error = "";
        try
        {
            if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                print_error('invalidcourseid');
            }

            if (!$gradeitem = grade_item::fetch(array('id'=>$id, 'courseid'=>$courseid))) {
                return 0;
            }
                    
            $gradeitem->categoryid = $catid;
            $gradeitem->itemname = $name;
            $gradeitem->gradepass = $gradepass;
            $gradeitem->grademax = $grademax;
            $gradeitem->aggregationcoef = $aggregationcoef;
            $gradeitem->update();

            $id = $gradeitem->id;
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
        $result = array();
        $result['id'] = $id;
        $result['version'] = IIC_SERVICE_PLUGIN_VERSION;
        $result['error'] = $error;
        return $result;   
    }

    public static function update_gradeitem_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

     public static function delete_gradeitem_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'id' => new external_value(PARAM_INT, 'id'),
            )
        );
    }

    public static function delete_gradeitem($courseid,$id)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_item.php");     
        $moduleid = 123;
        $error = "";
        try{
            $params = self::validate_parameters(
                self::delete_gradeitem_parameters(),
                array('courseid' => $courseid, 'id' => $id)
            );

            if (!$item = grade_item::fetch(array('id'=>$id, 'courseid'=>$courseid))) {
                print_error('invalidgradeitem');
            }            
            $item->delete();
            $moduleid = $id;
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

    public static function delete_gradeitem_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function get_gradebook_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id')
            )
        );
    }

    public static function get_gradebook($courseid)
    {
        function get_grade_tree($element)
        {            
            $object = $element['object'];
            $grade_item = $object->get_grade_item();

            if(!is_null($grade_item))
            {
                if($grade_item->itemtype == 'category')
                {
                    $category = grade_category::fetch(array('id' => $grade_item->iteminstance));
                    $grade_item->itemname = $category->fullname;
                }
                $gradeitems[] = $grade_item;
            }

            foreach($element['children'] as $child_el) {
                if(!$child_el->top_element)
                {
                    $gradeitems = array_merge($gradeitems, get_grade_tree($child_el));
                }
            }

            return $gradeitems;
        }

        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_category.php");

        $gtree = new grade_tree($courseid, false, false);
        $top_element = $gtree->top_element;

        $gradeitems = array();        

        foreach($top_element['children'] as $child_el) {
            if(!$child_el->top_element)
            {
                $gradeitems = array_merge($gradeitems, get_grade_tree($child_el));       
            }    
        }

        return $gradeitems;
    }

    public static function get_gradebook_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                        'id'=> new external_value(PARAM_INT, 'id'),
                        'categoryid'=> new external_value(PARAM_INT, 'categoryid'),
                        'itemname'=> new external_value(PARAM_TEXT, 'itemname'),
                        'itemtype'=> new external_value(PARAM_TEXT, 'itemtype'),
                        'itemmodule'=> new external_value(PARAM_TEXT, 'itemmodule'),
                        'iteminstance'=> new external_value(PARAM_TEXT, 'iteminstance'),
                        'gradetype'=> new external_value(PARAM_INT, 'gradetype'),
                        'multfactor'=> new external_value(PARAM_TEXT, 'multfactor'),
                        'plusfactor'=> new external_value(PARAM_TEXT, 'plusfactor'),
                        'aggregationcoef'=> new external_value(PARAM_TEXT, 'aggregationcoef'),
                        'aggregationcoef2'=> new external_value(PARAM_TEXT, 'aggregationcoef2'),
                        'grademin'=> new external_value(PARAM_TEXT, 'grademin'),
                        'grademax'=> new external_value(PARAM_TEXT, 'grademax'),
                        'gradepass'=> new external_value(PARAM_TEXT, 'gradepass')
                    )           
        ));        
    }

    public static function get_gradecategories_parameters()
    {
        return new external_function_parameters(
            array(                
                'courseid' => new external_value(PARAM_INT, 'course id')
                )
        );       
    }

    public static function get_gradecategories($courseid)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/grade/lib.php");
        require_once($CFG->libdir . "/grade/grade_category.php");

        $params = self::validate_parameters(
            self::get_gradecategories_parameters(),
            array('courseid' => $courseid)
        );

        $courseid = $params['courseid'];

        $categories = $DB->get_records('grade_categories', array('courseid' => $courseid));

        $gcategories = array();
        foreach($categories as $category) {

            //$qtype = question_bank::get_qtype($question->qtype, false);           

                $gdata = array();
                $gdata['id'] = $category->id;
                $gdata['fullname'] = $category->fullname;
                $gdata['aggregation'] = $category->aggregation;

            $gcategories[] = $gdata;
        }

        return $gcategories;
    }

    public static function get_gradecategories_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'answer id'),
                                'fullname'=> new external_value(PARAM_TEXT, 'fullname'),
                                'aggregation'=> new external_value(PARAM_RAW, 'aggregation'),                        
                )
            ));
    } 
}


