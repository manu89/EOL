<?php
/**
 * File: UserController.php
 * User: Masterplan
 * Date: 4/19/13
 * Time: 10:04 AM
 * Desc: Controller for all Admin's operations
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
               //, $user;

        //$user->role = 'a';
       // $_SESSION['user'] = serialize($user);

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();
    }

    /**
     *  @name   actionIndex
     *  @descr  Shows report index page
     */
    private function actionAoreport(){
        global $engine;
               //, $user;

        //$user->role = 'a';
        //$_SESSION['user'] = serialize($user);

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
     *  @name   actionShowpartecipant
     *  @descr  Shows partecipant div
     */
    private function actionShowpartecipant(){
        global $engine;
        //, $user;

        //$user->role = 'a';
        //$_SESSION['user'] = serialize($user);

        $engine->loadLibs();
        $engine->renderPage();
    }

    /**
     *  @name   actionShowstudent
     *  @descr  Shows report index page
     */
    private function actionShowstudent(){
        global $engine;

        $db=new sqlDB();

        //if(!($db->qShowStudent($_REQUEST['exams']))){
        if(!($db->qShowStudent())){
            echo "errore query";
        }
    }

    /**
     *  @name   actionAddstudent
     *  @descr  Shows report index page
     */
    private function actionAddstudent(){
        global $engine;

        $db=new sqlDB();
        $userid=$_POST['iduser'];
        $exams=$array=json_decode($_POST['exams']);
        if(!($db->qAddStudent($userid,$exams))){
            echo "errore query";
        }
    }

    /**
     *  @name   actionIndex
     *  @descr  Shows report index page
     */
    private function actionAoreporttemplate(){
        global $engine;
        //, $user;

        //$user->role = 'a';
        //$_SESSION['user'] = serialize($user);

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
                'actions' => array('Index', 'Aoreport','Showassesments','Showpartecipant',
                    'Showstudent','Addstudent','Aoreporttemplate'),
                'roles'   => array('a','e'),
            ),
            array(
                'deny',
                'actions' => array('*'),
                'roles'   => array('*'),
            ),
        );
    }
}
