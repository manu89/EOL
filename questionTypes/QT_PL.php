<?php
/**
 * File: QT_PL.php
 * User: Gmarsi
 * Date: 15/03/2015
 * Time: 12:41
 * Desc:
 */

class QT_PL extends Question {



    public function createNewQuestion()
    {
        $idQuestion = parent::createNewQuestion();
        return $idQuestion;
    }

    public function printQuestionEditForm($action, $readonly)
    {
        global $config, $user;

        $db = new sqlDB();
        if (!($db->qSelect('Languages')) || !($this->allLangs = $db->getResultAssoc('idLanguage'))) {
            die($db->getError());
        }
        ?>
        <ul xmlns="http://www.w3.org/1999/html">
            <li><a id="questionTab" href="#question-tab"><?= ttQuestion ?></a></li>
            <?php if ($action == 'show') echo '<li><a id="subTab" href="#subquestions">' . ttQuestionsubPL . '</a></li>'; ?>




        </ul>

        <div id="question-tab">

            <!-- Print question language tabs and textareas user by ckeditor -->
            <?php $this->printQuestionTabsAndTextareas($action); ?>

            <!-- Print question's extras list -->
            <?php $this->printQuestionExtraForm($action); ?>

            <div class="clearer bSpace"></div>

            <!-- Print hidden field for question's type (PL) -->
            <input type="hidden" id="questionType" value="PL">

            <!-- Print all other question's info -->
            <?php $this->printQuestionInfoEditForm($action, $readonly) ?>

            <div class="clearer bSpace"></div>

            <!-- Print buttons for question panel -->
            <?php $this->printQuestionEditButtons($action, $readonly);?>


            <div class="clearer"></div>

        </div>


  <?php if ($action == 'show') {?>

        <div id="subquestions">

            <div class="bSpace" id="subquestionsTableContainer">
                <div class="smallButtons">
                    <div id="newSubquestion_PL">
                        <img class="icon" src="<?= $config['themeImagesDir'] . 'new.png' ?>"/><br/>
                        <?= ttNew ?>
                    </div>
                </div>

                <?php $this->printSubquestionsTable($this->get('idQuestion'), $_SESSION['idSubject']) ?>

            </div>

            <div class="clearer"></div>
            <a class="button normal left rSpace tSpace" onclick="closeQuestionInfo(true);"><?= ttExit ?></a>
            <div class="clearer"></div>
        </div>
    <?php

    }
        $this->printQuestionTypeLibrary();
        echo '<script> initialize_PL(); </script>';
   ?>
        <div id="answers">

            <div class="bSpace" id="answersTableContainer">
                <div class="smallButtons">
                    <div id="newAnswer_PL">
                        <img class="icon" src="<?= $config['themeImagesDir'].'new.png' ?>"/><br/>
                        <?= ttNew ?>
                    </div>
                </div>
                <?php $this->printAnswersTable($this->get('idQuestion'), $_SESSION['idSubject']) ?>

            </div>


        </div>

<?php  }

 public function printSubquestionsTable($idQuestion){ ?>

        <table id="subquestionsTable" class="stripe hover">
            <thead>
            </thead>
            <tbody>
            <?php
            $db = new sqlDB();
            if($db->qsubquestionsetPL($idQuestion)){
                while($sub_questions = $db->nextRowAssoc()){
                    echo '<tr>

                              <td>'.strip_tags($sub_questions['text']).'</td>


                          </tr>';
                }
            }
            ?>
            </tbody>
        </table>

    <?php
    }
    public function printAnswersTable($idQuestion, $idSubject){ ?>

        <table id="answersTable" class="stripe hover">
            <thead>
            </thead>
            <tbody>
            <?php
            $db = new sqlDB();
            if($db->qAnswerSet($idQuestion, null, $idSubject)){
                while($answer = $db->nextRowAssoc()){
                    echo '<tr>

                              <td>'.strip_tags($answer['translation']).'</td>
                              <td>'.$answer['idAnswer'].'</td>
                          </tr>';
                }
            }
            ?>
            </tbody>
        </table>

    <?php
    }


