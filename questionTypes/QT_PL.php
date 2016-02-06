<?php
/**
 * File: QT_PL.php
 * User: Gmarsi
 * Date: 15/03/2015
 * Time: 12:41
 * Desc:
 */

class QT_PL extends Question
{


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


        <?php if ($action == 'show') { ?>

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



        <div id="answers">

            <div class="bSpace" id="answersTableContainer">
                <div class="smallButtons">
                    <div id="newAnswer_PL">
                        <img class="icon" src="<?= $config['themeImagesDir'] . 'new.png' ?>"/><br/>
                        <?= ttNew ?>
                    </div>
                </div>
                <?php global $log;
                $log->append($this->get('idQuestion')) ?>
                <?php $this->printAnswersTable($this->get('idQuestion'), $_SESSION['idSubject']) ?>

            </div>


        </div>

    <?php }

        $this->printQuestionTypeLibrary();
        echo '<script> initialize_PL(); </script>';

    }
    public function printSubquestionsTable($idQuestion)
    { ?>

        <table id="subquestionsTable" class="stripe hover">
            <thead>
            </thead>
            <tbody>
            <?php
            global $log;
            $db = new sqlDB();
            if (($db->qsubquestionsettestPL($this->get('idQuestion'))) && ($subSet = $db->getResultAssoc())) {

            }

            for ($a = 0; $a < count($subSet); $a++) {
                echo '<tr>
                    <td>' . ($subSet[$a]['text']) . '</td>
                            <td>' . ($subSet[$a]['text']) . '</td>
                            <td>' . ($subSet[$a]['sub_questions']) . '</td>

                      </tr>';


            }?>
            </tbody>
        </table>

    <?php
    }

    public function printAnswersTable($idQuestion, $idSubject)
    {
        ?>

        <table id="answersTable" class="stripe hover">
            <thead>
            </thead>
            <tbody>
            <?php
           $a=0;
            $db = new sqlDB();
            if ($db->qAnswerSet($idQuestion, null, $idSubject)) {



                while ($answer = $db->nextRowAssoc()) {
                 
                    echo '<tr >

                              <td>' . strip_tags($answer['translation']) . '</td>

                              <td>' . $answer['score'] . '</td>


                          </tr>'; }




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
        ?>
  <div class="questionTest" value="<?= $this->getRAsspc(0, 'translation') ?>" type="PL"></div>
        <div class="questionText"><?= $this->getRAsspc(0, 'translation') ?></div>
        <div class="questionAnswers">

                <?php

        for ($a =0;$a <= count($this); $a++) {


            if (($db->qAnswerSetPL($this->getRAsspc($a,'sub_questions'), $this->getRA('fkLanguage'), $_SESSION['idSubject'])) && ($answerSet = $db->getResultAssoc())) {

                $questionAnswers = '';


                // -------  Add extra buttons  ------- //
                $extra = '';
                if (strpos($this->get('extra'), 'c') !== false)
                    $extra .= '<img class="extraIcon calculator" src="' . $config['themeImagesDir'] . 'QEc.png' . '">';
                if (strpos($this->get('extra'), 'p') !== false)
                    $extra .= '<img class="extraIcon periodicTable" src="' . $config['themeImagesDir'] . 'QEp.png' . '">';
                ?>





                <?= $this->getRAsspc($a, 'text')

                ?><select>
                <?php


                foreach ($answerSet as $answer) {


                    if ($answer['fkLanguage'] != $this->getRAsspc($a, 'fkLanguage'))
                        $class = 'mainLang';

                    $questionAnswers .= '<div>


                                        <option value="' . $answer['idAnswer'] . '">' . $answer['translation'] . '</option>

                                     </div>';
                }
                echo $questionAnswers;


                ?>

                </div>
                </select>
                <br/>

            <?php
            }


        }
    }







    public function printQuestionInTest($idSubject, $answered, $extras)
    {
        global $config;
        global $log;

        $db = new sqlDB();
        if (($db->qsubquestionsettestPL($this->get('idQuestion'))) && ($subSet = $db->getResultAssoc())) {
            // var_dump($subSet);
        }


        ?>

        <div class="questionTest" value="<?= $this->get('idQuestion') ?>" type="PL">
            <div class="questionText"><?= $this->get('translation') ?></div>


            <div class="questionAnswers">
                <?php
                for ($a = 0; $a <= count($this); $a++) {


                if (($db->qAnswerSetPL($subSet[$a]['sub_questions'], $this->getRA('fkLanguage'))) && ($answerSet = $db->getResultAssoc())) {

                $questionAnswers = '';




                // -------  Add extra buttons  ------- //
                $extra = '';
                if (strpos($this->get('extra'), 'c') !== false)
                    $extra .= '<img class="extraIcon calculator" src="' . $config['themeImagesDir'] . 'QEc.png' . '">';
                if (strpos($this->get('extra'), 'p') !== false)
                    $extra .= '<img class="extraIcon periodicTable" src="' . $config['themeImagesDir'] . 'QEp.png' . '">';
                ?>






                <?= $subSet[$a]['text']?>


                <select id="prova">
                    <?php

                    foreach ($answerSet as $answer) {


                        if ($answer['fkLanguage'] != $this->getRAsspc($a, 'fkLanguage'))
                            $class = 'mainLang';

                        $questionAnswers .= '<div>


                                        <option id="prova" value="' . $answer['idAnswer'] . '">' . $answer['translation'] . '</option>

                                     </div>';

                    }

                    echo $questionAnswers;

                    }

                    ?>


            </div>

            </select>
            <br/>
            <?php
            } ?>

        </div>


    <?php
    }




    public
    function printQuestionInCorrection($idSubject, $answered, $scale, $lastQuestion)
    {
        global $config;



        $db = new sqlDB();
        if (($db->qsubquestionsettestPL($this->get('idQuestion'))) && ($subSet = $db->getResultAssoc())) {
            // var_dump($subSet);
        }
        for ($a = 0; $a <= count($this); $a++) {
            $questionAnswers = "";
            $questionClass = 'emptyQuestion';
            $questionScore = 0;
            if (($db->qAnswerSetPL($subSet[$a]['sub_questions'], $this->getRA('fkLanguage'))) && ($answerSet = $db->getResultAssoc('idAnswer'))) {
                //var_dump($answerSet);
                foreach ($answerSet as $idAnswer => $answer) {
                    //var_dump($idAnswer);
                    //var_dump($answer);
                    $answerdClass = "";

                    $right_wrongClass = ($answer['score'] > 0) ? 'rightAnswer' : 'wrongAnswer';
                    if (in_array($idAnswer, $answered)) {
                        $questionScore += round(($answer['score'] * $scale), 1);
                        $answerdClass = 'answered';
                    }

                    $questionAnswers .= '<div class="' . $answerdClass . '">
                                         <span value="' . $idAnswer . '" class="responsePL ' . $right_wrongClass . '"></span>
                                         <label>' . $answer['translation'] . '</label>
                                         <label class="score">' . round($answer['score'] * $scale, 1) . '</label>
                                     </div>';
                }

                $questionAnswers .= '<label class="questionScore">' . $questionScore . '</label>
                                 <div class="clearer"></div>';

                if (count($answered) != 0)
                    $questionClass = ($questionScore > 0) ? 'rightQuestion' : 'wrongQuestion';
                ?>

                <div class="questionTest <?= $questionClass . ' ' . $lastQuestion ?>"
                     value="<?= $this->get('idQuestion') ?>" type="PL">
                    <div class="questionText" onclick="showHide(this);">
                        <span class="responseQuestion"></span>
                        <?= $this->get('translation') ?>
                        <span class="responseScore"><?= number_format($questionScore, 1); ?></span>
                        <br/>
                        <?php $b = $a + 1;
                        print("Sottodomanda n." . $b); ?>    <br/> <?= $subSet[$a]['text'] ?>
                        <br/>
                        <br/>
                    </div>
                    <div class="questionAnswers hidden">
                        <?php
                        print("Risposte"); ?>
                        <br/>
                        <br/>
                        <?= $questionAnswers ?></div>
                </div>

            <?php

            }
        }
    }


    public function printQuestionInView($idSubject, $answered, $scale, $lastQuestion)
    {
        $this->printQuestionInCorrection($idSubject, $answered, $scale, $lastQuestion);
    }
}