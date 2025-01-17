<?php
/**
 * File: ImportqmController.php
 * User: Elia
 * Date: 5/21/15
 * Time: 10:45 AM
 * Desc: Controller for QM import operations
 */

class ImportqmController extends Controller{

    /**
     *  @name   ImportQMController
     *  @descr  Create an instance of ImportQMController class
     */
    public function ImportqmController (){}

    /**
     * @name    executeAction
     * @param   $action     String      Name of requested action
     * @descr   Execute action (if exists and if user is allowed)
     */
    public function executeAction($action){

        global $user;

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
     *  @descr  Show import index page
     */
    private function actionImportpage(){
        global $engine;

        $engine->renderDoctype();
        $engine->loadLibs();
        $engine->renderHeader();
        $engine->renderPage();
        $engine->renderFooter();

    }

    /**
     *  @name   actionInit
     *  @descr  Perform a  init ImportQM procedure
     */

    private function actionInit(){
        global $config, $log;
        if(file_exists($config['importQMDir'])){
            $res[0]='Questions Folder: <span style=\'color:green; font-weight:bold\'>Found</span></br></br>';
            $res[1]=true;
        }
        else {
            $res[0]='Questions Folder: <span style=\'color:red; font-weight:bold\'>Not Found</span></br></br>';
            $res[1]=false;
        }
        $output=json_encode($res);
        echo $output;
    }


    /**
     *  @name   actionPreview
     *  @descr  Preview import Data
     */

    private function actionPreview()
    {

        global $config, $log;
        $dir_iterator = new RecursiveDirectoryIterator($config['importQMDir']);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        $i = 0;
        $fileCounter = 0;
        $questionCounter=0;
        $temptype='';
        foreach ($iterator as $file) {
            if ($file->isfile()) {
                $path = pathinfo($file);
                if ($path['extension'] == 'xml' && filesize($file) > 0) {
                    $xml = file_get_contents($file) or die("Error: Cannot create object");
                    $xml = ImportQMController::fixImportErrors($xml);
                    $root = new SimpleXMLElement($xml);
                    $fileCounter++;
                    foreach ($root->children() as $item) {
                        $questionCounter++;
                        $itemtype = $item->itemmetadata->qmd_itemtype;
                        $questionsTypeArray[$i] = $itemtype;
                        $i++;
                    }
                }
            }
        }
        echo "<strong>".$fileCounter."</strong> XML Files Found<br/>";
        echo "<strong>".$questionCounter."</strong> Questions Found<br/><br/>";
        $questionsTypeArrayU=array_unique($questionsTypeArray);
        $i=0;
        foreach($questionsTypeArrayU as $key => $value){
            $res[$i]=$value;
            $i++;
        }
        for ($i=0;$i<11;$i++){
            $count[$i]=0;
        }
        $totQ=0;
        for ($i=0;$i<count($res);$i++){
            for ($j=0;$j<count($questionsTypeArray);$j++){
                //echo $res[$i]." ? ".$questionsTypeArray[$j]."<br>";
                if(strcmp($res[$i],$questionsTypeArray[$j])==0){
                    $count[$i]+=1;

                }
            }
            $totQ+=$count[$i];
        }
        echo "<table style='width:50%; margin: 0 auto'><tr><th>Type Of Question</th><th>Number</th></tr>";
        for ($i=0;$i<11;$i++){
            echo '<tr><td>'.$res[$i]."</td><td>".$count[$i]."</td></tr>";
        }
        echo "<tr> <td colspan='2'>&nbsp;</td></tr>";
        echo "</table>";
    }

    /**
     *  @name   actionImport
     *  @descr  Perform a ImportQM procedure
     */
    private function actionImport()
    {
        global $config, $log;
        $dir_iterator = new RecursiveDirectoryIterator($config['importQMDir']);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        $lastIdSubject=-1;
        $lastIdTopic=-1;
        $idLastLang=-1;
        $idTopic=-1;
        $aliasLastLang=-1;
        $lastTokenSbj="";
        $lastSubjectId=-1;
        $lastTokenTopic="";
        $lastTopicId=-1;


        $db = new sqlDB();


        if($db->qSelect("Flag_Import")) {
            if($row = $db->nextRowEnum() and $row[0]==0){

                try {
                    foreach ($iterator as $file) {
                        if ($file->isfile()) {
                            $path = pathinfo($file);
                            if ($path['extension'] == 'xml' && filesize($file) > 0) {


                                $xml = file_get_contents($file) or die("Error: Cannot create object");


                                //FIX IMPORT ENCODING
                                $xml = mb_convert_encoding($xml, 'HTML-ENTITIES', "UTF-8");

                                //$log->append(htmlentities($xml));



                                $xml = ImportQMController::fixImportErrors($xml);



                                //SETTO IL PATH DELLE IMMAGINI
                                $xml = str_replace("%SERVER.GRAPHICS%", $config['topicResQM'], $xml);






                                $root = new SimpleXMLElement($xml);




                                foreach ($root->children() as $item) {

                                    //CDB [subjectName] [lang] version [no.version]{\,/}[difficulty]{\,/}[topic] - [topicName]
                                    $qMetadata = $item->itemmetadata;

                                    $questionsInfo = ImportQMController::parsingQMetadata($qMetadata);

                                    $difficulty = $questionsInfo['topicDifficulty'];
                                    //INSERISCO UNA NUOVA LINGUA SE NON E' PRESENTE
                                    $aliasLang = ImportQMController::getAliasLanguage($questionsInfo['sbjLang']);

                                    if ($aliasLang != $aliasLastLang) {
                                        ImportQMController::createNewLanguage($aliasLang, $questionsInfo['sbjLang']);
                                        $idLang = ImportQMController::getLastSubject($aliasLang);
                                        $idLastLang = $idLang;
                                        $aliasLastLang = $aliasLang;

                                    } else {
                                        $idLang = $idLastLang;
                                    }

                                    //INSERISCO LA MATERIA SE NON ESISTE
                                    $tokenSbj = $questionsInfo['sbjName'] . $aliasLang . $questionsInfo['sbjVers'];

                                    if(strcmp($tokenSbj, $lastTokenSbj) == 0) {
                                        $idSubject = $lastSubjectId;
                                    } else {
                                        $idSubject = ImportQMController::createNewsubject($questionsInfo['sbjName'], "", $aliasLang, $questionsInfo['sbjVers']);
                                        $lastSubjectId = $idSubject;
                                        $lastTokenSbj = $tokenSbj;
                                    }

                                    $tokenTopic = $idSubject . $questionsInfo['topicCode'];
                                    //SE E' SEMPRE LA STESSA MATERIA UTILIZZO L'ID DEL TOPIC PRECEDENTE
                                    if ((strcmp($tokenTopic, $lastTokenTopic)) == 0) {
                                        $idTopic = $lastTopicId;
                                    } else {
                                        $idTopic = ImportQMController::createNewtopic($idSubject, $questionsInfo['topicName'], $questionsInfo['topicCode'],$questionsInfo['topicName']);
                                        $lastTopicId = $idTopic;
                                        $lastTokenTopic = $tokenTopic;
                                    }

                                    //$log->append("idTopic: ".$idTopic);

                                    switch ($questionsInfo['itemtype']) {
                                        
                                        case 'Multiple Choice':
                                            ImportQMController::parserMC($item,$idTopic,"MC",$difficulty,$idLang);
                                            break;

                                        case 'Multiple Response':
                                            ImportQMController::parserMR($item,$idTopic,"MR",$difficulty,$idLang);
                                            break;

                                        case 'True/False':
                                            ImportQMController::parserTF($item,$idTopic,"TF",$difficulty,$idLang);
                                            break;

                                        case 'Numeric':
                                            ImportQMController::parserNM($item,$idTopic,"NM",$difficulty,$idLang);
                                            break;

                                        case 'Text Match':
                                            ImportQMController::parserTM($item, $idTopic, "TM", $difficulty, $idLang);
                                            break;
                                        
                                        case 'Hot Spot':
                                            ImportQMController::parserHS($item, $idTopic, "HS", $difficulty, $idLang);
                                            break;
					

                                    }


                                }
                                //CLEAN THE OUTPUT BUFFER
                                ob_clean();

                            }

                        }

                    }
                    if($db->qUpdateImportFlag()){
                        echo 'ACK';
                    }
                    else{
                        echo 'NACK';
                    }


                }
                catch(Exception $ex){
                    echo 'NACK';
                }






            }
            else
                echo 'NACK';
        }




    }





    /**
     * @name parserTM
     * @param String $item
     * @param String $lastIdTopic
     */
    private static function parserTM($item,$lastIdTopic,$itemtype,$difficulty,$idLang){

        global $log;
        $res=null;
        $i=0;
        $res['Qtext']='';
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<APPLET><applet><embed></EMBED><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $questionOk=false;
        //$log->append("</br>XXX -------> LAST ID TOPIC:         $lastIdTopic");

        //E' RIMASTO DA GESTIRE L'UNICO CASO ISOLATO CON MATIMAGE IL CUI PATH DELL'IMG NON E' COMPLETO
        foreach($item->presentation->children() as $material)
            $res['Qtext'].=ImportQMController::getQuestionText($material);

        if($res['Qtext']=='')
            //$log->append("XXX -------> LAST ID TOPIC: $lastIdTopic DIFFICULTY: $difficulty QUESTION TEXT: ". $res['Qtext']);

        // ------------------------- INIZIO PARSING RISPOSTE TEXT MATCH ------------------------//
        $correctAnswersArrU=array();
        $i=0;
        foreach($item->resprocessing->children() as $respcondition){
            if($respcondition->getName()=="respcondition"){

                if($respcondition->setvar>0){
                    $conditionvar=$respcondition->conditionvar[0];
                    foreach($conditionvar->children() as $varequal){
                        $arr=array();
                        //$log->append($varequal);
                        if(strcmp($varequal->getName(),'varequal')==0) {

                            $arr = ImportQMController::checkNumber(explode(",", $varequal));

                        }
                        if(strcmp($varequal->getName(),'varcontains')==0)
                            $arr=ImportQMController::checkNumber(explode(",",$varequal));

                        //CASO DELLA OR
                        /*
                        <or>
                        <varcontains respident="1" case="No">propylene</varcontains>
                        <varcontains respident="1" case="No">propileno</varcontains>
                        </or>
                        */
                        $arrTemp=array();
                        $arr2=array();
                        if(strcmp($varequal->getName(),'or')==0){
                            foreach($varequal->children() as $vr){
                                $arrTemp=ImportQMController::checkNumber(explode(",",$vr));
                                $arr2=array_merge($arr2,$arrTemp);
                            }
                            //var_dump($arr2);

                        }

                        $resArr=array();
                        $j=0;
                        foreach($arr as $ans){
                            $resArr[$j]=$ans;
                            $j++;
                        }
                        foreach($arr2 as $ans){
                            $resArr[$j]=$ans;
                            $j++;
                        }
                        print_r($resArr);
                        foreach ($resArr as $answer) {
                            $correctAnswersArrU[$i]=$answer;
                            $Aindex = "Atext" . $i;
                            //LETTERA DELLA RISPOSTA
                            $res[$Aindex][0] = '';
                            //TESTO RISPOSTA
                            $res[$Aindex][1] = $answer;
                            $i++;

                            if(strcmp($answer,"")!=0){

                                $questionOk=true;
                            }

                        }

                    }


                }


            }

        }

        // ------------------------- FINE PARSING RISPOSTE TEXT MATCH ------------------------//

        if($questionOk==true) {
            $res['NoCorrect'] = count($correctAnswersArrU);
            $res['NoAnswers'] = $i;
            //$log->append("AAA Topic: ".$lastIdTopic." ".$difficulty." ".$idLang);
            //INSERIMENTO DOMANDA
            //    public function qNewQuestion($idTopic, $type, $difficulty, $extras, $shortText, $translationsQ);
            $row = null;
            $db = new sqlDB();
            $idQuestions[0] = -1;
            $res['Qtext'] = strip_tags($res['Qtext'], $QtextAllowedTags);
            $res['Qtext'] = str_replace("'", "", $res['Qtext']);
            $shortText = strip_tags($res['Qtext'], $shortTextAllowedTags);

            $extra = '';
            //$log->append('EED'.$res['Qtext']);
            if (strpos($res['Qtext'], '<APPLET') !== false || strpos($res['Qtext'], '<EMBED') !== false) {
                $extra = 'c';
                //$log->append('EEE'.$extra);
            }
            $res['Qtext'] = strip_tags($res['Qtext'], $QtextAllowedTags2);

            $idLastLang = 0;
            if ($idLang > $idLastLang)
                $idLastLang = $idLang;
            $translationsQ[0] = null;
            for ($j = 1; $j <= $idLastLang; $j++) {

                if ($idLang == $j) {
                    $translationsQ[$j] = $res['Qtext'];
                } else {
                    $translationsQ[$j] = "";
                }

            }

            if ($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty, $extra, $shortText, $translationsQ)) {
                if ($row = $db->nextRowEnum()) {
                    //$log->append($row[0]);
                }
            } else {
                //$log->append("## QUESTION TEXT: " . $res['Qtext'] . " idTopic: " . $lastIdTopic . " idLang: " . $idLang);

            }

            $db->close();
            $db = new sqlDB();
            //INSERIMENTO RISPOSTE
            $idLastLang = 0;
            for ($i = 0; $i < $res['NoAnswers']; $i++) {
                $db = new sqlDB();
                $Aindex = "Atext" . $i;
                $score = 1;
                //$log->append("SSS".$score);
                //$res[$Aindex][1]=strcmp($res[$Aindex][1],'')==0 ? 'NO TEXT' : $res[$Aindex][1];
                $res[$Aindex][1] = strip_tags($res[$Aindex][1], $QtextAllowedTags);

                $translationsA[0] = null;

                if ($idLang > $idLastLang)
                    $idLastLang = $idLang;

                for ($j = 1; $j <= $idLastLang; $j++) {

                    if ($idLang == $j) {
                        $translationsA[$j] = $res[$Aindex][1];
                    } else {
                        $translationsA[$j] = "";
                    }

                }

                //$log->append(count($translationsA));
                if ($db->qNewAnswer($row[0], $score, $translationsA)) {

                } else {
                    //$log->append('AAA '.serialize($translationsA)." Topic id ".$lastIdTopic." ".$difficulty." ".$res['Qtext']." ".$idLang);
                    //$log->append('AAA '.serialize($res)." Topic id ".$lastIdTopic." ".$difficulty);

                }
                $db->close();
            }
        }

    }


