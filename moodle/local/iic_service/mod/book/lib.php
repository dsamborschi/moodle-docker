<?php

/**
 * @package    iic_service
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_book_functions extends external_api {

    public static function get_book_parameters()
    {
        return new external_function_parameters(
            array(                
                'bookid' => new external_value(PARAM_INT, 'book id')
                )
        );       
    }

    public static function get_book($bookid)
    {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_book_parameters(),
            array('bookid' => $bookid)
        );

        $bookid = $params['bookid'];

        $books = $DB->get_records('book', array('id' => $bookid));

        $qresults = array();

        foreach($books as $key => $book) {

            $bookdata = array();
            
            $bookdata['id'] = $book->id;
            $bookdata['courseid'] = $book->course;
            $bookdata['name'] = $book->name;
            $bookdata['intro'] = $book->intro;
            $bookdata['introformat'] = $book->introformat;
            $bookdata['numbering'] = $book->numbering;
            $bookdata['navstyle'] = $book->navstyle;
            $bookdata['customtitles'] = $book->customtitles;
            $bookdata['revision'] = $book->revision;

            $chapters = $DB->get_records('book_chapters', array('bookid' => $bookid));

            $chapterdata = array();
            foreach($chapters as $chapter) {                
                $chapterdata[] = $chapter;                
            }
            $bookdata['chapter'] = $chapterdata;
            $qresults[] = $bookdata;
        }

        return $qresults;
    }

    public static function get_book_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'book id'),
                                'name'=> new external_value(PARAM_TEXT, 'book name'),
                                'courseid'=> new external_value(PARAM_INT, 'course id'),                        
                                'intro'=> new external_value(PARAM_TEXT, 'intro text'),
                                'introformat'=> new external_value(PARAM_INT, 'intro format'),
                                'numbering'=> new external_value(PARAM_INT, 'numbering'),
                                'navstyle'=> new external_value(PARAM_INT, 'navigation style'),
                                'customtitles'=> new external_value(PARAM_INT, 'custom titles'),
                                'revision'=> new external_value(PARAM_INT, 'revision'),                       
                                'chapter' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                                        'id'=> new external_value(PARAM_INT, 'chapter id'),
                                                        'pagenum'=> new external_value(PARAM_INT, 'page number'),
                                                        'subchapter'=> new external_value(PARAM_INT, 'sub chapter'),                        
                                                        'title'=> new external_value(PARAM_TEXT, 'book title'),   
                                                        'content'=> new external_value(PARAM_RAW, 'chapter content'),                                                 
                                                        'contentformat'=> new external_value(PARAM_INT, 'content format'),                                                 
                                                        'hidden'=> new external_value(PARAM_INT, 'hidden'),                                                 
                                                        'importsrc'=> new external_value(PARAM_TEXT, 'import source')
                                            )
                                        )
                                    )                    
                                )           
            ));
    }

    public static function get_booksfromcourse_parameters()
    {
        return new external_function_parameters(
            array(                
                'courseid' => new external_value(PARAM_INT, 'course id')
                )
        );       
    }

    public static function get_booksfromcourse($courseid)
    {
        global $CFG, $DB;

        $params = self::validate_parameters(
            self::get_booksfromcourse_parameters(),
            array('courseid' => $courseid)
        );

        $courseid = $params['courseid'];

        $books = $DB->get_records('book', array('course' => $courseid));

        $qresults = array();

        foreach($books as $key => $book) {

            $bookdata = array();
            
            $bookdata['id'] = $book->id;
            $bookdata['courseid'] = $book->course;
            $bookdata['name'] = $book->name;
            $bookdata['intro'] = $book->intro;
            $bookdata['introformat'] = $book->introformat;
            $bookdata['numbering'] = $book->numbering;
            $bookdata['navstyle'] = $book->navstyle;
            $bookdata['customtitles'] = $book->customtitles;
            $bookdata['revision'] = $book->revision;           
            $qresults[] = $bookdata;
        }

        return $qresults;
    }

    public static function get_booksfromcourse_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'book id'),
                                'name'=> new external_value(PARAM_TEXT, 'book name'),
                                'courseid'=> new external_value(PARAM_INT, 'course id'),                        
                                'intro'=> new external_value(PARAM_TEXT, 'intro text'),
                                'introformat'=> new external_value(PARAM_INT, 'intro format'),
                                'numbering'=> new external_value(PARAM_INT, 'numbering'),
                                'navstyle'=> new external_value(PARAM_INT, 'navigation style'),
                                'customtitles'=> new external_value(PARAM_INT, 'custom titles'),
                                'revision'=> new external_value(PARAM_INT, 'revision')                                             
                                )           
            ));
    }

    public static function create_book_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'book name')
                )
        );       
    }

    public static function create_book($courseid,$name)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_book_parameters(),
                array('courseid' => $courseid,'name' => $name)
            );

            $course = $DB->get_record('course', array('id' =>  $params['courseid']), '*', MUST_EXIST);        
        
            $newresource = new stdClass();
            $newresource->name = $name;
            $newresource->modulename =  'book';
            $newresource->course = $course->id;
            $newresource->section = 0;   
            $newresource->visible = true;
            $newresource->visibleoncoursepage = true;
            $newresource->groupingid = 0;
            $newresource->completion = 1;

            $newresource->introeditor = array('text' => 'This is your assignment', 'format' => FORMAT_HTML);

            $newresource->intro = "This is your " . $name;
            $newresource->introformat = true;
            $newresource->alwaysshowdescription = true;
            $newresource->numbering = 1;
            $newresource->navstyle = 1;
            $newresource->customtitles = 0;
            $newresource->revision = 1;       
            
            $savedcoursemodule = create_module($newresource);

            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedcoursemodule->instance, 'course' => $course->id,'module' => 3));
            
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

    public static function create_book_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version')
            )
        );
    }


    public static function create_bookchapter_parameters()
    {
        return new external_function_parameters(
            array(
                'bookid' => new external_value(PARAM_INT, 'book id'),
                'name' => new external_value(PARAM_TEXT, 'chapter name'),
                'content' => new external_value(PARAM_TEXT, 'content')
                )
        );       
    }

    public static function create_bookchapter($bookid,$name,$content)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");

        $moduleid = 123;
        $error = "";

        try{
            //validate parameter
            $params = self::validate_parameters(
                self::create_bookchapter_parameters(),
                array('bookid' => $bookid,'name' => $name,'content' => $content)
            );
        
            $newchapter = new stdClass();

            $newchapter->bookid = $bookid;
            $newchapter->title = "Chapter {$name}";
            $newchapter->pagenum = 1;
            $newchapter->subchapter = 0;
            $newchapter->hidden = 0;
            $newchapter->importsrc = '';
            $newchapter->content = $content;
            $newchapter->contentformat = FORMAT_MOODLE;
            $newchapter->timecreated = time();
            $newchapter->timemodified = time();

            // Make room for new page.
            $sql = "UPDATE {book_chapters}
            SET pagenum = pagenum + 1
            WHERE bookid = ? AND pagenum >= ?";
            $DB->execute($sql, array($newchapter->bookid, $newchapter->pagenum));
            $newchapter->id = $DB->insert_record('book_chapters', $newchapter);

            $sql = "UPDATE {book}
            SET revision = revision + 1
            WHERE id = ?";
            $DB->execute($sql, array($newchapter->bookid));

            $moduleid =  $newchapter->id;
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

    public static function create_bookchapter_returns()
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