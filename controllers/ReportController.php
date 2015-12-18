<?php
/**
 * File: ReportController.php
 * User: Masterplan
 * Date: 4/19/13
 * Time: 10:04 AM
 * Desc: Controller for Report operations
 */

class ReportController extends Controller{

    /**
     *  @name   ReportController
     *  @descr  Creates an instance of ReportController class
     */
    public function ReportController(){}

    /**
     * @name    executeAction
     * @param   $action         String      Name of requested action
     * @descr   Executes action (if exists and if user is allowed)
     */
    public function executeAction($action){
        global $user, $log;

        // If have necessary privileges execute action
        if ($this->getAccess($user, $action, $this->accessRules())) {
            $action = 'action'.$action;
            $this->$action();
            // Else, if user is not logged bring him the to login page
        }elseif($user->role == '?'){
            header('Location: index.php?page=login');
            // Otherwise: Access denied
        }else{
            Controller::error('AccessDenied');
        }
    }

    /**
     *  @name   actionIndex
     *  @descr  Shows report index page
     */
    private function actionIndex(){
        global $engine;

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();
    }

    /**
     *  @name   actionCreport
     *  @descr  Shows Creport home page
     */
    private function actionCreport(){
        global $engine;

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();
    }

    /**
     *  @name   actionShowassesments
     *  @descr  Shows report index page
     */
    private function actionShowassesments(){
        global $engine;

        $db=new sqlDB();

        if(!($db->qShowExams($_POST['letter']))){
            echo "errore query";
        }

    }

    /**
     *  @name   actionShowstudentcreport
     *  @descr  Shows report index page
     */
    private function actionShowstudentcreport(){
        global $engine;

        $db=new sqlDB();

        if(!($db->qShowStudentCreport($_POST['exam'],$_POST['minscore'],$_POST['maxscore'],$_POST['datein'],$_POST['datefn']))){
            echo "query error check the log file";
        }
    }


    /**
     *  @name   actionCreportparameters
     *  @descr  Set parameters for AOreport
     */
    private function actionCreportparameters(){

        $_SESSION['CRuser']=$_POST['CRiduser'];
        $_SESSION['CRexam']=json_decode($_POST['CRexam']);
        $_SESSION['CRminscore']=$_POST['CRminscore'];
        $_SESSION['CRmaxscore']=$_POST['CRmaxscore'];
        $_SESSION['CRdatein']=$_POST['CRdatein'];
        $_SESSION['CRdatefn']=$_POST['CRdatefn'];
    }

    /**
     *  @name   actionCreportlist
     *  @descr  Shows list of all the test done
     */
    private function actionCreportlist(){
        global $engine;

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();
    }


    /**
     * @name   accessRules
     * @descr  Returns all access rules for User controller's actions:
     *  array(
     *     array(
     *       (allow | deny),                                     Parameter
     *       'actions' => array('*' | 'act1', ['act2', ....]),   Actions
     *       'roles'   => array('*' | '?' | 'a' | 't' | 's')     User's Role
     *     ),
     *  );
     * @return array
     */
    private function accessRules(){
        return array(
            array(
                'allow',
                'actions' => array('Index', 'Creport','Showassesments',
                    'Showstudentcreport','Creportparameters','Creportlist'),
                'roles'   => array('a','e','t','at'),
            ),
            array(
                'deny',
                'actions' => array('*'),
                'roles'   => array('*'),
            ),
        );
    }
}
