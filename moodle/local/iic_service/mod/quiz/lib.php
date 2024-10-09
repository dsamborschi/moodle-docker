<?php

/**
  * @package    iic_service
  */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/local/iic_service/IICConstants.php');       

class iic_service_quiz_functions extends external_api {

    public static function create_quiz_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'quiz name'),
                'catid' => new external_value(PARAM_TEXT, 'quiz category id'),
                'sectionid' => new external_value(PARAM_TEXT, 'section Id'),
                'timelimit' => new external_value(PARAM_TEXT, 'time limit'),
                'opentime' => new external_value(PARAM_INT, 'open time'),
                'closetime' => new external_value(PARAM_INT, 'close time'),
                'activityid' => new external_value(PARAM_INT, 'activity id'),
                'grade' => new external_value(PARAM_INT, 'grade')
            )
        );       
    }

    public static function create_quiz($courseid,$name,$catid,$sectionid,$timelimit,$opentime,$closetime,$activityid,$grade)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");
        $moduleid = 123;
        $error = "";

        try{
            $params = self::validate_parameters(
                self::create_quiz_parameters(),
                array('courseid' => $courseid,'name' => $name,'catid' => $catid,'sectionid' => $sectionid,
                'timelimit' => $timelimit, 'opentime' => $opentime, 'closetime' => $closetime,'activityid' => $activityid,'grade' => $grade)
            );
            
            $courseid = $params['courseid'];
            $qname  = $params['name'];
            $catid = $params['catid'];
            $sectionid = $params['sectionid'];
            $timelimit = $params['timelimit'];
            $opentime = $params['opentime'];
            $closetime = $params['closetime'];

            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            //create an object with all of the neccesary information to build a quiz
            $myQuiz = new stdClass();
            $myQuiz->modulename = 'quiz';
            $myQuiz->name = $qname;
            $myQuiz->introformat = 0;
            $myQuiz->quizpassword = '';
            $myQuiz->course = $course->id;
            $myQuiz->section = $sectionid;
            $myQuiz->timeopen = $opentime;
            $myQuiz->timeclose = $closetime;
            $myQuiz->timelimit = $timelimit;
            $myQuiz->grade = $grade;
            $myQuiz->sumgrades = 10;
            $myQuiz->gradeperiod = 0;
            $myQuiz->attempts = 1;
            $myQuiz->preferredbehaviour = 'deferredfeedback';
            $myQuiz->attemptonlast = 0;
            $myQuiz->shufflequestions = 0;
            $myQuiz->grademethod = 1;
            $myQuiz->questiondecimalpoints = 2;
            $myQuiz->visible = 1;
            $myQuiz->questionsperpage = 1;
            $myQuiz->introeditor = array('text' => 'New quiz', 'format' => 1);

            //all of the review options
            $myQuiz->attemptduring = 1;
            $myQuiz->correctnessduring = 1;
            $myQuiz->marksduring = 1;
            $myQuiz->specificfeedbackduring = 1;
            $myQuiz->generalfeedbackduring = 1;
            $myQuiz->rightanswerduring = 1;
            $myQuiz->overallfeedbackduring = 1;

            $myQuiz->attemptimmediately = 1;
            $myQuiz->correctnessimmediately = 1;
            $myQuiz->marksimmediately = 1;
            $myQuiz->specificfeedbackimmediately = 1;
            $myQuiz->generalfeedbackimmediately = 1;
            $myQuiz->rightanswerimmediately = 1;
            $myQuiz->overallfeedbackimmediately = 1;

            $myQuiz->marksopen = 1;

            $myQuiz->attemptclosed = 1;
            $myQuiz->correctnessclosed = 1;
            $myQuiz->marksclosed = 1;
            $myQuiz->specificfeedbackclosed = 1;
            $myQuiz->generalfeedbackclosed = 1;
            $myQuiz->rightanswerclosed = 1;
            $myQuiz->overallfeedbackclosed = 1;

            //actually make the quiz using the function from course/lib.php

            $savedQuiz = create_module($myQuiz);


            //get the last added random short answer matching question (which will likely be the one we just added)
            $questions = $DB->get_records('question', array('category' => $catid));

            //add the quiz question
            foreach($questions as $question) {
                quiz_add_quiz_question($question->id, $savedQuiz, $page = 0, $maxmark = null);
            }
            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedQuiz->instance, 'course' => $course->id,'module' => $activityid));
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

    public static function create_quiz_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }

    public static function create_quiz_and_questions_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'quiz name'),
                'originalquizid' => new external_value(PARAM_INT, 'id of the quiz to restore questions from'),
                'sectionid' => new external_value(PARAM_TEXT, 'section Id'),
                'timelimit' => new external_value(PARAM_TEXT, 'time limit'),
                'opentime' => new external_value(PARAM_INT, 'open time'),
                'closetime' => new external_value(PARAM_INT, 'close time'),
                'activityid' => new external_value(PARAM_INT, 'activity id'),
                'grade' => new external_value(PARAM_INT, 'grade')
            )
        );       
    }

    public static function create_quiz_and_questions($courseid,$name,$originalquizid,$sectionid,$timelimit,$opentime,$closetime,$activityid,$grade)
    {
        global $CFG, $DB;

        require_once($CFG->dirroot . "/course/lib.php");
        $moduleid = 0;
        $error = "";

        try{
            $params = self::validate_parameters(
                self::create_quiz_and_questions_parameters(),
                array('courseid' => $courseid,'name' => $name,'originalquizid' => $originalquizid,'sectionid' => $sectionid,
                'timelimit' => $timelimit, 'opentime' => $opentime, 'closetime' => $closetime,'activityid' => $activityid,'grade' => $grade)
            );
            
            $courseid = $params['courseid'];
            $qname  = $params['name'];
            $originalquizid = $params['originalquizid'];
            $sectionid = $params['sectionid'];
            $timelimit = $params['timelimit'];
            $opentime = $params['opentime'];
            $closetime = $params['closetime'];

            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            //create an object with all of the neccesary information to build a quiz
            $myQuiz = new stdClass();
            $myQuiz->modulename = 'quiz';
            $myQuiz->name = $qname;
            $myQuiz->introformat = 0;
            $myQuiz->quizpassword = '';
            $myQuiz->course = $course->id;
            $myQuiz->section = $sectionid;
            $myQuiz->timeopen = $opentime;
            $myQuiz->timeclose = $closetime;
            $myQuiz->timelimit = $timelimit;
            $myQuiz->grade = $grade;
            $myQuiz->sumgrades = 10;
            $myQuiz->gradeperiod = 0;
            $myQuiz->attempts = 1;
            $myQuiz->preferredbehaviour = 'deferredfeedback';
            $myQuiz->attemptonlast = 0;
            $myQuiz->shufflequestions = 0;
            $myQuiz->grademethod = 1;
            $myQuiz->questiondecimalpoints = 2;
            $myQuiz->visible = 1;
            $myQuiz->questionsperpage = 1;
            $myQuiz->introeditor = array('text' => 'New quiz', 'format' => 1);

            //all of the review options
            $myQuiz->attemptduring = 1;
            $myQuiz->correctnessduring = 1;
            $myQuiz->marksduring = 1;
            $myQuiz->specificfeedbackduring = 1;
            $myQuiz->generalfeedbackduring = 1;
            $myQuiz->rightanswerduring = 1;
            $myQuiz->overallfeedbackduring = 1;

            $myQuiz->attemptimmediately = 1;
            $myQuiz->correctnessimmediately = 1;
            $myQuiz->marksimmediately = 1;
            $myQuiz->specificfeedbackimmediately = 1;
            $myQuiz->generalfeedbackimmediately = 1;
            $myQuiz->rightanswerimmediately = 1;
            $myQuiz->overallfeedbackimmediately = 1;

            $myQuiz->marksopen = 1;

            $myQuiz->attemptclosed = 1;
            $myQuiz->correctnessclosed = 1;
            $myQuiz->marksclosed = 1;
            $myQuiz->specificfeedbackclosed = 1;
            $myQuiz->generalfeedbackclosed = 1;
            $myQuiz->rightanswerclosed = 1;
            $myQuiz->overallfeedbackclosed = 1;

            //actually make the quiz using the function from course/lib.php

            $savedQuiz = create_module($myQuiz);


            //if original quiz id was provided, then try to restore questions from the original quiz to this new quiz.
            if($originalquizid > 0) {
                //join with quiz_slots table, to get the 'includingsubcategories', 'page', 'maxmark' fields. basically we are getting the configuration
                //of the question from its original quiz.
                $params = array();
                $params['originalquizid'] = $originalquizid;
                $questions = array();
                try{
                    $questions = question_preload_questions(null, "quizSlot.includingsubcategories, quizSlot.page, quizSlot.maxmark", "{quiz_slots} quizSlot ON q.id = quizSlot.questionid AND quizSlot.quizid = :originalquizid",
                                                            $params, "quizSlot.slot");
                }
                catch (Exception $e){
                    //in older version of moodle, quizSlot.includingsubcategories is not an available field in the database, so above query will fail.
                    $questions = question_preload_questions(null, "quizSlot.page, quizSlot.maxmark", "{quiz_slots} quizSlot ON q.id = quizSlot.questionid AND quizSlot.quizid = :originalquizid",
                                                            $params, "quizSlot.slot");
                    //populate the includingsubcategories field using the questiontext
                    foreach($questions as $question) {
                        if($question->qtype == "random") {
                            $question->includingsubcategories = !empty($question->questiontext);
                        }
                    }
                }

                //create each question in the new quiz
                foreach($questions as $currentQuestion) {
                    if($currentQuestion->qtype == "random") {
                        quiz_add_random_questions($savedQuiz, $currentQuestion->page, $currentQuestion->category, 1, $currentQuestion->includingsubcategories);
                    }
                    else {
                        quiz_add_quiz_question($currentQuestion->id, $savedQuiz, $currentQuestion->page, $currentQuestion->maxmark);
                    }
                }
            }

            $moduleid = $DB->get_field('course_modules', 'id', array('instance' => $savedQuiz->instance, 'course' => $course->id,'module' => $activityid));
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

    public static function create_quiz_and_questions_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }
    
    public static function update_quiz_parameters()
    {

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'moduleid' => new external_value(PARAM_INT, 'module id'),
                'name' => new external_value(PARAM_TEXT, 'quiz name'),
                'sequence' => new external_value(PARAM_TEXT, 'sequence'),
                'oldsequence' => new external_value(PARAM_TEXT, 'old sequence'),
                'sectionid' => new external_value(PARAM_TEXT, 'section Id'),
                'timelimit' => new external_value(PARAM_TEXT, 'time limit'),
                'opentime' => new external_value(PARAM_INT, 'open time'),
                'closetime' => new external_value(PARAM_INT, 'close time'),
                'grade' => new external_value(PARAM_INT, 'grade')
            )
        );       
    }

    public static function update_quiz($courseid,$moduleid,$name,$sectionid,
    $sequence,$oldsequence,$timelimit,$opentime,$closetime,$grade)
    {
        global $CFG, $USER, $DB; 

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . "/course/modlib.php");
        $error = "";
        try
        {
            //validate parameter
            $params = self::validate_parameters(
                self::update_quiz_parameters(),
                array('courseid' => $courseid,'moduleid' => $moduleid,'name' => $name,'sectionid' => $sectionid,'grade' => $grade,
                'sequence' => $sequence, 'oldsequence' => $oldsequence, 'timelimit' => $timelimit,'opentime' => $opentime, 'closetime' => $closetime )
            );

            $cm = $DB->get_record('course_modules', array('id' => $moduleid), 'instance,section', MUST_EXIST);

            $moduleinfo = new stdClass();
            
            $moduleinfo->id =  $cm->instance;
            $moduleinfo->name =  $name;
            $moduleinfo->timeopen = $opentime;
            $moduleinfo->timeclose = $closetime;
            $moduleinfo->timelimit = $timelimit;
            $moduleinfo->grade = $grade;

            $DB->update_record('quiz', $moduleinfo);

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

    public static function update_quiz_returns()
    {
        return new external_single_structure(
            array(
                'id'   => new external_value(PARAM_INT, 'id'),
                'error'   => new external_value(PARAM_TEXT, 'error'),
                'version'   => new external_value(PARAM_TEXT, 'version') 
            )
        );
    }    


    public static function get_questionsfromcategory_parameters()
    {
        return new external_function_parameters(
            array(                
                'catid' => new external_value(PARAM_INT, 'category id')
                )
        );       
    }

    public static function get_questionsfromcategory($catid)
    {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');

        $params = self::validate_parameters(
            self::get_questionsfromcategory_parameters(),
            array('catid' => $catid)
        );

        $catid = $params['catid'];

        $questions = $DB->get_records('question', array('category' => $catid));

        $qresults = array();
        foreach($questions as $key => $question) {

            //$qtype = question_bank::get_qtype($question->qtype, false);
            $questiondata = array();
            
            $questiondata['id'] = $question->id;
            $questiondata['name'] = $question->name;
            $questiondata['qtype'] = $question->qtype;
            $questiondata['questiontext'] = $question->questiontext;

            $answers = $DB->get_records('question_answers', array('question' => $question->id));

            $answerdata = array();
            foreach($answers as $answer) {
                // $answerdata['id'] = $answer->id;
                // $answerdata['answer'] = $answer->answer;
                // $answerdata['answerformat'] = $answer->answerformat;
                // $answerdata['fraction'] = $answer->fraction;
                $answerdata[] = $answer;                
            }
            $questiondata['answer'] = $answerdata;
            $qresults[] = $questiondata;
        }

        return $qresults;
    }

    public static function get_questionsfromcategory_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'question id'),
                                'name'=> new external_value(PARAM_TEXT, 'question name'),
                                'qtype'=> new external_value(PARAM_TEXT, 'question type'),                        
                                'questiontext'=> new external_value(PARAM_RAW, 'question text'),
                                'answer' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                                        'id'=> new external_value(PARAM_INT, 'answer id'),
                                                        'answer'=> new external_value(PARAM_RAW, 'answer'),
                                                        'answerformat'=> new external_value(PARAM_RAW, 'answer format'),                        
                                                        'fraction'=> new external_value(PARAM_RAW, 'answer fraction'),                                                 
                                            )
                                        )
                                    )                    
                                )           
            ));
    } 

    public static function get_answersforquestion_parameters()
    {
        return new external_function_parameters(
            array(                
                'qid' => new external_value(PARAM_INT, 'question id')
                )
        );       
    }

    public static function get_answersforquestion($qid)
    {
        global $CFG, $DB;
        require_once($CFG->libdir . '/questionlib.php');

        $params = self::validate_parameters(
            self::get_answersforquestion_parameters(),
            array('qid' => $qid)
        );

        $qid = $params['qid'];

        $answers = $DB->get_records('question_answers', array('question' => $qid));

        $qresults = array();
        foreach($answers as $answer) {

            //$qtype = question_bank::get_qtype($question->qtype, false);           

                $answerdata = array();
                $answerdata['id'] = $answer->id;
                $answerdata['answer'] = $answer->answer;
                $answerdata['answerformat'] = $answer->answerformat;
                $answerdata['fraction'] = $answer->fraction;           

            $qresults[] = $answerdata;
        }

        return $qresults;
    }

    public static function get_answersforquestion_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                                'id'=> new external_value(PARAM_INT, 'answer id'),
                                'answer'=> new external_value(PARAM_RAW, 'answer'),
                                'answerformat'=> new external_value(PARAM_RAW, 'answer format'),                        
                                'fraction'=> new external_value(PARAM_RAW, 'answer fraction'),                                                 
                )
            ));
    } 
}