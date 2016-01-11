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
        $_SESSION['CRexam']=$_POST['CRexam'];
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
     *  @name   actionShowtestscreport
     *  @descr  Shows all the test for creport
     */
    private function actionShowtestscreport(){
        $db=new sqlDB();

        if(!($db->qShowTestsCreport($_SESSION['CRuser'],$_SESSION['CRexam'],$_SESSION['CRminscore'],$_SESSION['CRmaxscore'],$_SESSION['CRdatein'],$_SESSION['CRdatefn']))){
            echo "errore query caricamento test";
        }

    }

    /**
     *  @name   actionLoadcreportresult
     *  @descr  Load parameters for specific Test
     */
    private function actionLoadcreportresult(){
        global $engine;
        $_SESSION['CRdateTaken']=$_POST['dateTaken'];
        $_SESSION['CRscoreFinal']=$_POST['scoreFinal'];
        $_SESSION['CRstatus']=$_POST['status'];
        $_SESSION['CRidTest']=$_POST['idTest'];
    }

    /**
     *  @name   actionCreportpdf
     *  @descr  Shows the report
     */
    private function actionCreportpdf(){
        global $config,$user;
        include($config['systemPhpGraphLibDir'].'phpgraphlib.php');
        include($config['systemFpdfDir'].'fpdf.php');
        $db=new sqlDB();
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B',22);
        $pdf->Image("themes/default/images/eol.png");
        $pdf->Cell(0,15,ttReportCoaching,1,1,'C',false);
        $pdf->Cell(0,5,"",0,1);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(30,10,ttReportPartecipant,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(70,10,$db->qLoadStudent($_SESSION['CRuser']),"B",0);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttStudentDetailCreport,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(45,10,"User_".$_SESSION['CRuser'],"B",1);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(30,10,ttGroup,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(70,10,$db->qLoadGroup($_SESSION['CRuser']),"B",0);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttStatus,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(45,10,$_SESSION['CRstatus'],"B",1);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttReportAssesmentName,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(60,10,$_SESSION['CRexam'],"B",0);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttScoreFinal,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(45,10,$_SESSION['CRscoreFinal'],"B",1);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttTimeUsed,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(60,10,$db->qLoadTimeUsed($_SESSION['CRidTest']),"B",0);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttTimeLimit,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(45,10,$db->qLoadTestTimeLimit($_SESSION['CRidTest']),"B",1);
        $pdf->SetFont('Helvetica','B',12);
        $pdf->Cell(40,10,ttReportDateTaken,"B",0);
        $pdf->SetFont('Helvetica','',12);
        $pdf->Cell(45,10,$_SESSION['CRdateTaken'],"B",0);
        $pdf->Cell(100,10,"","B",1);
        $pdf->Cell(0,5,"",0,1);
        $pdf->SetFont('Helvetica','B',16);
        $num=$db->qLoadTestNumQuestions($_SESSION['CRidTest']);
        $pdf->Cell(0,10,ttQuestions." - ".ttQuestionsPresented.": ".$num['qpresented'].", ".ttQuestionsAnswered.": ".$num['qanswered'],0,1);
        $pdf->Cell(0,3,"",0,1);
        $questions=$db->qLoadTestQuestions($_SESSION['CRidTest']);
        $i=1;
        //select lang to load for question & answer
        $langs=get_required_files();
        foreach($langs as $lang){
            if(strpos($lang,"it/lang.php")){$idLang=2;}
            if(strpos($lang,"en/lang.php")){$idLang=1;}
        }
        $d=0;
        foreach($questions as $question){
            $details=$db->qShowQuestionsDetails($_SESSION['CRidTest'],$idLang,$question);
            if ($i==2){
                $pdf->Addpage();
                $d=0;
            }else{
                if(($d % 3==0) && ($d!=0)){
                    $pdf->AddPage();
                    $d=0;
                }
            }
            $pdf->SetFont('Helvetica','B',20);
            $pdf->Cell(10,10,$i,1,0,"C");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->SetTextColor(255,0,0);
            $pdf->Cell(170,10,$details['questionText'],1,0);
            if ($details['score']>0){
                if ($details['maxScore']==$details['score']){
                    $pdf->Image($config['themeImagesDir'].'done.png',null,null,10);
                }
                else{
                    $pdf->Image($config['themeImagesDir'].'Inactive.png',null,null,10);
                }
            }else{
                $pdf->Image($config['themeImagesDir'].'False.png',null,null,10);

            }
            $pdf->Cell(0,3,"",0,1);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttReportQuestionType,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            switch ($details['qtype']) {
                case "MC":
                    $pdf->Cell(50,10,ttQTMC,0,1,"");
                    break;
                case "MR":
                    $pdf->Cell(50,10,ttQTMR,0,1,"");
                    break;
                case "YN":
                    $pdf->Cell(50,10,ttQTYN,0,1,"");
                    break;
                case "TF":
                    $pdf->Cell(50,10,ttQTTF,0,1,"");
                    break;
                case "ES":
                    $pdf->Cell(50,10,ttQTES,0,1,"");
                    break;
                case "NM":
                    $pdf->Cell(50,10,ttQTNM,0,1,"");
                case "TM":
                    $pdf->Cell(50,10,ttQTTM,0,1,"");
                    break;
            }
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttTopic,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['qtopic'],0,1,"");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttDifficulty,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['difficulty'],0,1,"");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttScore,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['score'],0,1,"");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttScoreMax,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['maxScore'],0,1,"");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttAnswerNum,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['answerNum'],0,1,"");
            $pdf->SetFont('Helvetica','B',12);
            $pdf->Cell(80,10,ttAnswer,0,0,"");
            $pdf->SetFont('Helvetica','',12);
            $pdf->Cell(50,10,$details['answerText'],0,1,"");

            $i++;//questions counter
            $d++;
        }
        $t=time();
        //creo la cartella Creport se non esiste
        $dir = $config['systemViewsDir']."Report/generated_report/Creport";
        if (file_exists($dir)==false){
            mkdir($config['systemViewsDir']."Report/generated_report/Creport");
        }
        //creo la cartella dell'examiner se non esiste
        $dir = $config['systemViewsDir']."Report/generated_report/Creport/".$user->surname."_".$user->name;
        if (file_exists($dir)==false){
            mkdir($config['systemViewsDir']."Report/generated_report/Creport/".$user->surname."_".$user->name);
        }
        $pdf->Output($config['systemViewsDir']."Report/generated_report/Creport/".$user->surname."_".$user->name."/Creport_".$user->surname."_".$user->name."_".date("d-m-Y_H:i:s",$t).".pdf","F");
        $pdf->Output();


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
                    'Showstudentcreport','Creportparameters','Creportlist',
                    'Showtestscreport','Loadcreportresult','Creportpdf'),
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