    /**
     *
     */
    public function printQuestionPreview()
    {
        global $log;
        global $config;
        $info = null;
        $db = new sqlDB();

       //echo $this->getRAsspc(0,'sub_questions')." ".$this->getRAsspc(0,'fkLanguage')." ".$_SESSION['idSubject'];

        if (($db->qAnswerSetPL($this->getRA('sub_questions'), $this->getRA('fkLanguage'), $_SESSION['idSubject'])) && ($answerSet = $db->getResultAssoc())) {
            $questionAnswers = '';
            //var_dump($answerSet);


            // -------  Add extra buttons  ------- //
            $extra = '';
            if (strpos($this->get('extra'), 'c') !== false)
                $extra .= '<img class="extraIcon calculator" src="' . $config['themeImagesDir'] . 'QEc.png' . '">';
            if (strpos($this->get('extra'), 'p') !== false)
                $extra .= '<img class="extraIcon periodicTable" src="' . $config['themeImagesDir'] . 'QEp.png' . '">';
            ?>

        <div class="questionTest" value="<?= $this->getRAsspc(0,'translation') ?>" type="PL">
            <div class="questionText"><?= $this->getRAsspc(0,'translation') ?></div>
            <?php

            for ($i = 0; $i < count($this); $i++) {
                ?>
                    <div class="questionAnswers"><?= $this->getRAsspc($i,'text') ?>
                    <?php
                        ?><select>
                            <?php

                            foreach ($answerSet as $answer) {


                                if ($answer['fkLanguage'] != $this->getRAsspc($i,'fkLanguage'))
                                    $class = 'mainLang';

                                $questionAnswers .='<div>


                                        <option value="'.$answer['idAnswer'].'">'.$answer['translation'].'</option>

                                     </div>';
                            }
                                echo $questionAnswers;


                            ?>
                        </select></div>

            <?php
            }

        }

            }






    public function printQuestionInTest($idSubject, $answered, $extras){
        global $config;
        global $log;

        $db = new sqlDB();

        if(($db->qAnswerSet($this->get('idQuestion'), $this->get('fkLanguage'), $idSubject)) && ($answerSet = $db->getResultAssoc())){
    $questionAnswers = '';

            if(($db-> qsubquestionsettestPL($this->get('idQuestion')))  && ($subSet = $db->nextRowAssoc())) {


                // -------  Add extra buttons  ------- //
                $extra = '';
                if (strpos($this->get('extra'), 'c') !== false)
                    $extra .= '<img class="extraIcon calculator" src="' . $config['themeImagesDir'] . 'QEc.png' . '">';
                if (strpos($this->get('extra'), 'p') !== false)
                    $extra .= '<img class="extraIcon periodicTable" src="' . $config['themeImagesDir'] . 'QEp.png' . '">';
                ?>


               <div class="questionTest" value="<?= $this->get('idQuestion') ?>" type="PL">
                <div class="questionText"><?= $this->get('translation') ?></div>

                <?php

                for ($i = 0; $i < count($this); $i++) {


                    ?>


                    <div class="questionAnswers"><?= $subSet['text']?>


                <select id="prova">
                            <?php

                            foreach ($answerSet as $answer) {


                                if ($answer['fkLanguage'] != $this->getRAsspc($i, 'fkLanguage'))
                                    $class = 'mainLang';

                                $questionAnswers .='<div>


                                        <option id="prova" value="'.$answer['idAnswer'].'">'.$answer['translation'].'</option>

                                     </div>';

                            }

                            echo $questionAnswers;
                            ?>
                   </select>
                    </div>

                <?php
                }
            }
}
}


    public function printQuestionInCorrection($idSubject, $answered, $scale, $lastQuestion)
    {
        global $config;

        $questionAnswers = '';
        $questionScore = 0;
        $questionClass = 'emptyQuestion';
        $scale=1;
        $db = new sqlDB();
        if ($db->qAnswerSet($this->get('idQuestion'), null, $idSubject) && ($answerSet = $db->getResultAssoc('idAnswer'))) {

                foreach ($answerSet as $idAnswer => $answer) {



                    $answerdClass = "";
                    $right_wrongClass = ($answer['score'] > 0) ? 'rightAnswer' : 'wrongAnswer';
                    if (in_array($idAnswer, $answered)) {
                        $questionScore += round(($answer['score'] * 1),1);
                        $answerdClass = 'answered';



                    }
                    $questionAnswers .= '<div class="' . $answerdClass . '">
                                         <span value="' . $idAnswer . '" class="responseMR ' . $right_wrongClass . '"></span>
                                         <label>' . $answer['translation'] . '</label>
                                         <label class="score">' . round($answer['score'] * 1,1) . '</label>
                                     </div>';
                }
                $questionAnswers .= '<label class="questionScore">' . $questionScore . '</label>
                                 <div class="clearer"></div>';

                if (count($answered) != 0)
                    $questionClass = ($questionScore > 0) ? 'rightQuestion' : 'wrongQuestion';
                ?>

            <div class="questionTest <?= $questionClass . ' ' . $lastQuestion ?>" value="<?= $this->get('idQuestion') ?>" type="PL">
                <div class="questionText" onclick="showHide(this);">
                    <span class="responseQuestion"></span>
                    <?= $this->get('translation') ?>
                    <span class="responseScore"><?= number_format($questionScore, 1); ?></span>
                </div>
                <div class="questionAnswers hidden"><?= $questionAnswers ?></div>
            </div>

            <?php
            } else {
                die(ttEAnswers);
            }
        }


    public function printQuestionInView($idSubject, $answered, $scale, $lastQuestion){
        $this->printQuestionInCorrection($idSubject, $answered, $scale, $lastQuestion);
    }
}