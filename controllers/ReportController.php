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
     *  @name   actionShowgroups
     *  @descr  Shows report index page
     */
    private function actionShowgroups(){
        global $engine;

        $db=new sqlDB();

        if(!($db->qShowGroups($_POST['letter'],$exams=json_decode($_POST['exams']),$_POST['minscore'],$_POST['maxscore'],$_POST['datein'],$_POST['datefn']))){
            echo "query error check the log file";
        }

    }

    /**
     *  @name   actionShowpartecipant
     *  @descr  Shows partecipant div
     */
    private function actionShowpartecipant(){
        global $engine;

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

        $groups=json_decode($_POST['groups']);

        if (($groups[0]!="") or ($groups[0]!=null)){
            if(!($db->qShowStudentGroup($groups,$exams=json_decode($_POST['exams']),$_POST['minscore'],$_POST['maxscore'],$_POST['datein'],$_POST['datefn']))){
                echo "query error check the log file";
            }
        }
        else{
            if(!($db->qShowStudent($exams=json_decode($_POST['exams']),$_POST['minscore'],$_POST['maxscore'],$_POST['datein'],$_POST['datefn']))){
                echo "query error check the log file";
            }
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
        if(!($db->qAddStudent($userid))){
            echo "errore query";
        }
    }

    /**
     *  @name   actionShowparticipantdetails
     *  @descr  Shows partecipant div
     */
    private function actionShowparticipantdetails(){
        global $engine;

        $engine->loadLibs();
        $engine->renderPage();
    }

    /**
     *  @name   actionPrintparticipantdetails
     *  @descr  Shows report index page
     */
    private function actionPrintparticipantdetails(){
        global $engine;

        $db=new sqlDB();
        $userid=$_POST['iduser'];
        if(!($db->qShowStudentDetails($userid))){
            echo "errore query";
        }
    }

    /**
     *  @name   actionAoreportparameters
     *  @descr  Set parameters for AOreport
     */
    private function actionAoreportparameters(){
        global $engine;

        $_SESSION['userparam']=$_POST['iduser'];
        $_SESSION['examsparam']=json_decode($_POST['exams']);
        $_SESSION['groupsparam']=json_decode($_POST['groups']);
        $_SESSION['minscoreparam']=$_POST['minscore'];
        $_SESSION['maxscoreparam']=$_POST['maxscore'];
        $_SESSION['datein']=$_POST['datein'];
        $_SESSION['datefn']=$_POST['datefn'];
    }

    /**
     *  @name   actionAoreporttemplate
     *  @descr  Shows report template for AOReport
     */
    private function actionAoreporttemplate(){
        global $engine;

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();
    }

    /**
     *  @name   actionAoreportresult
     *  @descr  Shows the report
     */
    private function actionAoreportresult(){
        global $engine,$config;

        include($config['systemPhpGraphLibDir'].'phpgraphlib.php');
        include($config['systemFpdfDir'].'fpdf.php');
        $db=new sqlDB();

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B',22);
        $pdf->Image("themes/default/images/eol.png");
        $pdf->Cell(0,20,ttAssesmentOverview,1,1,'C',false);
        $pdf->Cell(0,8,"",0,1);

        // participant is selected
        if ($_SESSION['userparam']!=""){
            $pdf->SetLeftMargin(30);
            $pdf->SetFont('Helvetica','B',13);
            $pdf->Cell(85,10,ttStudent,0,0);
            $pdf->SetFont('Helvetica','',13);
            $pdf->Cell(85,10,$db->qLoadStudent($_SESSION['userparam']),0,1);
            $pdf->SetFont('Helvetica','B',13);
            $pdf->Cell(85,10,ttStudentDetail,0,0);
            $pdf->SetFont('Helvetica','',13);
            $pdf->Cell(85,10,$_SESSION['userparam'],0,1);
            $pdf->SetLeftMargin(10);
            $pdf->Cell(0,5,"",0,1);


            if (($_SESSION['examsparam'][0]!="") or ($_SESSION['examsparam'][0]!=null)){ // case assesment are selected in the main!!
                $i=0;
                while(($_SESSION['examsparam'][$i]!="") or ($_SESSION['examsparam'][$i]!=null)){

                    if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                        if ($i>0){
                            $pdf->AddPage();
                        }
                        $pdf->SetFont('Helvetica','B',16);
                        $pdf->Cell(0,10,ttReportAssessmentInformation,1,1,'L',false);
                        $pdf->Cell(0,10,"",0,1);
                    }
                    //print assesment name
                    if (isset($_POST['assesmentName'])) {
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$_SESSION['examsparam'][$i],0,1);
                    }

                    //print assesment ID
                    if (isset($_POST['assesmentID'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentID,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentID($_SESSION['examsparam'][$i]),0,1);
                    }

                    //print assesment author
                    if (isset($_POST['assesmentAuthor'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentAuthor,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentAuthor($_SESSION['examsparam'][$i]),0,1);
                    }

                    //print assesment DATA/TIME FIRST TAKEN
                    if (isset($_POST['assesmentDateTimeFirst'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentDateTimeFirst,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentDateTimeFirstTaken($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment DATA/TIME LAST TAKEN
                    if (isset($_POST['assesmentDateTimeLast'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentDateTimeLast,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentDateTimeLastTaken($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment number of times started
                    if (isset($_POST['assesmentNumberStarted'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberStarted,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberStarted($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print exam number of times not finished
                    if (isset($_POST['assesmentNumberNotFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberNotFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberNotFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment number of times finished
                    if (isset($_POST['assesmentNumberFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment min score finished
                    if (isset($_POST['assesmentMinscoreFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMinscoreFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMinScoreFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment max score finished
                    if (isset($_POST['assesmentMaxscoreFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMaxcoreFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMaxScoreFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment medium score finished
                    if (isset($_POST['assesmentMediumFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMediumFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMedScoreFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment least time finished
                    if (isset($_POST['assesmentLeastTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentLeastTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentLeastTimeFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment most time finished
                    if (isset($_POST['assesmentMostTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMostTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMostTimeFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment medium time finished
                    if (isset($_POST['assesmentMediumTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMediumTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMediumTimeFinished($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment std deviation
                    if (isset($_POST['assesmentStdDeviation'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentStdDeviation,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentStdDeviation($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //now load all the topics relative to selected student
                    $usertopics=$db->qLoadTopicUser($_SESSION['examsparam'][$i],$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);

                    //print all statistics relative to each topics loaded before
                    foreach($usertopics as $topic){
                        if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicStdDeviation']))){
                            $pdf->SetLeftMargin(10);
                            if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                                $pdf->AddPage();
                            }
                            else{
                                if ($i>0){
                                    $pdf->AddPage(); // add a page only from second assesment analysis
                                }
                            }
                            $pdf->SetFont("Helvetica","B",16);
                            $pdf->Cell(0,10,ttReportTopicInformation,1,1,'L',false);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$topic,0,1);
                        }

                        //print topic medium score
                        if (isset($_POST['topicAverageScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicAverageScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMedScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic min score
                        if (isset($_POST['topicMinimumScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicMinimumScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMinScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic max score
                        if (isset($_POST['topicMaximumScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicMaximumScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMaxScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic std deviation
                        if (isset($_POST['topicStdDeviation'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicStandardDeviation,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicStdDeviation($topic,$_SESSION['userparam']),0,1);
                        }

                        $pdf->SetLeftMargin(10);
                    }

                    if ((isset($_POST['graphicHistogram']))or(isset($_POST['graphicTopicScore']))){
                        $pdf->SetFont("Helvetica","B",16);
                        $pdf->Cell(0,5,"",0,1);
                        $pdf->Cell(0,10,ttReportGraphicalDsiplays,1,1,'L',false);
                        $pdf->Cell(0,1,"",0,1);
                    }
                    //draw assesments Histograms if selected
                    if (isset($_POST['graphicHistogram'])) {
                        $graphdata=$db->qLoadAssesmentScores($_SESSION['examsparam'][$i], $_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                        ${'graph'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/assesmentsgraph".$i.".png");
                        ${'graph'.$i}->addData($graphdata);
                        ${'graph'.$i}->setTitle("Assesments Scores");
                        ${'graph'.$i}->setTextColor("black");
                        ${'graph'.$i}->setXValuesHorizontal(true);
                        ${'graph'.$i}->setBarColor("#6da2ff");
                        ${'graph'.$i}->createGraph();
                        $pdf->Image("../views/Report/generated_graphs/assesmentsgraph".$i.".png");

                    }

                    //draw topics Histograms if selected
                    if (isset($_POST['graphicTopicScore'])) {
                        $graphdatatopic=$db->qLoadTopicScores($usertopics, $_SESSION['examsparam'][$i], $_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                        ${'topics'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/topicsgraph".$i.".png");
                        ${'topics'.$i}->addData($graphdatatopic);
                        ${'topics'.$i}->setTitle("Topics Scores");
                        ${'topics'.$i}->setTextColor("black");
                        ${'topics'.$i}->setXValuesHorizontal(true);
                        ${'topics'.$i}->setBarColor("green");
                        ${'topics'.$i}->createGraph();
                        $pdf->Image("../views/Report/generated_graphs/topicsgraph".$i.".png");
                    }

                    $i++;//counter for assesments
                }
            }
            else{
                //case exams are not selected
                $allexams=$db->qLoadExams();
                $i=0;
                foreach($allexams as $exam){

                    if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                        if ($i>0){
                            $pdf->AddPage(); // add a page only from second assesment analysis
                        }
                        $pdf->SetFont('Helvetica','B',16);
                        $pdf->Cell(0,10,ttReportAssessmentInformation,1,1,'L',false);
                        $pdf->Cell(0,10,"",0,1);
                    }
                    //print assesment name
                    if (isset($_POST['assesmentName'])) {
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$exam,0,1);
                    }

                    //print assesment ID
                    if (isset($_POST['assesmentID'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentID,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentID($exam),0,1);
                    }

                    //print assesment author
                    if (isset($_POST['assesmentAuthor'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentAuthor,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentAuthor($exam),0,1);
                    }

                    //print assesment DATA/TIME FIRST TAKEN
                    if (isset($_POST['assesmentDateTimeFirst'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentDateTimeFirst,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentDateTimeFirstTaken($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment DATA/TIME LAST TAKEN
                    if (isset($_POST['assesmentDateTimeLast'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentDateTimeLast,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentDateTimeLastTaken($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment number of times started
                    if (isset($_POST['assesmentNumberStarted'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberStarted,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberStarted($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print exam number of times not finished
                    if (isset($_POST['assesmentNumberNotFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberNotFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberNotFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment number of times finished
                    if (isset($_POST['assesmentNumberFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentNumberFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentNumberFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment min score finished
                    if (isset($_POST['assesmentMinscoreFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMinscoreFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMinScoreFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment max score finished
                    if (isset($_POST['assesmentMaxscoreFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMaxcoreFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMaxScoreFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment medium score finished
                    if (isset($_POST['assesmentMediumFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMediumFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMedScoreFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment least time finished
                    if (isset($_POST['assesmentLeastTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentLeastTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentLeastTimeFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment most time finished
                    if (isset($_POST['assesmentMostTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMostTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMostTimeFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment medium time finished
                    if (isset($_POST['assesmentMediumTimeFinished'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentMediumTimeFinished,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentMediumTimeFinished($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //print assesment std deviation
                    if (isset($_POST['assesmentStdDeviation'])){
                        $pdf->SetFont("Helvetica","B",13);
                        $pdf->SetLeftMargin(30);
                        $pdf->Cell(85,10,ttReportAssesmentStdDeviation,0,0);
                        $pdf->SetFont("Helvetica","",13);
                        $pdf->Cell(85,10,$db->qShowAssesmentStdDeviation($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                    }

                    //now load all the topics relative to selected student
                    $usertopics=$db->qLoadTopicUser($exam,$_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);

                    //print all statistics relative to each topics loaded before
                    foreach($usertopics as $topic){
                        if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicStdDeviation']))){
                            $pdf->SetLeftMargin(10);
                            if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                                $pdf->AddPage();
                            }
                            else{
                                if ($i>0){
                                    $pdf->AddPage(); // add a page only from second assesment analysis
                                }
                            }
                            $pdf->SetFont("Helvetica","B",16);
                            $pdf->Cell(0,10,ttReportTopicInformation,1,1,'L',false);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$topic,0,1);
                        }

                        //print topic medium score
                        if (isset($_POST['topicAverageScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicAverageScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMedScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic min score
                        if (isset($_POST['topicMinimumScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicMinimumScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMinScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic max score
                        if (isset($_POST['topicMaximumScore'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicMaximumScore,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicMaxScore($topic,$_SESSION['userparam']),0,1);
                        }

                        //print topic std deviation
                        if (isset($_POST['topicStdDeviation'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportTopicStandardDeviation,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowTopicStdDeviation($topic,$_SESSION['userparam']),0,1);
                        }

                        $pdf->SetLeftMargin(10);
                    }

                    if ((isset($_POST['graphicHistogram']))or(isset($_POST['graphicTopicScore']))){
                        $pdf->SetFont("Helvetica","B",16);
                        $pdf->Cell(0,5,"",0,1);
                        $pdf->Cell(0,10,ttReportGraphicalDsiplays,1,1,'L',false);
                        $pdf->Cell(0,1,"",0,1);
                    }
                    //draw assesments Histograms if selected
                    if (isset($_POST['graphicHistogram'])) {
                        $graphdata=$db->qLoadAssesmentScores($exam, $_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                        ${'graph'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/assesmentsgraph".$i.".png");
                        ${'graph'.$i}->addData($graphdata);
                        ${'graph'.$i}->setTitle("Assesments Scores");
                        ${'graph'.$i}->setTextColor("black");
                        ${'graph'.$i}->setXValuesHorizontal(true);
                        ${'graph'.$i}->setBarColor("#6da2ff");
                        ${'graph'.$i}->createGraph();
                        $pdf->Image("../views/Report/generated_graphs/assesmentsgraph".$i.".png");

                    }

                    //draw topics Histograms if selected
                    if (isset($_POST['graphicTopicScore'])) {
                        $graphdatatopic=$db->qLoadTopicScores($usertopics, $exam, $_SESSION['userparam'],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                        ${'topics'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/topicsgraph".$i.".png");
                        ${'topics'.$i}->addData($graphdatatopic);
                        ${'topics'.$i}->setTitle("Topics Scores");
                        ${'topics'.$i}->setTextColor("black");
                        ${'topics'.$i}->setXValuesHorizontal(true);
                        ${'topics'.$i}->setBarColor("green");
                        ${'topics'.$i}->createGraph();
                        $pdf->Image("../views/Report/generated_graphs/topicsgraph".$i.".png");
                    }

                    $i++;//counter for assesments
                }
            }


        }

        //only groups are selected
        if (( ($_SESSION['groupsparam'][0]!="") or ($_SESSION['groupsparam'][0]!=null)) && ($_SESSION['userparam']=="")){ //only groups are selected
            $pdf->SetLeftMargin(50);
            $pdf->SetFont('Helvetica','B',13);
            $pdf->Cell(65,10,ttReportGroup,0,0);
            $pdf->SetFont('Helvetica','',13);
            $groupsname="";
            $i=0;
            while (($_SESSION['groupsparam'][$i]!="") or ($_SESSION['groupsparam'][$i]!=null)){//print on pdf the groups relative to report
                if ($i>0){
                    $groupsname .=", ".$_SESSION['groupsparam'][$i];
                }
                else{
                    $groupsname .=$_SESSION['groupsparam'][$i];
                }
                $i++;
            }
            $pdf->Cell(85,10,$groupsname,0,1);
            $pdf->SetLeftMargin(10);
            $pdf->Cell(0,5,"",0,1);

            if (($_SESSION['examsparam'][0]!="") or ($_SESSION['examsparam'][0]!=null)){
                //case exams selected
                $d=0;
                while (($_SESSION['groupsparam'][$d]!="") or ($_SESSION['groupsparam'][$d]!=null)){
                    $i=0;
                    while(($_SESSION['examsparam'][$i]!="") or ($_SESSION['examsparam'][$i]!=null)) {

                        if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                            if ($i > 0) {
                                $pdf->AddPage();
                            }
                            $pdf->SetFont('Helvetica', 'B', 16);
                            $pdf->Cell(0, 10, ttReportAssessmentInformation, 1, 1, 'L', false);
                            $pdf->Cell(0, 10, "", 0, 1);
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->Cell(85,10,$_SESSION['groupsparam'][$d],0,1);
                            $pdf->Cell(0,0.5,"",1,1);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->SetFont("Helvetica","B",16);
                        }

                        //print assesment name
                        if (isset($_POST['assesmentName'])) {
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$_SESSION['examsparam'][$i],0,1);
                        }

                        //print assesment ID
                        if (isset($_POST['assesmentID'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentID,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentID($_SESSION['examsparam'][$i]),0,1);
                        }

                        //print assesment author
                        if (isset($_POST['assesmentAuthor'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentAuthor,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentAuthor($_SESSION['examsparam'][$i]),0,1);
                        }

                        //print assesment DATA/TIME FIRST TAKEN
                        if (isset($_POST['assesmentDateTimeFirst'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentDateTimeFirst,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentDateTimeFirstTakenGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment DATA/TIME LAST TAKEN
                        if (isset($_POST['assesmentDateTimeLast'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentDateTimeLast,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentDateTimeLastTakenGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment number of times started
                        if (isset($_POST['assesmentNumberStarted'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberStarted,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberStartedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print exam number of times not finished
                        if (isset($_POST['assesmentNumberNotFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberNotFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberNotFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment number of times finished
                        if (isset($_POST['assesmentNumberFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment min score finished
                        if (isset($_POST['assesmentMinscoreFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMinscoreFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMinScoreFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment max score finished
                        if (isset($_POST['assesmentMaxscoreFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMaxcoreFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMaxScoreFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment medium score finished
                        if (isset($_POST['assesmentMediumFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMediumFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMedScoreFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment least time finished
                        if (isset($_POST['assesmentLeastTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentLeastTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentLeastTimeFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment most time finished
                        if (isset($_POST['assesmentMostTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMostTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMostTimeFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment medium time finished
                        if (isset($_POST['assesmentMediumTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMediumTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMediumTimeFinishedGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment std deviation
                        if (isset($_POST['assesmentStdDeviation'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentStdDeviation,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentStdDeviationGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //now load all the topics relative to selected student
                        $grouptopics=$db->qLoadTopicGroup($_SESSION['examsparam'][$i],$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);

                        //print all statistics relative to each topics loaded before
                        foreach($grouptopics as $topic){
                            if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicStdDeviation']))){
                                $pdf->SetLeftMargin(10);
                                if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                                    $pdf->AddPage();
                                }
                                else{
                                    if ($i>0){
                                        $pdf->AddPage(); // add a page only from second assesment analysis
                                    }
                                }
                                $pdf->SetFont("Helvetica","B",16);
                                $pdf->Cell(0,10,ttReportTopicInformation,1,1,'L',false);
                                $pdf->Cell(0,5,"",0,1);
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicName,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$topic,0,1);
                            }

                            //print topic medium score
                            if (isset($_POST['topicAverageScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicAverageScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMedScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic min score
                            if (isset($_POST['topicMinimumScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicMinimumScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMinScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic max score
                            if (isset($_POST['topicMaximumScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicMaximumScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMaxScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic std deviation
                            if (isset($_POST['topicStdDeviation'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicStandardDeviation,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicStdDeviationGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            $pdf->SetLeftMargin(10);
                        }

                        if ((isset($_POST['graphicHistogram']))or(isset($_POST['graphicTopicScore']))){
                            $pdf->SetFont("Helvetica","B",16);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->Cell(0,10,ttReportGraphicalDsiplays,1,1,'L',false);
                            $pdf->Cell(0,1,"",0,1);
                        }
                        //draw assesments Histograms if selected
                        if (isset($_POST['graphicHistogram'])) {
                            $graphdata=$db->qLoadAssesmentScoresGroup($_SESSION['examsparam'][$i], $_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                            ${'graph'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/assesmentsgraph".$i.".png");
                            ${'graph'.$i}->addData($graphdata);
                            ${'graph'.$i}->setTitle("Assesments Scores");
                            ${'graph'.$i}->setTextColor("black");
                            ${'graph'.$i}->setXValuesHorizontal(true);
                            ${'graph'.$i}->setBarColor("#6da2ff");
                            ${'graph'.$i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/assesmentsgraph".$i.".png");

                        }

                        //draw topics Histograms if selected
                        if (isset($_POST['graphicTopicScore'])) {
                            $graphdatatopic=$db->qLoadTopicScoresGroup($grouptopics, $_SESSION['examsparam'][$i], $_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                            ${'topics'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/topicsgraph".$i.".png");
                            ${'topics'.$i}->addData($graphdatatopic);
                            ${'topics'.$i}->setTitle("Topics Scores");
                            ${'topics'.$i}->setTextColor("black");
                            ${'topics'.$i}->setXValuesHorizontal(true);
                            ${'topics'.$i}->setBarColor("green");
                            ${'topics'.$i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/topicsgraph".$i.".png");
                        }
                        $i++;//counter of assesments
                    }
                    $d++;//counter of groups
                }

            }
            else{
                //case exams are not selected
                $allexams=$db->qLoadExams();
                $d=0;
                while (($_SESSION['groupsparam'][$d]!="") or ($_SESSION['groupsparam'][$d]!=null)){
                    $i=0;
                    foreach($allexams as $exam){
                        if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                            if ($i > 0) {
                                $pdf->AddPage();
                            }
                            $pdf->SetFont('Helvetica', 'B', 16);
                            $pdf->Cell(0, 10, ttReportAssessmentInformation, 1, 1, 'L', false);
                            $pdf->Cell(0, 10, "", 0, 1);
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->Cell(85,10,$_SESSION['groupsparam'][$d],0,1);
                            $pdf->Cell(0,0.5,"",1,1);
                            $pdf->Cell(0,5,"",0,1);
                        }

                        //print assesment name
                        if (isset($_POST['assesmentName'])) {
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$exam,0,1);
                        }

                        //print assesment ID
                        if (isset($_POST['assesmentID'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentID,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentID($exam),0,1);
                        }

                        //print assesment author
                        if (isset($_POST['assesmentAuthor'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentAuthor,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentAuthor($exam),0,1);
                        }

                        //print assesment DATA/TIME FIRST TAKEN
                        if (isset($_POST['assesmentDateTimeFirst'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentDateTimeFirst,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentDateTimeFirstTakenGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment DATA/TIME LAST TAKEN
                        if (isset($_POST['assesmentDateTimeLast'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentDateTimeLast,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentDateTimeLastTakenGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment number of times started
                        if (isset($_POST['assesmentNumberStarted'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberStarted,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberStartedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print exam number of times not finished
                        if (isset($_POST['assesmentNumberNotFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberNotFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberNotFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment number of times finished
                        if (isset($_POST['assesmentNumberFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentNumberFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentNumberFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment min score finished
                        if (isset($_POST['assesmentMinscoreFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMinscoreFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMinScoreFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment max score finished
                        if (isset($_POST['assesmentMaxscoreFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMaxcoreFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMaxScoreFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment medium score finished
                        if (isset($_POST['assesmentMediumFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMediumFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMedScoreFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment least time finished
                        if (isset($_POST['assesmentLeastTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentLeastTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentLeastTimeFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment most time finished
                        if (isset($_POST['assesmentMostTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMostTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMostTimeFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment medium time finished
                        if (isset($_POST['assesmentMediumTimeFinished'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentMediumTimeFinished,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentMediumTimeFinishedGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //print assesment std deviation
                        if (isset($_POST['assesmentStdDeviation'])){
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentStdDeviation,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$db->qShowAssesmentStdDeviationGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']),0,1);
                        }

                        //now load all the topics relative to selected student
                        $grouptopics=$db->qLoadTopicGroup($exam,$_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);

                        //print all statistics relative to each topics loaded before
                        foreach($grouptopics as $topic){
                            if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicStdDeviation']))){
                                $pdf->SetLeftMargin(10);
                                if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))){
                                    $pdf->AddPage();
                                }
                                else{
                                    if ($i>0){
                                        $pdf->AddPage(); // add a page only from second assesment analysis
                                    }
                                }
                                $pdf->SetFont("Helvetica","B",16);
                                $pdf->Cell(0,10,ttReportTopicInformation,1,1,'L',false);
                                $pdf->Cell(0,5,"",0,1);
                            }

                            //print topic medium score
                            if (isset($_POST['topicAverageScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicAverageScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMedScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic min score
                            if (isset($_POST['topicMinimumScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicMinimumScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMinScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic max score
                            if (isset($_POST['topicMaximumScore'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicMaximumScore,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicMaxScoreGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            //print topic std deviation
                            if (isset($_POST['topicStdDeviation'])){
                                $pdf->SetFont("Helvetica","B",13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85,10,ttReportTopicStandardDeviation,0,0);
                                $pdf->SetFont("Helvetica","",13);
                                $pdf->Cell(85,10,$db->qShowTopicStdDeviationGroup($topic,$_SESSION['groupsparam'][$d]),0,1);
                            }

                            $pdf->SetLeftMargin(10);
                        }

                        if ((isset($_POST['graphicHistogram']))or(isset($_POST['graphicTopicScore']))){
                            $pdf->SetFont("Helvetica","B",16);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->Cell(0,10,ttReportGraphicalDsiplays,1,1,'L',false);
                            $pdf->Cell(0,1,"",0,1);
                        }
                        //draw assesments Histograms if selected
                        if (isset($_POST['graphicHistogram'])) {
                            $graphdata=$db->qLoadAssesmentScoresGroup($exam, $_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                            ${'graph'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/assesmentsgraph".$i.".png");
                            ${'graph'.$i}->addData($graphdata);
                            ${'graph'.$i}->setTitle("Assesments Scores");
                            ${'graph'.$i}->setTextColor("black");
                            ${'graph'.$i}->setXValuesHorizontal(true);
                            ${'graph'.$i}->setBarColor("#6da2ff");
                            ${'graph'.$i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/assesmentsgraph".$i.".png");

                        }

                        //draw topics Histograms if selected
                        if (isset($_POST['graphicTopicScore'])) {
                            $graphdatatopic=$db->qLoadTopicScoresGroup($grouptopics, $exam, $_SESSION['groupsparam'][$d],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                            ${'topics'.$i} = new PHPGraphLib(500,350, "../views/Report/generated_graphs/topicsgraph".$i.".png");
                            ${'topics'.$i}->addData($graphdatatopic);
                            ${'topics'.$i}->setTitle("Topics Scores");
                            ${'topics'.$i}->setTextColor("black");
                            ${'topics'.$i}->setXValuesHorizontal(true);
                            ${'topics'.$i}->setBarColor("green");
                            ${'topics'.$i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/topicsgraph".$i.".png");
                        }

                        $i++; //counter of assesments
                    }
                    $d++;//counter of groups
                }
            }

        }

        //report for all participants
        if (($_SESSION['userparam']=="") && ($_SESSION['groupsparam'][0]=="") && ($_SESSION['groupsparam'][0]==null)) {

            //case selected exams
            if (($_SESSION['examsparam'][0] != "") or ($_SESSION['examsparam'][0] != null)) {
                    $i = 0;
                    while (($_SESSION['examsparam'][$i] != "") or ($_SESSION['examsparam'][$i] != null)) {
                        $students = $db->qLoadAllStudent($_SESSION['examsparam'][$i],$_SESSION['minscoreparam'],$_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']);
                        foreach ($students as $student) {

                        if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                            if ($i > 0) {
                                $pdf->AddPage();
                            }
                            $pdf->SetFont('Helvetica', 'B', 16);
                            $pdf->Cell(0, 10, ttReportAssessmentInformation, 1, 1, 'L', false);
                            $pdf->Cell(0, 10, "", 0, 1);
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->Cell(85, 10, $db->qLoadStudent($student), 0, 0);
                            $pdf->Cell(70,10,"ID: User_".$student,0,1);
                            $pdf->Cell(0,0.5,"",1,1);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->SetFont("Helvetica", "B", 16);
                        }

                        //print assesment name
                        if (isset($_POST['assesmentName'])) {
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$_SESSION['examsparam'][$i],0,1);
                        }

                        //print assesment ID
                        if (isset($_POST['assesmentID'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentID, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentID($_SESSION['examsparam'][$i]), 0, 1);
                        }

                        //print assesment author
                        if (isset($_POST['assesmentAuthor'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentAuthor, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentAuthor($_SESSION['examsparam'][$i]), 0, 1);
                        }

                        //print assesment DATA/TIME FIRST TAKEN
                        if (isset($_POST['assesmentDateTimeFirst'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentDateTimeFirst, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentDateTimeFirstTaken($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'],$_SESSION['datein'],$_SESSION['datefn']), 0, 1);
                        }

                        //print assesment DATA/TIME LAST TAKEN
                        if (isset($_POST['assesmentDateTimeLast'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentDateTimeLast, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentDateTimeLastTaken($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment number of times started
                        if (isset($_POST['assesmentNumberStarted'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberStarted, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberStarted($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print exam number of times not finished
                        if (isset($_POST['assesmentNumberNotFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberNotFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberNotFinished($_SESSION['examsparam'][$i],$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment number of times finished
                        if (isset($_POST['assesmentNumberFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment min score finished
                        if (isset($_POST['assesmentMinscoreFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMinscoreFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMinScoreFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment max score finished
                        if (isset($_POST['assesmentMaxscoreFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMaxcoreFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMaxScoreFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment medium score finished
                        if (isset($_POST['assesmentMediumFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMediumFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMedScoreFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment least time finished
                        if (isset($_POST['assesmentLeastTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentLeastTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentLeastTimeFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment most time finished
                        if (isset($_POST['assesmentMostTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMostTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMostTimeFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment medium time finished
                        if (isset($_POST['assesmentMediumTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMediumTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMediumTimeFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment std deviation
                        if (isset($_POST['assesmentStdDeviation'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentStdDeviation, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentStdDeviation($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }
                        
                        //now load all the topics relative to selected student
                        $topics = $db->qLoadTopicUser($_SESSION['examsparam'][$i],$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);

                        //print all statistics relative to each topics loaded before
                        foreach ($topics as $topic) {
                            if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicsStdDeviation']))) {
                                $pdf->SetLeftMargin(10);
                                if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                                    $pdf->AddPage();
                                } else {
                                    if ($i > 0) {
                                        $pdf->AddPage(); // add a page only from second assesment analysis
                                    }
                                }
                                $pdf->SetFont("Helvetica", "B", 16);
                                $pdf->Cell(0, 10, ttReportTopicInformation, 1, 1, 'L', false);
                                $pdf->Cell(0, 5, "", 0, 1);
                            }

                            //print topic medium score
                            if (isset($_POST['topicAverageScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicAverageScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMedScore($topic, $student), 0, 1);
                            }

                            //print topic min score
                            if (isset($_POST['topicMinimumScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicMinimumScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMinScore($topic, $student), 0, 1);
                            }

                            //print topic max score
                            if (isset($_POST['topicMaximumScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicMaximumScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMaxScore($topic, $student), 0, 1);
                            }

                            //print topic std deviation
                            if (isset($_POST['topicStdDeviation'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicStandardDeviation, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicStdDeviation($topic,$student), 0, 1);
                            }

                            $pdf->SetLeftMargin(10);
                        }

                        if ((isset($_POST['graphicHistogram'])) or (isset($_POST['graphicTopicScore']))) {
                            $pdf->SetFont("Helvetica", "B", 16);
                            $pdf->Cell(0, 5, "", 0, 1);
                            $pdf->Cell(0, 10, ttReportGraphicalDsiplays, 1, 1, 'L', false);
                            $pdf->Cell(0, 1, "", 0, 1);
                        }
                        //draw assesments Histograms if selected
                        if (isset($_POST['graphicHistogram'])) {
                            $graphdata = $db->qLoadAssesmentScores($_SESSION['examsparam'][$i],$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);
                            ${'graph' . $i} = new PHPGraphLib(500, 350, "../views/Report/generated_graphs/assesmentsgraph" . $i . ".png");
                            ${'graph' . $i}->addData($graphdata);
                            ${'graph' . $i}->setTitle("Assesments Scores");
                            ${'graph' . $i}->setTextColor("black");
                            ${'graph' . $i}->setXValuesHorizontal(true);
                            ${'graph' . $i}->setBarColor("#6da2ff");
                            ${'graph' . $i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/assesmentsgraph" . $i . ".png");

                        }

                        //draw topics Histograms if selected
                        if (isset($_POST['graphicTopicScore'])) {
                            $graphdatatopic = $db->qLoadTopicScores($topics, $_SESSION['examsparam'][$i],$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);
                            ${'topics' . $i} = new PHPGraphLib(500, 350, "../views/Report/generated_graphs/topicsgraph" . $i . ".png");
                            ${'topics' . $i}->addData($graphdatatopic);
                            ${'topics' . $i}->setTitle("Topics Scores");
                            ${'topics' . $i}->setTextColor("black");
                            ${'topics' . $i}->setXValuesHorizontal(true);
                            ${'topics' . $i}->setBarColor("green");
                            ${'topics' . $i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/topicsgraph" . $i . ".png");
                        }
                    }
                    $i++; //counter of assesments
                    }
            }
            else {
                //case exams are not selected
                $allexams = $db->qLoadExams();
                $i = 0;
                foreach ($allexams as $exam) {
                    $students = $db->qLoadAllStudent($exam, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);
                    foreach ($students as $student) {
                        if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                            if ($i > 0) {
                                $pdf->AddPage();
                            }
                            $pdf->SetFont('Helvetica', 'B', 16);
                            $pdf->Cell(0, 10, ttReportAssessmentInformation, 1, 1, 'L', false);
                            $pdf->Cell(0, 10, "", 0, 1);
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->Cell(85, 10, $db->qLoadStudent($student), 0, 0);
                            $pdf->Cell(70,10,"ID: User_".$student,0,1);
                            $pdf->Cell(0,0.5,"",1,1);
                            $pdf->Cell(0,5,"",0,1);
                            $pdf->SetFont("Helvetica", "B", 16);
                        }

                        //print assesment name
                        if (isset($_POST['assesmentName'])) {
                            $pdf->SetFont("Helvetica","B",13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85,10,ttReportAssesmentName,0,0);
                            $pdf->SetFont("Helvetica","",13);
                            $pdf->Cell(85,10,$exam,0,1);
                        }

                        //print assesment ID
                        if (isset($_POST['assesmentID'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentID, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentID($exam), 0, 1);
                        }

                        //print assesment author
                        if (isset($_POST['assesmentAuthor'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentAuthor, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentAuthor($exam), 0, 1);
                        }

                        //print assesment DATA/TIME FIRST TAKEN
                        if (isset($_POST['assesmentDateTimeFirst'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentDateTimeFirst, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentDateTimeFirstTaken($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment DATA/TIME LAST TAKEN
                        if (isset($_POST['assesmentDateTimeLast'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentDateTimeLast, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentDateTimeLastTaken($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment number of times started
                        if (isset($_POST['assesmentNumberStarted'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberStarted, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberStarted($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print exam number of times not finished
                        if (isset($_POST['assesmentNumberNotFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberNotFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberNotFinished($exam,$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment number of times finished
                        if (isset($_POST['assesmentNumberFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentNumberFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentNumberFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment min score finished
                        if (isset($_POST['assesmentMinscoreFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMinscoreFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMinScoreFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment max score finished
                        if (isset($_POST['assesmentMaxscoreFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMaxcoreFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMaxScoreFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment medium score finished
                        if (isset($_POST['assesmentMediumFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMediumFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMedScoreFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment least time finished
                        if (isset($_POST['assesmentLeastTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentLeastTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentLeastTimeFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment most time finished
                        if (isset($_POST['assesmentMostTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMostTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMostTimeFinished($exam, $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment medium time finished
                        if (isset($_POST['assesmentMediumTimeFinished'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentMediumTimeFinished, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentMediumTimeFinished($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //print assesment std deviation
                        if (isset($_POST['assesmentStdDeviation'])) {
                            $pdf->SetFont("Helvetica", "B", 13);
                            $pdf->SetLeftMargin(30);
                            $pdf->Cell(85, 10, ttReportAssesmentStdDeviation, 0, 0);
                            $pdf->SetFont("Helvetica", "", 13);
                            $pdf->Cell(85, 10, $db->qShowAssesmentStdDeviation($_SESSION['examsparam'][$i], $student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']), 0, 1);
                        }

                        //now load all the topics relative to selected student
                        $topics = $db->qLoadTopicUser($exam,$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);

                        //print all statistics relative to each topics loaded before
                        foreach ($topics as $topic) {
                            if ((isset($_POST['topicAverageScore'])) or (isset($_POST['topicMinimumScore'])) or (isset($_POST['topicMaximumScore'])) or (isset($_POST['topicsStdDeviation']))) {
                                $pdf->SetLeftMargin(10);
                                if ((isset($_POST['assesmentName'])) or (isset($_POST['assesmentID'])) or (isset($_POST['assesmentAuthor'])) or (isset($_POST['assesmentDateTimeFirst'])) or (isset($_POST['assesmentDateTimeLast'])) or (isset($_POST['assesmentNumberStarted'])) or (isset($_POST['assesmentNumberNotFinished'])) or (isset($_POST['assesmentNumberFinished'])) or (isset($_POST['assesmentMinscoreFinished'])) or (isset($_POST['assesmentMaxscoreFinished'])) or (isset($_POST['assesmentMediumFinished'])) or (isset($_POST['assesmentLeastTimeFinished'])) or (isset($_POST['assesmentMostTimeFinished'])) or (isset($_POST['assesmentMediumTimeFinished'])) or (isset($_POST['assesmentStdDeviation']))) {
                                    $pdf->AddPage();
                                } else {
                                    if ($i > 0) {
                                        $pdf->AddPage(); // add a page only from second assesment analysis
                                    }
                                }
                                $pdf->SetFont("Helvetica", "B", 16);
                                $pdf->Cell(0, 10, ttReportTopicInformation, 1, 1, 'L', false);
                                $pdf->Cell(0, 5, "", 0, 1);
                            }

                            //print topic medium score
                            if (isset($_POST['topicAverageScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicAverageScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMedScore($topic, $student), 0, 1);
                            }

                            //print topic min score
                            if (isset($_POST['topicMinimumScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicMinimumScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMinScore($topic, $student), 0, 1);
                            }

                            //print topic max score
                            if (isset($_POST['topicMaximumScore'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicMaximumScore, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicMaxScore($topic, $student), 0, 1);
                            }

                            //print topic std deviation
                            if (isset($_POST['topicStdDeviation'])) {
                                $pdf->SetFont("Helvetica", "B", 13);
                                $pdf->SetLeftMargin(30);
                                $pdf->Cell(85, 10, ttReportTopicStandardDeviation, 0, 0);
                                $pdf->SetFont("Helvetica", "", 13);
                                $pdf->Cell(85, 10, $db->qShowTopicStdDeviation($topic,$student), 0, 1);
                            }

                            $pdf->SetLeftMargin(10);
                        }

                        if ((isset($_POST['graphicHistogram'])) or (isset($_POST['graphicTopicScore']))) {
                            $pdf->SetFont("Helvetica", "B", 16);
                            $pdf->Cell(0, 5, "", 0, 1);
                            $pdf->Cell(0, 10, ttReportGraphicalDsiplays, 1, 1, 'L', false);
                            $pdf->Cell(0, 1, "", 0, 1);
                        }
                        //draw assesments Histograms if selected
                        if (isset($_POST['graphicHistogram'])) {
                            $graphdata = $db->qLoadAssesmentScores($exam,$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);
                            ${'graph' . $i} = new PHPGraphLib(500, 350, "../views/Report/generated_graphs/assesmentsgraph" . $i . ".png");
                            ${'graph' . $i}->addData($graphdata);
                            ${'graph' . $i}->setTitle("Assesments Scores");
                            ${'graph' . $i}->setTextColor("black");
                            ${'graph' . $i}->setXValuesHorizontal(true);
                            ${'graph' . $i}->setBarColor("#6da2ff");
                            ${'graph' . $i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/assesmentsgraph" . $i . ".png");

                        }

                        //draw topics Histograms if selected
                        if (isset($_POST['graphicTopicScore'])) {
                            $graphdatatopic = $db->qLoadTopicScores($topics,$exam,$student, $_SESSION['minscoreparam'], $_SESSION['maxscoreparam'], $_SESSION['datein'], $_SESSION['datefn']);
                            ${'topics' . $i} = new PHPGraphLib(500, 350, "../views/Report/generated_graphs/topicsgraph" . $i . ".png");
                            ${'topics' . $i}->addData($graphdatatopic);
                            ${'topics' . $i}->setTitle("Topics Scores");
                            ${'topics' . $i}->setTextColor("black");
                            ${'topics' . $i}->setXValuesHorizontal(true);
                            ${'topics' . $i}->setBarColor("green");
                            ${'topics' . $i}->createGraph();
                            $pdf->Image("../views/Report/generated_graphs/topicsgraph" . $i . ".png");
                        }
                    }

                    $i++; //counter of assesments
                }
            }
        }

        $pdf->Output();
        $t=time();
        $pdf->Output($config['systemViewsDir']."Report/generated_report/AOreport/AOreport_".date("d-m-Y_H:i:s",$t).".pdf","F");

    }

    /**
     *  @name   actionSavetemplate
     *  @descr  Save a Report Template
     */
    private function actionSavetemplate(){
        if (isset($_POST['assesmentName'])){
            $assesmentName=1;
        }else{$assesmentName=0;}

        if (isset($_POST['assesmentID'])){
            $assesmentID=1;
        }else{$assesmentID=0;}

        if (isset($_POST['assesmentAuthor'])){
            $assesmentAuthor=1;
        }else{$assesmentAuthor=0;}

        if (isset($_POST['assesmentDateTimeFirst'])){
            $assesmentDateTimeFirst=1;
        }else{$assesmentDateTimeFirst=0;}

        if (isset($_POST['assesmentDateTimeLast'])){
            $assesmentDateTimeLast=1;
        }else{$assesmentDateTimeLast=0;}

        if (isset($_POST['assesmentLeastTimeFinished'])){
            $assesmentLeastTimeFinished=1;
        }else{$assesmentLeastTimeFinished=0;}

        if (isset($_POST['assesmentNumberStarted'])){
            $assesmentNumberStarted=1;
        }else{$assesmentNumberStarted=0;}

        if (isset($_POST['assesmentNumberNotFinished'])){
            $assesmentNumberNotFinished=1;
        }else{$assesmentNumberNotFinished=0;}

        if (isset($_POST['assesmentNumberFinished'])){
            $assesmentNumberFinished=1;
        }else{$assesmentNumberFinished=0;}

        if (isset($_POST['assesmentMinscoreFinished'])){
            $assesmentMinscoreFinished=1;
        }else{$assesmentMinscoreFinished=0;}

        if (isset($_POST['assesmentMaxscoreFinished'])){
            $assesmentMaxscoreFinished=1;
        }else{$assesmentMaxscoreFinished=0;}

        if (isset($_POST['assesmentMediumFinished'])){
            $assesmentMediumFinished=1;
        }else{$assesmentMediumFinished=0;}
        if (isset($_POST['assesmentMostTimeFinished'])){
            $assesmentMostTimeFinished=1;
        }else{$assesmentMostTimeFinished=0;}

        if (isset($_POST['assesmentMediumTimeFinished'])){
            $assesmentMediumTimeFinished=1;
        }else{$assesmentMediumTimeFinished=0;}

        if (isset($_POST['assesmentStdDeviation'])){
            $assesmentStdDeviation=1;
        }else{$assesmentStdDeviation=0;}

        if (isset($_POST['topicAverageScore'])){
            $topicAverageScore=1;
        }else{$topicAverageScore=0;}

        if (isset($_POST['topicMinimumScore'])){
            $topicMinimumScore=1;
        }else{$topicMinimumScore=0;}

        if (isset($_POST['topicMaximumScore'])){
            $topicMaximumScore=1;
        }else{$topicMaximumScore=0;}

        if (isset($_POST['topicStdDeviation'])){
            $topicStdDeviation=1;
        }else{$topicStdDeviation=0;}

        if (isset($_POST['graphicHistogram'])){
            $graphicHistogram=1;
        }else{$graphicHistogram=0;}

        if (isset($_POST['graphicTopicScore'])){
            $graphicTopicScore=1;
        }else{$graphicTopicScore=0;}

        $db=new sqlDB();
        if($db->qInsertTemplate($_POST['templateName'],$assesmentName,$assesmentID,$assesmentAuthor,$assesmentDateTimeFirst,$assesmentDateTimeLast,$assesmentNumberStarted,$assesmentNumberNotFinished,$assesmentNumberFinished,$assesmentMinscoreFinished,$assesmentMaxscoreFinished,$assesmentMediumFinished,$assesmentLeastTimeFinished,$assesmentMostTimeFinished,$assesmentMediumTimeFinished,$assesmentStdDeviation,$topicAverageScore,$topicMinimumScore,$topicMaximumScore,$topicStdDeviation,$graphicHistogram,$graphicTopicScore)){
            echo "true";
        }


    }

    private function actionLoadtemplate(){
        global $log;
        $db=new sqlDB();
        $checkbox=json_encode($db->qLoadCheckboxTemplate($_POST['templateName']));
        echo $checkbox;
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
                    'Showstudent','Addstudent','Aoreporttemplate','Showparticipantdetails',
                    'Printparticipantdetails','Aoreportparameters','Showgroups','Aoreportresult',
                    'Savetemplate','Loadtemplate'),
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