    /**
     * @name parserMR
     * @param String $item
     * @param String $lastIdTopic
     */
    private static function parserMR($item,$lastIdTopic,$itemtype,$difficulty,$idLang){

        global $log;
        $res=null;
        $i=0;
        $res['Qtext']='';
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<table><TABLE><tr><TR><td><TD><th><TH><APPLET><applet><embed></EMBED><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";


        //$log->append("</br>XXX -------> LAST ID TOPIC:         $lastIdTopic");

        //E' RIMASTO DA GESTIRE L'UNICO CASO ISOLATO CON MATIMAGE IL CUI PATH DELL'IMG NON E' COMPLETO
        foreach($item->presentation->children() as $material)
            $res['Qtext'].=ImportQMController::getQuestionText($material);
        /*
        if($res['Qtext']=='')
            $log->append("XXX -------> LAST ID TOPIC: $lastIdTopic DIFFICULTY: $difficulty QUESTION TEXT: ". $res['Qtext']);
        */


        $response_lid = $item->presentation->response_lid[0]->render_choice;
        foreach($response_lid->children() as $response_label){
            if($res['Qtext']=='' and strcmp($response_label->getName(),'material')==0) {
                $res['Qtext'].=$response_label->mattext;
            }
            if(strcmp($response_label->getName(),'response_label')==0) {
                $Aindex = "Atext" . $i;
                //LETTERA DELLA RISPOSTA
                $res[$Aindex][0] = $response_label['ident'];
                //TESTO RISPOSTA
                $res[$Aindex][1] = $response_label->material->mattext;
                $i++;
            }

        }

        $j=0;
        $addAction=false;
        foreach($item->resprocessing->children() as $respcondition){

            if($respcondition->getName()=="respcondition"){

                if(($respcondition->setvar>0) && ($respcondition->setvar['action']=='Add')){
                    $conditionvar=$respcondition->conditionvar[0];
                    foreach($conditionvar->children() as $varequal){
                        if(strcmp($varequal->getName(),'varequal')==0){
                            $correctAnswersArr[$j]=$varequal;
                            $j++;
                        }
                    }

                    $addAction=true;
                }


            }

        }

        if($addAction==false) {
            foreach ($item->resprocessing->children() as $respcondition) {
                if ($respcondition->getName() == "respcondition") {
                    if (($respcondition->setvar > 0) && ($respcondition->setvar['action'] == 'Set')) {
                        $conditionvar = $respcondition->conditionvar[0];
                        foreach ($conditionvar->children() as $varequal) {
                            if (strcmp($varequal->getName(), 'varequal') == 0) {
                                $correctAnswersArr[$j] = $varequal;
                                $j++;
                            }
                        }


                    }
                }
            }
        }

        $arrTemp=array_unique($correctAnswersArr);
        $c=0;
        foreach($arrTemp as $value){
            $correctAnswersArrU[$c]=$value;
            //$log->append($correctAnswersArrU[$c]);
            $c++;
        }

        $res['NoCorrect']=count($correctAnswersArrU);
        $res['NoAnswers']=$i;
        //$log->append("AAA Topic: ".$lastIdTopic." ".$difficulty." ".$idLang);

        $res['Acorrect']=ImportQMController::setScoreMR($res,$correctAnswersArrU);


        //INSERIMENTO DOMANDA
        //    public function qNewQuestion($idTopic, $type, $difficulty, $extras, $shortText, $translationsQ);


        $row=null;
        $db = new sqlDB();
        $idQuestions[0]=-1;
        $res['Qtext']=strip_tags($res['Qtext'],$QtextAllowedTags);
        $res['Qtext']=str_replace("'","",$res['Qtext']);
        $shortText=strip_tags($res['Qtext'],$shortTextAllowedTags);

        $extra='';
        //$log->append('EED'.$res['Qtext']);
        if(strpos($res['Qtext'],'<APPLET')!==false || strpos($res['Qtext'],'<EMBED')!==false) {
            $extra = 'c';
            //$log->append('EEE'.$extra);
        }
        $res['Qtext'] =strip_tags($res['Qtext'],$QtextAllowedTags2);

        $idLastLang=0;
        if($idLang>$idLastLang)
            $idLastLang=$idLang;
        $translationsQ[0]=null;
        for($j=1;$j<=$idLastLang;$j++){


            if($idLang==$j){
                $translationsQ[$j]=$res['Qtext'];
            }
            else{
                $translationsQ[$j]="";
            }

        }

        if($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty, $extra, $shortText,$translationsQ)){
            if($row = $db->nextRowEnum()){
                //$log->append($row[0]);

            }
        }else{
            //$log->append("## QUESTION TEXT: ".$res['Qtext']." idTopic: ".$lastIdTopic." idLang: ".$idLang);


        }

        $db->close();
        $db = new sqlDB();

        //INSERIMENTO RISPOSTE
        $idLastLang=0;
        for($i=0;$i<$res['NoAnswers'];$i++){
            $db = new sqlDB();

            $Aindex="Atext".$i;
            $ACindex="Acorrect".$i;
            $score=0;

            $score=$res['Acorrect'][$ACindex]['score'];

            //$log->append("SSS".$score);

            //$res[$Aindex][1]=strcmp($res[$Aindex][1],'')==0 ? 'NO TEXT' : $res[$Aindex][1];
            $res[$Aindex][1]=strip_tags($res[$Aindex][1],$QtextAllowedTags);


            $translationsA[0]=null;

            if($idLang>$idLastLang)
                $idLastLang=$idLang;

            for($j=1;$j<=$idLastLang;$j++){

                if($idLang==$j){
                    $translationsA[$j]=$res[$Aindex][1];
                }
                else{
                    $translationsA[$j]="";
                }

            }

            //$log->append(count($translationsA));

            if($db->qNewAnswer($row[0], $score , $translationsA)) {

            }
            else{
                //$log->append('AAA'.$lastIdTopic." ".$difficulty." ".$res['Qtext']." ".$idLang);

            }
            $db->close();
        }



    }


    /**
     * @name parserMC
     * @param String $item
     * @param String $lastIdTopic
     * @descr parse the MC questions
     */

    private static function parserMC($item,$lastIdTopic,$itemtype,$difficulty,$idLang){
        global $log;
        $res=null;
        $i=0;
        $res['Qtext']='';
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<table><TABLE><tr><TR><td><TD><th><TH><embed></EMBED><APPLET><applet><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";



        //E' RIMASTO DA GESTIRE L'UNICO CASO ISOLATO CON MATIMAGE IL CUI PATH DELL'IMG NON E' COMPLETO
        foreach($item->presentation->children() as $material) {
            $res['Qtext'] .= ImportQMController::getQuestionText($material);
        }





        if(strpos($res['Qtext'],'topicresources/1222601455/A3S3007_es.jpg')!=false)
            $log->append('ZZZ' . substr($res['Qtext'],strlen($res['Qtext'])-10,10));
        $response_lid = $item->presentation->response_lid[0]->render_choice;
        foreach($response_lid->children() as $response_label){

            if(strcmp($response_label->getName(),'response_label')==0) {
                $Aindex = "Atext" . $i;
                //LETTERA DELLA RISPOSTA
                $res[$Aindex][0] = $response_label['ident'];
                //TESTO RISPOSTA
                $res[$Aindex][1] = $response_label->material->mattext;
                $i++;
            }

        }

        $max=0;
        $letter='';

        foreach($item->resprocessing->children() as $respcondition){

            if(($respcondition->setvar)>$max){
                $letter=$respcondition->conditionvar->varequal;
                $max=$respcondition->setvar;
            }


        }
        $res['Acorrect']=$letter;
        $res['NoAnswers']=$i;


        //INSERIMENTO DOMANDA
        //    public function qNewQuestion($idTopic, $type, $difficulty, $extras, $shortText, $translationsQ);


        $row=null;
        $db = new sqlDB();
        $idQuestions[0]=-1;
        $res['Qtext']=strip_tags($res['Qtext'],$QtextAllowedTags);
        $res['Qtext']=str_replace("'","",$res['Qtext']);
        $shortText=strip_tags($res['Qtext'],$shortTextAllowedTags);

        $extra='';
        //$log->append('EED'.$res['Qtext']);
        if(strpos($res['Qtext'],'<APPLET')!==false || strpos($res['Qtext'],'<EMBED')!==false) {
            $extra = 'c';
            //$log->append('EEE'.$extra);
        }
        $res['Qtext'] =strip_tags($res['Qtext'],$QtextAllowedTags2);

        $idLastLang=0;
        if($idLang>$idLastLang)
            $idLastLang=$idLang;
        $translationsQ[0]=null;
        for($j=1;$j<=$idLastLang;$j++){


            if($idLang==$j){
                $translationsQ[$j]=$res['Qtext'];
            }
            else{
                $translationsQ[$j]="";
            }

        }



        if($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty, $extra, $shortText,$translationsQ)){
            if($row = $db->nextRowEnum()){
                //$log->append($row[0]);

            }
        }else{
            //$log->append("## QUESTION TEXT: ".$res['Qtext']." idTopic: ".$lastIdTopic." idLang: ".$idLang);


        }

        $db->close();
        $db = new sqlDB();


        //INSERIMENTO RISPOSTE
        $idLastLang=0;
        for($i=0;$i<$res['NoAnswers'];$i++){
            $db = new sqlDB();
            $Aindex="Atext".$i;

            $score=0;

            if((strcmp($res[$Aindex][0],$res['Acorrect']))==0)
                $score=1.0;
            else
                $score=0.0;


            //$log->append($score ."   ".$idLang."   ".$res[$Aindex][1]);

            //$res[$Aindex][1]=strcmp($res[$Aindex][1],'')==0 ? 'NO TEXT' : $res[$Aindex][1];
            $res[$Aindex][1]=strip_tags($res[$Aindex][1],$QtextAllowedTags);


            $translationsA[0]=null;

            if($idLang>$idLastLang)
                $idLastLang=$idLang;

            for($j=1;$j<=$idLastLang;$j++){


                if($idLang==$j){
                    $translationsA[$j]=$res[$Aindex][1];
                }
                else{
                    $translationsA[$j]="";
                }

            }


            //$log->append(count($translationsA));

            if($db->qNewAnswer($row[0], $score , $translationsA)) {

            }
            else{
                //$log->append('AAA'.$lastIdTopic." ".$difficulty." ".$res['Qtext']." ".$idLang);

            }
            $db->close();
        }



    }


    /**
     * @name parserTF
     * @param String $item
     * @param String $lastIdTopic
     */
    private static function parserTF($item,$lastIdTopic,$itemtype,$difficulty,$idLang){
        global $log;
        $res=null;
        $i=0;
        $res['Qtext']='';
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<table><TABLE><tr><TR><td><TD><th><TH><embed></EMBED><APPLET><applet><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";


        //E' RIMASTO DA GESTIRE L'UNICO CASO ISOLATO CON MATIMAGE IL CUI PATH DELL'IMG NON E' COMPLETO
        foreach($item->presentation->children() as $material)
            $res['Qtext'].=ImportQMController::getQuestionText($material);



        $response_lid = $item->presentation->response_lid[0]->render_choice;
        foreach($response_lid->children() as $response_label){

            if(strcmp($response_label->getName(),'response_label')==0) {
                $Aindex = "Atext" . $i;
                //LETTERA DELLA RISPOSTA
                $res[$Aindex][0] = $response_label['ident'];
                //TESTO RISPOSTA
                $res[$Aindex][1] = $response_label->material->mattext;
                $i++;
            }

        }

        $max=0;
        $letter='';

        foreach($item->resprocessing->children() as $respcondition){

            if(($respcondition->setvar)>$max){
                $letter=$respcondition->conditionvar->varequal;
                $max=$respcondition->setvar;
            }


        }
        $res['Acorrect']=$letter;
        $res['NoAnswers']=$i;


        //INSERIMENTO DOMANDA
        //    public function qNewQuestion($idTopic, $type, $difficulty, $extras, $shortText, $translationsQ);


        $row=null;
        $db = new sqlDB();
        $idQuestions[0]=-1;
        $res['Qtext']=strip_tags($res['Qtext'],$QtextAllowedTags);
        $res['Qtext']=str_replace("'","",$res['Qtext']);
        $shortText=strip_tags($res['Qtext'],$shortTextAllowedTags);

        $extra='';
        //$log->append('EED'.$res['Qtext']);
        if(strpos($res['Qtext'],'<APPLET')!==false || strpos($res['Qtext'],'<EMBED')!==false) {
            $extra = 'c';
            //$log->append('EEE'.$extra);
        }
        $res['Qtext'] =strip_tags($res['Qtext'],$QtextAllowedTags2);

        $idLastLang=0;
        if($idLang>$idLastLang)
            $idLastLang=$idLang;
        $translationsQ[0]=null;
        for($j=1;$j<=$idLastLang;$j++){


            if($idLang==$j){
                $translationsQ[$j]=$res['Qtext'];
            }
            else{
                $translationsQ[$j]="";
            }

        }




        if($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty,$extra, $shortText,$translationsQ)){
            if($row = $db->nextRowEnum()){
                //$log->append($row[0]);

            }
        }else{
            //("## QUESTION TEXT: ".$res['Qtext']." idTopic: ".$lastIdTopic." idLang: ".$idLang);


        }

        $db->close();
        $db = new sqlDB();


        //INSERIMENTO RISPOSTE
        $idLastLang=0;
        for($i=0;$i<$res['NoAnswers'];$i++){
            $db = new sqlDB();
            $Aindex="Atext".$i;
            $text=$res[$Aindex][1];
            $re=substr($text,0,1);


            $score=null;

            if((strcmp($res[$Aindex][0],$res['Acorrect']))==0)
                $score=$re . "*1";
            else
                $score=$re . "*0";


            //$log->append($score ."   ".$idLang."   ".$res[$Aindex][1]);

            //$res[$Aindex][1]=strcmp($res[$Aindex][1],'')==0 ? 'NO TEXT' : $res[$Aindex][1];
            $res[$Aindex][1]=strip_tags($res[$Aindex][1],$QtextAllowedTags);


            $translationsA[0]=null;

            if($idLang>$idLastLang)
                $idLastLang=$idLang;

            for($j=1;$j<=$idLastLang;$j++){


                if($idLang==$j){
                    $translationsA[$j]=$res[$Aindex][1];
                }
                else{
                    $translationsA[$j]="";
                }

            }


            //$log->append(count($translationsA));

            if($db->qNewAnswer($row[0], $score , $translationsA)) {

            }
            else{
                //$log->append('AAA'.$lastIdTopic." ".$difficulty." ".$res['Qtext']." ".$idLang);

            }
            $db->close();
        }



    }

    /**
     * @name parserNM
     * @param String $item
     * @param String $lastIdTopic
     */
    private static function parserNM($item,$lastIdTopic,$itemtype,$difficulty,$idLang){
        global $log;
        $i=0;
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<table><TABLE><tr><TR><td><TD><th><TH><EMBED><embed></EMBED><APPLET><applet><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";

        $res['Qtext']='';

        //E' RIMASTO DA GESTIRE L'UNICO CASO ISOLATO CON MATIMAGE IL CUI PATH DELL'IMG NON E' COMPLETO
        foreach($item->presentation->children() as $material)
            $res['Qtext'].=ImportQMController::getQuestionText($material);

        //INSERIMENTO DOMANDA
        $row=null;
        $db = new sqlDB();
        $idQuestions[0]=-1;
        $res['Qtext']=strip_tags($res['Qtext'],$QtextAllowedTags);
        $res['Qtext']=str_replace("'","",$res['Qtext']);
        $shortText=strip_tags($res['Qtext'],$shortTextAllowedTags);

        $extra='';
        //$log->append('EED'.$res['Qtext']);
        if(strpos($res['Qtext'],'<APPLET')!==false || strpos($res['Qtext'],'<EMBED')!==false) {
            $extra = 'c';
            //$log->append('EEE'.$extra);
        }
        $res['Qtext'] =strip_tags($res['Qtext'],$QtextAllowedTags2);

        $idLastLang=0;
        if($idLang>$idLastLang)
            $idLastLang=$idLang;
        $translationsQ[0]=null;
        for($j=1;$j<=$idLastLang;$j++){


            if($idLang==$j){
                $translationsQ[$j]=$res['Qtext'];
            }
            else{
                $translationsQ[$j]="";
            }

        }

        $resprocessing = $item->resprocessing;
        $max = 0;

        //CHECKIF ANSWERS HAVE TEXT
        foreach($resprocessing->children() as $respcondition){
            if($respcondition->getName()=="respcondition"){

                if($respcondition->setvar>$max){
                    $max=$respcondition->setvar;
                    $letter=$respcondition->conditionvar->varequal;
                    if($letter == '') {
                        $letter = $respcondition->conditionvar->vargte;
                        if($letter == '') {
                            $letter = $respcondition->conditionvar->varlte;
                            if ($letter == '') {
                                $letter = $respcondition->conditionvar->or->vargte;
                                if ($letter == '')
                                    $letter = $respcondition->conditionvar->or->varequal;
                            }
                        }

                    }


                }

            }
        }
        if($letter!='') {


            if ($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty, $extra, $shortText, $translationsQ)) {
                if ($row = $db->nextRowEnum()) {
                    $temp = $row[0];

                }
            } else {
                //$log->append("## QUESTION TEXT: ".$res['Qtext']." idTopic: ".$lastIdTopic." idLang: ".$idLang);


            }

            $db->close();
            $db = new sqlDB();


            $resprocessing = $item->resprocessing;
            $max = 0;

            foreach ($resprocessing->children() as $respcondition) {
                if ($respcondition->getName() == "respcondition") {

                    if ($respcondition->setvar > $max) {
                        $max = $respcondition->setvar;
                        $letter = $respcondition->conditionvar->varequal;
                        if ($letter == '') {
                            $letter = $respcondition->conditionvar->vargte;
                            if ($letter == '') {
                                $letter = $respcondition->conditionvar->varlte;
                                if ($letter == '') {
                                    $letter = $respcondition->conditionvar->or->vargte;
                                    if ($letter == '')
                                        $letter = $respcondition->conditionvar->or->varequal;
                                }
                            }

                        }


                    }

                }
            }


            //INSERIMENTO RISPOSTE
            $idLastLang = 0;

            $db = new sqlDB();
            $letter;
            $score = 1.0;

            //$log->append($score ."   ".$idLang."   ".$res[$Aindex][1]);

            //$res[$Aindex][1]=strcmp($res[$Aindex][1],'')==0 ? 'NO TEXT' : $res[$Aindex][1];
            $letter = strip_tags($letter, $QtextAllowedTags);


            $translationsA[0] = null;

            if ($idLang > $idLastLang)
                $idLastLang = $idLang;

            for ($j = 1; $j <= $idLastLang; $j++) {


                if ($idLang == $j) {
                    $translationsA[$j] = $letter;
                } else {
                    $translationsA[$j] = "";
                }

            }


            //$log->append(count($translationsA));

            if ($db->qNewAnswer($row[0], $score, $translationsA)) {

            } else {
                //$log->append('AAA idQuestion' . $temp . " " . $lastIdTopic . " " . $difficulty . " " . $res['Qtext'] . " " . $idLang);

            }
            $db->close();
        }


    }


    /**
     * @name parserHS
     * @param String $item
     * @param String $lastIdTopic
     * @descr parse the HS questions
     */

    private static function parserHS($item,$lastIdTopic,$itemtype,$difficulty,$idLang){
        global $log;
        $resmat=null;
        $i=0;
        $res['Qtext']='';
        $shortTextAllowedTags="<p><sub><sup><P><SUB><SUP>";
        $QtextAllowedTags="<table><TABLE><tr><TR><td><TD><th><TH><embed></EMBED><APPLET><applet><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";
        $QtextAllowedTags2="<table><TABLE><tr><TR><td><TD><th><TH><span><div><em></div></span><object><p><img><sub><sup><OBJECT><P><IMG><SUB><SUP>";


        foreach($item->presentation->children() as $material) {
            $res['Qtext'] .= ImportQMController::getQuestionText($material);
        }

        $material = $item->presentation->response_xy->render_hotspot->material;
        //$log->append('HS Q'.(isset($material)?'yes':'no'));

        if(isset($material)) {

            if (strcmp($material->matimage->getName(), 'matimage') == 0) {
                //$log->append('HS Q'.$material->matimage->getName());
                $matimage = $material->matimage;
                $srcImg = $matimage['uri'];
                $heightImg = $matimage['height'];
                $widthImg = $matimage['width'];
                $resmat = "<img src='../$srcImg' height='$heightImg' height='$widthImg' alt=''/> ";
            }

            //CONCACT TEXT WITH HOTSPOT IMG
            $res['Qtext'] .= $resmat;
            //$log->append('HS Q' . $resmat);

            $max = 0;

            foreach ($item->resprocessing->children() as $respcondition) {
                if (($respcondition->setvar) > $max) {
                    $range = $respcondition->conditionvar->varinside;
                    $max = $respcondition->setvar;
                }
            }
            $res['Acorrect'] = $range;
            $res['NoAnswers'] = $i;


            //INSERT QUESTIONS
            $row = null;
            $db = new sqlDB();
            $idQuestions[0] = -1;
            $res['Qtext'] = strip_tags($res['Qtext'], $QtextAllowedTags);
            $res['Qtext'] = str_replace("'", "", $res['Qtext']);
            $shortText = strip_tags($res['Qtext'], $shortTextAllowedTags);
            $extra = '';

            //$log->append('EED' . $res['Qtext']);
            if (strpos($res['Qtext'], '<APPLET') !== false || strpos($res['Qtext'], '<EMBED') !== false) {
                $extra = 'c';
                //$log->append('EEE' . $extra);
            }
            $res['Qtext'] = strip_tags($res['Qtext'], $QtextAllowedTags2);

            $idLastLang = 0;
            if ($idLang > $idLastLang)
                $idLastLang = $idLang;
            $translationsQ[0] = null;
            for ($j = 1; $j <= $idLastLang; $j++) {
                if ($idLang == $j) {
                    $translationsQ[$j] = $res['Qtext'];
                } else {
                    $translationsQ[$j] = "";
                }
            }

            if ($db->qNewQuestion($lastIdTopic, $itemtype, $difficulty, $extra, $shortText, $translationsQ)) {
                if ($row = $db->nextRowEnum()) {
                    //$log->append($row[0]);
                }
            }
            else{
                //$log->append("## QUESTION TEXT: " . $res['Qtext'] . " idTopic: " . $lastIdTopic . " idLang: " . $idLang);
            }
            $db->close();
            $db = new sqlDB();

            //Normalize range   es. 12,4 21,3 -> 12,4,21,3
            $coord = explode(' ', $res['Acorrect']);
            $res['Acorrect'] = implode(',', $coord);


            //INSERT ANSWERS
            $idLastLang = 0;
            $db = new sqlDB();
            $score = 1.0;
            $translationsA[0] = null;
            if ($idLang > $idLastLang)
                $idLastLang = $idLang;
            for ($j = 1; $j <= $idLastLang; $j++) {
                if ($idLang == $j) {
                    $translationsA[$j] = $res['Acorrect'];
                } else {
                    $translationsA[$j] = "";
                }
            }
            //$log->append(count($translationsA));
            if ($db->qNewAnswer($row[0], $score, $translationsA)) {

            } else {
                //$log->append('AAA'.$lastIdTopic." ".$difficulty." ".$res['Qtext']." ".$idLang);
            }
            $db->close();
        }




    }

    /**
     * @name fixImportErrors
     * @param String $xml root $xml
     * @return Array $xml root $xml
     */
    private static function fixImportErrors($xml){
        //FIX ERROR GENERATED BY EXPORT
        $xml=str_replace("&apos","&apos;",$xml);
        $xml=str_replace("^","&and;",$xml);
        $xml=str_replace("&lt","&lt;",$xml);
        $xml=str_replace("&gt","&gt;",$xml);
        $xml=str_replace("&auml;","&#228;",$xml);
        $xml=str_replace("><</", ">&lt;</", $xml);
        if(strpos($xml,"</questestinterop>")==false){
            $xml=$xml."</questestinterop>";
        }
        return $xml;


    }


    /**
     * @name parsingQPath
     * @param String $qpath question path
     * @return Array $res quesion info
     */
    private static function parsingQMetadata($qMetadata)
    {
        $qPath=$qMetadata->qmd_topic;

        global $log;
        //ARRAY COD -> NAME SUBJECTS
        $subjectsList['ac3'] = 'Analyical Chemistry 3';
        $subjectsList['bc3'] = 'Biological Chemistry 3';
        $subjectsList['cc4'] = 'Computational Chemistry 4';
        $subjectsList['ce3'] = 'Chemical Engineering 3';
        $subjectsList['ch4'] = 'Cultural Heritage 4';
        $subjectsList['gc1'] = 'General Chemistry 1';
        $subjectsList['gc2'] = 'General Chemistry 2';
        $subjectsList['gc'] = 'General Chemistry';
        $subjectsList['ic3'] = 'Inorganic Chemistry 3';
        $subjectsList['oc3'] = 'Organic Chemistry 3';
        $subjectsList['pc3'] = 'Physical Chemistry 3';
        $subjectsList['mc'] = ' mc ';
        $subjectsList['xxx'] = 'END';

        //CREATE ASSOCIATIVE ARRAY FROM PATH STRING
        if (substr_count($qPath, "/") > 0)
            $parts = explode("/", $qPath);
        else
            $parts = explode("\\", $qPath);

        //GET ARRAY $parts LENGTH
        $lenParts = count($parts);

        //MANAGE ALL CASES
        switch ($lenParts) {
            case 2:
                $subjectName = strtolower($parts[0]);
                $difficulty = 1;
                $TopicName = $parts[1];

                //GESTISCO IL CASO UNICO cdb_mc_v400en
                if ($subjectName == 'cdb_mc_v400en') {

                    $sbjName = 'mc';
                    $sbjLang = 'english';
                    $version = '4.00';
                    $difficulty = 1;

                }
                break;
            case 3:
                $subjectName = strtolower($parts[0]);
                $difficulty = substr($parts[1], strlen($parts[1]) - 1, 1);
                $TopicName = $parts[2];
                break;
            case 4:
                $temp=explode(' ',$parts[1]);
                $modulo=$temp[2];
                $tParts=explode(' ',$parts[0]);
                $tParts[1]=$tParts[1].$modulo;
                $parts[0]=implode(' ',$tParts);

                $subjectName = strtolower($parts[0]);
                $difficulty = substr($parts[2], strlen($parts[2]) - 1, 1);
                $TopicName = $parts[3];
                break;

        }

        if (substr_count($subjectName, "version") > 0) {
            $SubjectParts = explode("version", $subjectName);
            $SubjectNameAndLang = explode(" ", $SubjectParts[0]);
            $sbjName = $SubjectNameAndLang[1];
            $sbjLang = $SubjectNameAndLang[2];
            $version = $SubjectParts[1];
            if (substr($version, 2, 1) != '.') {
                $version = substr($version, 1, 1) . '.' . substr($version, 2, 2);
            }
            $version = floatval($version);

        }

        //PREPARE RESULTS ASSOCIATIVE ARRAY
        $TopicNames = explode(' - ', $TopicName);

        if (count($TopicNames) > 1) {
            $res['topicCode'] = substr($TopicNames[0],0,strlen($TopicNames[0])-2).substr($TopicNames[0],strlen($TopicNames[0])-1,1);
            $res['topicName'] = $TopicNames[1];
        } else {
            $TopicNames = explode(' – ', $TopicName);
            if (count($TopicNames) > 1) {
                $res['topicCode'] = substr($TopicNames[0],0,strlen($TopicNames[0])-2).substr($TopicNames[0],strlen($TopicNames[0])-1,1);
                $res['topicName'] = $TopicNames[1];
            } else {
                $TopicNames = explode('  -', $TopicName);
                if (count($TopicNames) > 1) {
                    $res['topicCode'] = substr($TopicNames[0],0,strlen($TopicNames[0])-2).substr($TopicNames[0],strlen($TopicNames[0])-1,1);
                    $res['topicName'] = $TopicNames[1];
                } else {
                    $TopicNames = explode('–', $TopicName);
                    if (count($TopicNames) > 1) {
                        $res['topicCode'] = substr($TopicNames[0],0,strlen($TopicNames[0])-2).substr($TopicNames[0],strlen($TopicNames[0])-1,1);
                        $res['topicName'] = $TopicNames[1];
                    } else {
                        $TopicNames = explode(' -', $TopicName);
                        if (count($TopicNames) > 1) {
                            $res['topicCode'] = substr($TopicNames[0],0,strlen($TopicNames[0])-2).substr($TopicNames[0],strlen($TopicNames[0])-1,1);
                            $res['topicName'] = $TopicNames[1];
                        } else {
                            $res['topicCode'] = $TopicNames[0];
                            $res['topicName'] = $TopicNames[0];
                        }
                    }
                }
            }
        }

        if($sbjName=='ce3'){
            $res['topicCode']=substr($res['topicCode'],0,strlen($res['topicCode'])-2) . substr($res['topicCode'],strlen($res['topicCode'])-1,1);
            //$log->append('###'.$res['topicCode']);
        }



        //$log->append($res['topicCode']);
        $res['sbjName']=$subjectsList[$sbjName];
        $res['sbjLang']=$sbjLang;
        $res['sbjVers']=$version;
        $res['topicDifficulty']=$difficulty;
        $res['itemtype'] = $qMetadata->qmd_itemtype;


        return $res;


    }


    /**
     *  @name   createNewLanguage
     *  @descr  Creates a new XML language file
     */
    private static function getAliasLanguage($description){

        $langCode['english']='en';
        $langCode['spanish']='es';
        $langCode['german']='de';
        $langCode['french']='fr';
        $langCode['italian']='it';
        $langCode['polish']='pl';
        $langCode['russian']='ru';
        $langCode['greek']='gr';
        $langCode['slovenian']='si';

        return $langCode[$description];

    }



    /**
     *  @name   createNewLanguage
     *  @descr  Creates a new XML language file
     */
    private static function createNewLanguage($alias,$description){
        global $engine, $log, $config;

            if (file_exists($config['systemLangsDir'] . $alias . '/')) {

            } else {
                $db = new sqlDB();
                if ($db->qCreateLanguage($alias, $description)) {
                    if ((mkdir($config['systemLangsDir'] . $alias . '/')) &&
                        (copy($config['systemLangsDir'] . 'en/lang.php', $config['systemLangsDir'] . $alias . '/lang.php')) &&
                        (copy($config['systemLangsDir'] . 'en/lang.js', $config['systemLangsDir'] . $alias . '/lang.js')) &&
                        (copy($config['systemLangsXml'] . 'en.xml', $config['systemLangsXml'] . $alias . '.xml'))
                    ) {
                        $xml = new DOMDocument();
                        $xml->load($config['systemLangsXml'] . $alias . '.xml');
                        $xml->getElementById('alias')->nodeValue = $alias;
                        $xml->getElementById('name')->nodeValue = $description;
                        $xml->save($config['systemLangsXml'] . $alias . '.xml');
                        //echo 'ACK';
                    } else {
                        unlink($config['systemLangsDir'] . $alias . '/lang.php');
                        unlink($config['systemLangsDir'] . $alias . '/lang.js');
                        unlink($config['systemLangsXml'] . $alias . '.xml');
                        rmdir($config['systemLangsDir'] . $alias . '/');
                    }



                } else {
                    echo ttError;
                }
            }
    }



    /**
     *  @name   createNewsubject
     *  @descr  Show page to create a new subject
     */
    private function createNewsubject($sbjName,$sbjDesc,$sbjLang,$sbjVers){
        global $log;


            $db = new sqlDB();
            if (($db->qSelect("Languages","alias",$sbjLang) && ($langId = $db->nextRowEnum()))) {
                if (($db->qNewSubject($sbjName." - ".strtoupper($sbjLang)." V". $sbjVers, $sbjDesc, $langId[0], $sbjVers)) && ($subjectID = $db->nextRowEnum())) {
                    return $subjectID[0];
                } else {
                    //die($db->getError());
                    return -1;
                }

            }
            $db->close();
    }



    /**
     *  @name   createNewtopic
     *  @descr  Show page to create a new topic
     */
    private function createNewtopic($idSbj,$topicName,$topicCode,$topicDesc){
        global $log;


        $db = new sqlDB();
        if($db->qNewTopicV2($idSbj, $topicName,$topicCode, $topicDesc)){
            if($row = $db->nextRowEnum()){
                return $row[0];
            }
            else{
                return -1;
            }
        }else{
            //die($db->getError());

            if($db->qSelectTwoArgs("Topics", "code", $topicCode,"fkSubject", $idSbj)) {
                if($row = $db->nextRowEnum()){
                    //$log->append("###".$row[0]);

                    return $row[0];
                }
                else
                    return -1;
            }
        }
        $db->close();


    }


    /**
     *  @name   setScoreMR
     *  @param  array $res answers list
     *  @param  array $correctAnswersArrU correct answers list
     *  @return array score per asnwer
     *  @descr  Set the right score for MR
     */
    private static function setScoreMR($res,$correctAnswersArrU){
        global $log;
        $NoWrong=$res['NoAnswers']-$res['NoCorrect'];

        //round(ceil(1/$res['NoCorrect'] * 10) / 10,2); //  to round up to 1dp

        $valueRight=round(1/$res['NoCorrect'], 1);



        //RISOLVERE I CASI IN CUI NON CI SONO RISPOSTE ERRATE
        if($NoWrong>0){
            $valueWrong=-round(1/$NoWrong, 1);
        }
        else{
                $valueWrong = 0;
                //$log->append("AAAAAAAAAAAAAAAAAAAAAA");
        }

        //$log->append("AAA".$res['NoAnswers']." ".$res['NoCorrect']." ".$NoWrong."  ".$valueRight." ".$valueWrong);

        $out='';
        for($i=0;$i<$res['NoAnswers'];$i++){

            $ACindex="Acorrect".$i;
            $Aindex = "Atext" . $i;

            //$log->append("XXX".$res[$Aindex][0]);

            $out[$ACindex]['score']=$valueWrong;

            foreach($correctAnswersArrU as $value){
                //$log->append("XXX------>".$value." ");
                if(strcmp($res[$Aindex][0],$value)==0)
                    $out[$ACindex]['score'] = $valueRight;

            }

        }

        return $out;


    }



    /**
     *  @name   checkNumber
     *  @param  array $arr answers list
     *  @return array $res numeri value
     *  @descr  check if list is composed by two numeric value in this case merge the int part with the decimal part
     */
    private static function checkNumber($arr){

        $res=array();
        if(count($arr)==2){
            if(is_numeric($arr[0]) and is_numeric($arr[1])){
                $num=implode('.',$arr);
                $res=explode(",", $num);
            }
            else
                $res=$arr;

        }
        else
            $res=$arr;


        return $res;
    }

    /**
     * @name stripLastBrTags
     * @param String $text
     * @param String $text
     */
    private static function stripLastBrTags($text){

        if(strlen($text)>=0){
            $brTag=substr($text,strlen($text)-4,4);
            if($brTag=='<br>' or $brTag=='<BR>'){
                $subtext=substr($text, 0,strlen($text)-4);
                return ImportQMController::stripLastBrTags($subtext);
            }
            else{
                return $text;
            }
        }
        else{
            return $text;
        }
    }
    /**
     *  @name   getQuestionText
     *  @param  simpleXMLObject $material answers list
     *  @return string  $res text
     *  @descr  check if list is composed by two numeric value in this case merge the int part with the decimal part
     */
    private static function getQuestionText($material){
        global $log;
        $res='';
        if(strcmp($material->getName(),'material')==0) {
            $mat=$material->children();
            if (strcmp($mat[0]->getName(), 'mattext') == 0) {
                $res= $mat[0];
                /*
                if(strpos($res,'en la cromatogra')!=false || strpos($res,'topicresources/1222601455/A3S3007_es.jpg')!=false)
                    $log->append('ZZZ' . $res);
                */

            }
            else if (strcmp($mat[0]->getName(), 'matimage') == 0) {
                $srcImg = $mat[0]['uri'];
                $heightImg = $mat[0]['height'];
                $widthImg = $mat[0]['width'];
                $res = "<img src='../../$srcImg' height='$heightImg' height='$widthImg' alt=''/> ";

            }


        }
        return $res;

    }

    /**
     * @name getLastSubject
     * @param String $item
     * @param String $lastIdTopic
     */
    private static function getLastSubject($lang){

        $db=new sqlDB();
        if($db->qSelect("Languages","alias",$lang)){
            if($row = $db->nextRowEnum()){
                return $row[0];
            }
            else
                return -1;
        }else{
            //die($db->getError());
            return -1;
        }
        $db->close();
    }



    /**
     *  @name   accessRules
     *  @descr  Returns all access rules for Login controller's actions:
     *  array(
     *     array(
     *       (allow | deny),                                     Parameter
     *       'actions' => array('*' | 'act1', ['act2', ....]),   Actions
     *       'roles'   => array('*' | '?' | 'a' | 't' | 's')     User's Role
     *     ),
     *  );
     */
    private function accessRules(){
        return array(
            array(
                'allow',
                'actions' => array('Init','Preview','Import','Importpage'
                ),
                'roles'   => array('a'),
            ),

            array(
                'deny',
                'actions' => array('*'),
                'roles'   => array('*'),
            ),
        );
    }

}
