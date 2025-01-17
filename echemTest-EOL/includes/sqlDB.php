<?php
/**
 * File: sqlDB.php
 * User: Masterplan
 * Date: 3/15/13
 * Time: 12:09 PM
 * Desc: Interface class for MySql database
 */

class sqlDB {

    private $dbHost;
    private $dbPort;
    private $dbName;
    private $dbUsername;
    private $dbPassword;
    public $mysqli;
    private $active = false;
    private $error;
    public 	$result;
	private $result2;

    /**
     * @name    sqlDB
     * @descr   Creates a sqlDB object
     */
    public function sqlDB(){

        global $config;

        $this->dbHost = $config['dbHost'];
        $this->dbPort = $config['dbPort'];
        $this->dbName = $config['dbName'];
        $this->dbUsername = $config['dbUsername'];
        $this->dbPassword = $config['dbPassword'];
    }

/*******************************************************************
*                              Login                               *
*******************************************************************/

    /**
     * @name    qLogin
     * @param   $email      String  Login email
     * @param   $password   String  Login password
     * @return  array|null  User's informations
     * @descr   Define and execute queries for login
     */
    public function qLogin($email, $password){
        $mysqli = $this->connect();

        $query = "SELECT idUser, name, surname, email, password, role, alias
                  FROM Users
                  JOIN Languages
                      ON fkLanguage = idLanguage
                  WHERE
	                  email = ?
                      AND
                      password = ?";

        // Prepare statement
        if (!($stmt = $mysqli->prepare($query))) {
            echo 'Prepare failed: (' . $mysqli->errno . ') ' . $mysqli->error;
        }
        // Binding parameters
        if (!$stmt->bind_param('ss', $email, $password)) {
            echo 'Binding parameters failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        // Execute query
        if (!$stmt->execute()) {
            echo 'Execute failed: (' . $stmt->errno . ') ' . $stmt->error;
        }
        $stmt->bind_result($i, $n, $s, $e, $p, $r, $l);
        if($stmt->fetch()){
            $result = array(
                'id'        => $i,
                'name'      => $n,
                'surname'   => $s,
                'email'     => $e,
                'lang'      => $l,
                'role'      => $r);
            // AGGIUNTA DAMIANO LOGIN TIME lastLogin
            /*
            $dt = new DateTime(); 
            $dt=$dt->format('Y-m-d H:i:s');
            $this->mysqli = $this->connect();
            $query = "UPDATE Users SET `lastLogin` = '$dt' WHERE `idUser` = 1";
            $this->execQuery($query);
            */
        }else{
            $result = null;
        }

        $mysqli->close();
        return $result;
    }

/*******************************************************************
*                            Subjects                              *
*******************************************************************/

    /**
     * @name    qSubject
     * @param   $idUser       String        Logged user's ID
     * @param   $role         String        Logged user's role
     * @return  Boolean
     * @descr   Get a list of subjects
     */
    public function qSubjects($idUser, $role){
        global $log;

        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            if(($role == 'e') || ($role == 'er') || ($role == 't') || ($role == 'at')){
                $query = "SELECT *
                          FROM
                              Subjects
                          WHERE
                              idSubject IN (
                                  SELECT fkSubject
                                  FROM
                                      Users_Subjects
                                  WHERE
                                      fkUser = '$idUser'
                              )
                          ORDER BY name";
                $this->execQuery($query);
            }else{
                $query = "SELECT *
                          FROM
                              Subjects
                          ORDER BY name";
                $this->execQuery($query);
            }
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateSubjectInfo
     * @param   $idSubject       String        Requested Subject's ID
     * @param   $name            String        Subject's name
     * @param   $desc            String        Subject's description
     * @param   $teachers        Array         Assigned teachers
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateSubjectInfo($idSubject, $name, $desc, $teachers){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($name, $desc));
            $queries = array();
            $query = "UPDATE Subjects
                      SET
                          name = '$data[0]',
                          description = '$data[1]'
                      WHERE
                          idSubject = '$idSubject'";
            array_push($queries, $query);
            $query = "DELETE
                      FROM Users_Subjects
                      WHERE
                          fkSubject = '$idSubject'";
            array_push($queries, $query);
            if(count($teachers) > 0){
                $query = "INSERT INTO Users_Subjects (fkUser, fkSubject)
                          VALUES ";
                foreach($teachers as $teacher){
                    $query .= "('$teacher', '$idSubject'),\n";
                }
                $query = substr_replace($query , '', -2);
                array_push($queries, $query);
            }
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewSubject
     * @param   $name          String        Subject's name
     * @param   $desc          String        Subject's description
     * @param   $lang          String        Subject's main language
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qNewSubject($name, $desc, $lang,$vers){
        global $log, $user;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            $data = $this->prepareData(array($name, $desc, $lang,$vers));
            $query = "INSERT INTO Subjects (name, description, fkLanguage,version)
                      VALUES ('$data[0]', '$data[1]', '$data[2]', '$data[3]')";
            array_push($queries, $query);
            $query = "SET @subID = LAST_INSERT_ID()";
            array_push($queries, $query);
            $query = "INSERT INTO Users_Subjects (fkUser , fkSubject)
                      VALUES ('$user->id', @subID)";
            array_push($queries, $query);
            $query = "SELECT @subID";
            array_push($queries, $query);
            $this->execTransaction($queries);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }











    /**
     * @name    qDeleteSubject
     * @param   $idSubject         String        Subject's ID
     * @return  Boolean
     * @descr   Returns true if subject and all its related was successfully deleted, false otherwise
     */
    public function qDeleteSubject($idSubject){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "DELETE FROM Subjects
                      WHERE idSubject = '$idSubject'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                              Topics                              *
*******************************************************************/

    /**
     * @name    qUpdateTopicInfo
     * @param   $idTopic        String        Requested Topic's ID
     * @param   $name           String        Topic's name
     * @param   $desc           String        Topic's description
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateTopicInfo($idTopic, $name, $desc){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($idTopic, $name, $desc));
            $query = "UPDATE Topics
                      SET
                          name = '$data[1]',
                          description = '$data[2]'
                      WHERE
                          idTopic = '$data[0]'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qGetEditAndDeleteConstraints
     * @param   $action         String          Constraint's action
     * @param   $table          String          Constraint's table
     * @param   $params         Array           Constraint's array values
     * @return  Boolean
     * @descr   Gets the list of costraints
     */
    public function qGetEditAndDeleteConstraints($action, $table, $params){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "";
            switch($action){
                case "delete" :
                    switch($table){
                        case "topic" :
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                          JOIN TestSettings ON fkTestSetting = idTestSetting
                                      WHERE
                                          fkQuestion IN (SELECT idQuestion
                                                         FROM Questions
                                                         WHERE
                                                             fkTopic = '".$params[0]."')
                                      GROUP BY idTestSetting"; break;
                        case "question1" :          // Check if question is in test settings
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                      JOIN
                                          TestSettings ON idTestSetting = fkTestSetting
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      GROUP BY idTestSetting"; break;
                        case "question2" :          // Check if question is in History or Sets_Questions
                            $query = "SELECT fkQuestion
                                      FROM
                                          History
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      UNION
                                      SELECT fkQuestion
                                      FROM
                                          Sets_Questions
                                      WHERE
                                          fkQuestion = '$params[0]'"; break;
                        case "answer1" :          // Check if question is in test settings
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                      JOIN
                                          TestSettings ON idTestSetting = fkTestSetting
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      GROUP BY idTestSetting"; break;
                        case "answer2" :          // Check if question is in History or Sets_Questions
                            $query = "SELECT fkQuestion
                                      FROM
                                          History
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      UNION
                                      SELECT fkQuestion
                                      FROM
                                          Sets_Questions, Sets
                                      WHERE
                                          fkQuestion = '$params[0]'
                                          AND
                                          assigned = 'y'"; break;
                    }; break;
                case "edit" :
                    switch($table){
                        case "testsetting" :
                            $query = "SELECT *
                                      FROM Exams
                                      WHERE
                                          status != 'a'
                                          AND
                                          fkTestSetting = '".$params[0]."'"; break;
                        case "question1" :
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                      JOIN
                                          TestSettings ON idTestSetting = fkTestSetting
                                      WHERE
                                          fkQuestion = '$params[0]';"; break;
                        case "question2" :
                            $query = "SELECT fkQuestion
                                      FROM
                                          History
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      UNION
                                      SELECT fkQuestion
                                      FROM
                                          Sets_Questions, Sets
                                      WHERE
                                          fkQuestion = '$params[0]'
                                          AND
                                          assigned = 'y'"; break;
                        case "answer1" :
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                      JOIN
                                          TestSettings ON idTestSetting = fkTestSetting
                                      WHERE
                                          fkQuestion = '$params[0]';"; break;
                        case "answer2" :
                            $query = "SELECT fkQuestion
                                      FROM
                                          History
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      UNION
                                      SELECT fkQuestion
                                      FROM
                                          Sets_Questions, Sets
                                      WHERE
                                          fkQuestion = '$params[0]'
                                          AND
                                          assigned = 'y'"; break;
                    } break;
                case 'create' :
                    switch($table){
                        case "answer1" :
                            $query = "SELECT *
                                      FROM
                                          Questions_TestSettings
                                      JOIN
                                          TestSettings ON idTestSetting = fkTestSetting
                                      WHERE
                                          fkQuestion = '$params[0]';"; break;
                        case "answer2" :
                            $query = "SELECT fkQuestion
                                      FROM
                                          History
                                      WHERE
                                          fkQuestion = '$params[0]'
                                      UNION
                                      SELECT fkQuestion
                                      FROM
                                          Sets_Questions, Sets
                                      WHERE
                                          fkQuestion = '$params[0]'
                                          AND
                                          assigned = 'y'"; break;
                    } break;
            }
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteTopic
     * @param   $idTopic         String        Topic's ID
     * @return  Boolean
     * @descr   Returns true if topic and all its related was successfully deleted, false otherwise
     */
    public function qDeleteTopic($idTopic){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{

            $query = "CREATE VIEW questionstoflag AS
                          (SELECT idQuestion
                          FROM History
                              JOIN Questions ON idQuestion = fkQuestion
                          WHERE
                              fkTopic = '$idTopic'
                              AND
                              idQuestion != 'd'
                          GROUP BY idQuestion)";
            array_push($queries, $query);
            $query = "UPDATE Questions
                      SET
                          status = 'd'
                      WHERE
                          idQuestion IN (SELECT idQuestion
                                         FROM
                                             questionstoflag)";
            array_push($queries, $query);
            $query = "DELETE FROM Questions
                      WHERE
                          fkTopic = '$idTopic'
                          AND
                          idQuestion NOT IN (SELECT idQuestion
                                             FROM
                                                 questionstoflag)";
            array_push($queries, $query);
            $query = "DROP VIEW questionstoflag";
            array_push($queries, $query);
            $query = "DELETE FROM Topics
                      WHERE
                          idTopic = '$idTopic'";
            array_push($queries, $query);

            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewTopic
     * @param   $idSubject      String        Subject's ID
     * @param   $name           String        Topic's name
     * @param   $desc           String        Topic's description
     * @return  Boolean
     * @descr   Returns true if topic was saved created, false otherwise
     */
    public function qNewTopic($idSubject, $name, $desc){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($name, $desc));
            $query = "INSERT INTO Topics (name, description, fkSubject)
                  VALUES ('$data[0]', '$data[1]', '$idSubject')";
            array_push($queries, $query);
            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }




    /**
     * @name    qNewTopicV2
     * @param   $idSubject      String        Subject's ID
     * @param   $name           String        Topic's name
     * @param   $code           String        Topic's unique code
     * @param   $desc           String        Topic's description
     * @return  Boolean
     * @descr   Returns true if topic was saved created, false otherwise
     */
    public function qNewTopicV2($idSubject, $name, $code, $desc){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($name, $code, $desc));
            $query = "INSERT INTO Topics (name, code, description, fkSubject)
                  VALUES ('$data[0]', '$data[1]', '$data[2]', '$idSubject')";
            array_push($queries, $query);
            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                            Questions                             *
*******************************************************************/

    /**
     * @name    qQuestions
     * @param   $idSubject       String        Subject's ID
     * @param   $idTopic         String        Topic's ID
     * @param   $idQuestion      String        Question's ID
     * @param   $idLanguage      String        Question's language
     * @return  Boolean
     * @descr   Get questions info by subject ID, topic ID or question ID
     */
    public function qQuestions($idSubject, $idTopic, $idQuestion = null, $idLanguage = null){
        global $log;
        $ack = true;
        $this->result = null;

        try{
            if($idQuestion == null){
                $this->mysqli = $this->connect();
                $topics = array();
                if($idTopic == '-1'){                         // No topic selected => Show all subject's questions
                    $query = "SELECT idTopic
                              FROM
                                  Topics
                              WHERE
                                  fkSubject = '$idSubject'";
                    $this->execQuery($query);
                    while($row = $this->nextRowAssoc()){
                        array_push($topics, $row['idTopic']);
                    }
                }else{
                    array_push($topics, $idTopic);
                }
                if(count($topics) > 0){ // DAMIANO CONTROLLA PERCHE STATUS ERA DIVERSO DA D E NON UGUALE A
                    $query = "SELECT idQuestion, status, translation, type, difficulty, fkLanguage, idTopic, name, shortText
                              FROM Questions
	                              JOIN TranslationQuestions ON idQuestion = fkQuestion
	                              JOIN Topics ON idTopic = fkTopic
                              WHERE
                                  status != 'd'
                                  AND
                                  fkTopic IN (".implode(',', $topics).")";
                    if($idLanguage != null)
                        $query .= " AND fkLanguage = '$idLanguage'";
                    $query .= " ORDER BY idQuestion";
                }
            }else{                                             // Returns info about only one question
                $query = "SELECT *
                          FROM Questions
                          WHERE
                              idQuestion = '$idQuestion'";
                if($idLanguage != null)
                    $query .= " AND fkLanguage = '$idLanguage'";
            }
            $this->mysqli = $this->connect();
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qCountQuestionPerTopic
     * @descr   Get questions info by subject ID, topic ID or question ID
     */
    public function qCountQuestionPerTopic($idTopic,$difficulty, $idLanguage = null){
        global $log;
        $ack = true;
        $this->result = null;
        try{
            $query = "SELECT count(*) as maxQuestions
                      FROM Questions
                          JOIN TranslationQuestions ON idQuestion = fkQuestion
                          JOIN Topics ON idTopic = fkTopic
                      WHERE
                          status = 'a'
                          AND
                          difficulty = $difficulty
                          AND
                          fkTopic = $idTopic";
            if($idLanguage != null)
                $query .= " AND fkLanguage = '$idLanguage'";

            $this->mysqli = $this->connect();
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }



    /**
     * @name    qQuestionInfo
     * @param   $idQuestion         String        Question's ID
     * @param   $idLanguage         String        Question's language ID
     * @return  Boolean
     * @descr   Get infos about selected question
     */
    public function qQuestionInfo($idQuestion, $idLanguage = null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Questions
                      	  JOIN Topics ON idTopic = fkTopic
                      	  JOIN TranslationQuestions ON idQuestion = fkQuestion
                      	  JOIN Languages ON idLanguage = fkLanguage
                      WHERE
                          idQuestion = '$idQuestion'";
            if($idLanguage != null)
                $query .= " AND fkLanguage = '$idLanguage'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
    //controllare nome funzione
    public function qQsuestionInfo($idQuestion, $idLanguage = null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Questions
                      	  JOIN Topics ON idTopic = fkTopic
                      	  JOIN TranslationQuestions ON idQuestion = fkQuestion
                      	  JOIN Languages ON idLanguage = fkLanguage
                          JOIN Sub_questions ON fkQuestions=idQuestion
                      WHERE
                          idQuestion = '$idQuestion'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewQuestion
     * @param   $idTopic            String        Topic's ID
     * @param   $type               String        Question's type
     * @param   $difficulty         String        Question's difficulty
     * @param   $extras             String        Question's extras
     * @param   $shortText          String        Question's difficulty
     * @param   $translationsQ      Array         Question's translations
     * @return  Boolean
     * @descr   Update all questions details (infos and translations)
     */
    public function qNewQuestion($idTopic, $type, $difficulty, $extras, $shortText, $translationsQ){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($type, $difficulty, $extras, $shortText));
            $query = "INSERT INTO Questions (type, difficulty, extra, shortText, fkTopic)
                      VALUES ('$data[0]', '$data[1]', '$data[2]', '$data[3]','$idTopic')";
            array_push($queries, $query);
            $query = "UPDATE Questions
                      SET
                          fkRootQuestion = LAST_INSERT_ID()
                      WHERE
                          idQuestion = LAST_INSERT_ID()";
            array_push($queries, $query);
            $query = "INSERT INTO TranslationQuestions(fkQuestion,fkLanguage,translation)
                      VALUES ";
            foreach($translationsQ as $idLanguage => $translation){
                if($translation != null){
                    $data = $this->prepareData(array($translation));
                    $query .= "(LAST_INSERT_ID(), '$idLanguage', '$data[0]'),\n";
                }
            }
            $query = substr_replace($query , '', -2);       // Remove last coma
            array_push($queries, $query);
            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qGetSettingsOnNewQuestion
     * @param   $idTopic            String        Topic's ID
     * @return  Boolean
     * @descr   Get all the test settings related on a dated topic
     */
    public function qGetSettingsOnNewQuestion($idTopic){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "SELECT idTestSetting 
                      FROM Topics JOIN Subjects ON Topics.fkSubject = Subjects.idSubject
                      JOIN TestSettings ON Subjects.idSubject = TestSettings.fkSubject
                      WHERE Topics.idTopic = '$idTopic'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qInsertQuestionsDistributionOnNewQuestion
     * @return  Boolean
     * @descr   Insert into questions distribution the new question
     */
    public function qInsertQuestionsDistributionOnNewQuestion($idTestSetting,$idQuestion){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "INSERT INTO questionsdistribution (fkTestsetting, fkQuestion, counter)
                          VALUES('$idTestSetting','$idQuestion',0)";
            $this->execQuery($query);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qChangeQuestionStatus
     * @param   $idQuestion         String        Question's ID
     * @param   $status             String        Question's status
     * @return  Boolean
     * @descr   Change question's status
     */
    public function qChangeQuestionStatus($idQuestion, $status){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "UPDATE Questions
                      SET
                          status = '$status'
                      WHERE
                          idQuestion = '$idQuestion'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDuplicateQuestion
     * @param   $idQuestion             String        Question's ID
     * @param   $updateMandatory        Boolean       If true update question's ID on Questions_TestSettings table
     * @param   $idAnswerToEdit         String        ID of answer to delete or edit
     * @return  Boolean
     * @descr   Duplicates question and its answers with all translations
     */
    public function qDuplicateQuestion($idQuestion, $updateMandatory, $idAnswerToEdit=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $query = "INSERT INTO Questions (type, difficulty, status, extra, shortText, fkRootQuestion, fkTopic)
                      SELECT type, difficulty, status, extra, shortText, fkRootQuestion, fkTopic
                      FROM
                          Questions
                      WHERE
                          idQuestion = '$idQuestion'";
            array_push($queries, $query);
            $query = "SET @questID = LAST_INSERT_ID()";
            array_push($queries, $query);
            $query = "UPDATE Questions
                      SET
                          status = 'd'
                      WHERE
                          idQuestion = '$idQuestion'";
            array_push($queries, $query);
            $query = "INSERT INTO TranslationQuestions
                      SELECT @questID, fkLanguage, translation
                      FROM TranslationQuestions
                      WHERE
                          fkQuestion = '$idQuestion'";
            array_push($queries, $query);
            $query = "SELECT *
                      FROM
                          Answers
                      WHERE
                          fkQuestion = '$idQuestion'";
            $this->execQuery($query);
            while($answers = $this->nextRowAssoc()){
                $query = "INSERT INTO Answers (score, fkQuestion)
                          VALUES ('".$answers['score']."', @questID)";
                array_push($queries, $query);
                $query = "SET @aswrID = LAST_INSERT_ID()";
                array_push($queries, $query);
                $query = "INSERT INTO TranslationAnswers
                          SELECT @aswrID, fkLanguage, translation
                          FROM TranslationAnswers
                          WHERE
                              fkAnswer = '".$answers['idAnswer']."'";
                array_push($queries, $query);
                if($idAnswerToEdit == $answers['idAnswer']){
                    $query = "SET @newAswrID = @aswrID";
                    array_push($queries, $query);
                }
            }
            if($updateMandatory){
                $query = "UPDATE Questions_TestSettings
                          SET
                              fkQuestion = @questID
                          WHERE
                              fkQuestion = '$idQuestion'";
                array_push($queries, $query);
            }
            if($idAnswerToEdit != null){
                $query = "SELECT @questID, @newAswrID";
            }else{
                $query = "SELECT @questID";
            }
            array_push($queries, $query);

            $this->mysqli = $this->connect();
            $this->execTransaction($queries);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;

    }

    /**
     * @name    qUpdateQuestionInfo
     * @param   $idQuestion         String        Question's ID
     * @param   $idTopic            String        Topic's ID
     * @param   $difficulty         String        Question's difficulty
     * @param   $extras             String        Question's extras
     * @param   $shortText          String        Question's short text
     * @param   $translationsQ      Array         Question's translations
     * @return  Boolean
     * @descr   Update all questions details (infos and translations)
     */
    public function qUpdateQuestionInfo($idQuestion, $idTopic, $difficulty, $extras, $shortText, $translationsQ){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($difficulty, $extras, $shortText));
            $query = "UPDATE Questions
                      SET
                          difficulty = '$data[0]',
                          extra = '$data[1]',
                          shortText = '$data[2]',
                          fkTopic = '$idTopic'
                      WHERE
                          idQuestion = '$idQuestion'";
            array_push($queries, $query);
            $query = "DELETE FROM TranslationQuestions
                          WHERE
                              fkQuestion = '$idQuestion'";
            array_push($queries, $query);
            foreach($translationsQ as $idLanguage => $translation){
                if($translation != null){
                    $data = $this->prepareData(array($translation));
                    $query = "INSERT INTO TranslationQuestions
                              VALUES ('$idQuestion', '$idLanguage', '$data[0]')";
                    array_push($queries, $query);
                }
            }
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteQuestion
     * @param   $idQuestion         String        Question's ID
     * @param   $remove             Boolean       If true delete question from database, else only flag status to 'd'
     * @return  Boolean
     * @descr   Return true if question is successfully deleted, false otherwise
     */
    public function qDeleteQuestion($idQuestion, $remove=true){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            if($remove){
                $query = "DELETE FROM Questions
                          WHERE idQuestion = '$idQuestion'";
            }else{
                $query = "UPDATE Questions
                          SET
                              status = 'd'
                          WHERE
                              idQuestion = '$idQuestion'";
            }
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                             Answers                              *
*******************************************************************/

    /**
     * @name    qAnswerInfo
     * @param   $idAnswer         String        Answer's ID
     * @return  Boolean
     * @descr   Get infos about selected answer
     */
    public function qAnswerInfo($idAnswer){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Answers, TranslationAnswers, Languages
                      WHERE
                          idAnswer = '$idAnswer'
                          AND
                          idAnswer = fkAnswer
                          AND
                          idLanguage = fkLanguage";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
	/**
     * @name    qAnswerInfo
     * @param   $idAnswer         String        Answer's ID
     * @return  Boolean
     * @descr   Get infos about selected answer
     */
    public function qSubquestionsInfo($sub_questions){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Sub_Questions
                      WHERE
                          sub_questions = '$sub_questions'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

	/**
     * @name    qnewSubquestions
     * @param   $idQuestion         String        Question's ID
     * @param   $score              String        Answer's type
     * @param   $translationsA      Array         Answer's translations
     * @return  Boolean
     * @descr   create new subQuestion
     */
    public function qnewSubquestions($idQuestion,$score,$translationsA){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $query ="INSERT INTO Sub_Questions (fkQuestions)
                      VALUES ('$idQuestion')";
            array_push($queries, $query);
            if(count($translationsA) > 0){
                $index = 0;
                $query = "INSERT INTO TranslationSubQuestion
                      VALUES ";
                while($index < count($translationsA)){
                    if($translationsA[$index] != null){
                        $data2 = $this->prepareData(array($translationsA[$index]));
                        $query .= "(LAST_INSERT_ID(), '$index', '$data2[0]','$idQuestion'),\n";
                    }
                    $index++;
                }
                $query = substr_replace($query , '', -2);       // Remove last coma
                array_push($queries, $query);
            }

            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
    /**
     * @name    qNewAnswer
     * @param   $idQuestion         String        Question's ID
     * @param   $score              String        Answer's type
     * @param   $translationsA      Array         Answer's translations
     * @return  Boolean
     * @descr   Update all answers details (infos and translations)
     */
    public function qNewAnswer($idQuestion, $score, $translationsA){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $query = "INSERT INTO Answers (score, fkQuestion)
                      VALUES ('$score', '$idQuestion')";
            array_push($queries, $query);
            if(count($translationsA) > 0){
                $index = 0;
                $query = "INSERT INTO TranslationAnswers
                      VALUES ";
                while($index < count($translationsA)){
                    if($translationsA[$index] != null){
                        $data2 = $this->prepareData(array($translationsA[$index]));
                        $query .= "(LAST_INSERT_ID(), '$index', '$data2[0]'),\n";
                    }
                    $index++;
                }
                $query = substr_replace($query , '', -2);       // Remove last coma
                array_push($queries, $query);
            }
            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
	public function qNewAnswerPL($sub,$idQuestion, $score, $translationsA){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $query = "INSERT INTO Answers (score, fksub, fkQuestion)
                      VALUES ('$score','$sub','$idQuestion')";
            array_push($queries, $query);
            if(count($translationsA) > 0){
                $index = 0;
                $query = "INSERT INTO TranslationAnswers
                      VALUES ";
                while($index < count($translationsA)){
                    if($translationsA[$index] != null){
                        $data2 = $this->prepareData(array($translationsA[$index]));
                        $query .= "(LAST_INSERT_ID(), '$index', '$data2[0]'),\n";
                    }
                    $index++;
                }
                $query = substr_replace($query , '', -2);       // Remove last coma
                array_push($queries, $query);
            }
            $query = "SELECT LAST_INSERT_ID()";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
    /**
     * @name    qUpdateAnswerInfo
     * @param   $idAnswer           String        Answer's ID
     * @param   $score              String        Answer's score
     * @param   $translationsA      Array         Answer's translations
     * @return  Boolean
     * @descr   Update all answer details (infos and translations)
     */
    public function qUpdateAnswerInfo($idAnswer, $score, $translationsA){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($idAnswer, $score));
            $query = "UPDATE Answers
                      SET
                          score = '$data[1]'
                      WHERE
                          idAnswer = '$data[0]'";
            array_push($queries, $query);
            $query = "DELETE FROM TranslationAnswers
                      WHERE
                          fkAnswer = '$data[0]'";
            array_push($queries, $query);
            $index = 0;
            while($index < count($translationsA)){
                if($translationsA[$index] != null){
                    $data2 = $this->prepareData(array($translationsA[$index]));
                    $query = "INSERT INTO TranslationAnswers
                              VALUES ('$data[0]', '$index', '$data2[0]')";
                    array_push($queries, $query);
                }
                $index++;
            }
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteAnswer
     * @param   $idAnswer         String        Question's ID
     * @return  Boolean
     * @descr   Return true if answer is successfully deleted, false otherwise
     */
    public function qDeleteAnswer($idAnswer){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "DELETE FROM Answers
                      WHERE idAnswer = '$idAnswer'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                              Exams                               *
*******************************************************************/

    /**
     * @name    qExams
     * @return  Boolean
     * @descr   Get exams's list of teacher
     */
    public function qExams(){
        global $log, $user;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{

          if($user->role == "er"){
            $query = "SELECT subgroup FROM Users WHERE idUser ='$user->id'";
            $this->execQuery($query);
            $row = $this->nextRowAssoc();
            $subgroup = $row["subgroup"];
            $query = "SELECT idExam, Exams.name exam, status, Subjects.name subject,
                           TestSettings.name settings, Exams.password, datetime, idSubject, idTestSetting, scale
                    FROM
                        Exams
                            LEFT JOIN Subjects ON Exams.fkSubject = Subjects.idSubject
                            LEFT JOIN TestSettings ON Exams.fkTestSetting = TestSettings.idTestSetting
                            LEFT JOIN Users_Subjects ON Subjects.idSubject = Users_Subjects.fkSubject
                            JOIN Users ON Users.idUser = Users_Subjects.fkUser
                    WHERE subgroup = '$subgroup'
                    ORDER BY datetime DESC";
          }else{
            $query = "SELECT idExam, Exams.name exam, status, Subjects.name subject,
                           TestSettings.name settings, password, datetime, idSubject, idTestSetting, scale
                    FROM
                        Exams
                            LEFT JOIN Subjects ON Exams.fkSubject = Subjects.idSubject
                            LEFT JOIN TestSettings ON Exams.fkTestSetting = TestSettings.idTestSetting
                            LEFT JOIN Users_Subjects ON Subjects.idSubject = Users_Subjects.fkSubject
                    WHERE fkUser = '$user->id'
                    ORDER BY datetime DESC";
          }

          $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qExamsAvailable
     * @param   $idSubject              String          Subject's ID
     * @param   $idUser                 String          Student's ID
     * @return  Boolean
     * @descr   Get list of exams for requested subject
     */
    public function qExamsAvailable($idSubject, $idUser){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Exams AS E
                      WHERE
                          E.fkSubject = '$idSubject'
                          AND (
                              ((E.status = 'w' OR E.status = 's') AND NOW() BETWEEN E.regStart AND E.regEnd)
                              OR
                              ((E.status = 'w' OR E.status = 's') AND EXISTS (
                                                                          SELECT *
                                                                          FROM Tests AS T
                                                                          WHERE
                                                                              T.fkExam = E.idExam
                                                                              AND
                                                                              T.fkUser = '$idUser')
                              )
                          )";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qExamsInProgress
     * @param   $idTeacher              String|null          Teachers's ID
     * @return  Boolean
     * @descr   Get list of available exams for requested teacher
     */
    public function qExamsInProgress($subGroup,$idTeacher=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
          // ci stava anche AND U.subGroup = '$subGroup
            $query = "SELECT DISTINCT idExam, E.name AS examName, S.name AS subjectName, E.fkSubject, datetime, status
                      FROM Exams AS E
                      JOIN Subjects AS S ON S.idSubject = E.fkSubject
                      JOIN Users_Subjects AS US ON US.fkSubject = S.idSubject
                      JOIN Users AS U ON U.idUser  = US.fkUser
                      WHERE
                          E.status != 'a' ";
            if($idTeacher != null)
                $query .= "AND
                           E.fkSubject IN (SELECT US.fkSubject
                                           FROM Users_Subjects AS US
                                           WHERE
                                           US.fkUser = '$idTeacher')";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewExam
     * @param   $name               String        Exam's name
     * @param   $idSubject          String        Exam's subject ID
     * @param   $idTestSetting      String        Test Settings's ID
     * @param   $datetime           String        Exam's day and time
     * @param   $desc               String        Exam's description
     * @param   $regStart           String        Exam's registration start day and time
     * @param   $regEnd             String        Exam's registration end day and time
     * @param   $rooms              String        Exam's rooms list
     * @param   $password           String        Exam's password
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qNewExam($name, $idSubject, $idTestSetting, $datetime, $desc, $regStart, $regEnd, $rooms, $password){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            $data = $this->prepareData(array($name, $desc));
            $query = "INSERT INTO Exams (name, datetime, description, regStart, regEnd, password, fkTestSetting, fkSubject)
                      VALUES ('$data[0]', '$datetime', '$data[1]', $regStart, $regEnd, '$password', '$idTestSetting', '$idSubject')";
            array_push($queries, $query);
            $query = "SET @examID = LAST_INSERT_ID()";
            array_push($queries, $query);
            $rooms = json_decode(stripslashes($rooms), true);
            if(count($rooms) > 0){
                $query = "INSERT INTO Exams_Rooms (fkExam, fkRoom)
                          VALUES ";
                for($index = 0; $index < count($rooms); $index++){
                    $query .= "(@examID, '$rooms[$index]'),\n";
                }
                $query = substr_replace($query , '', -2);
                array_push($queries, $query);
            }
            $query = "SELECT idExam, Exams.name exam, status, Subjects.name subject, TestSettings.name settings, password, datetime, idSubject, idTestSetting
                      FROM
                          Exams
                              LEFT JOIN Subjects ON Exams.fkSubject = Subjects.idSubject
                              LEFT JOIN TestSettings ON Exams.fkTestSetting = TestSettings.idTestSetting
                      WHERE idExam = @examID";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateExamInfo
     * @param   $idExam             String        Requested Exam's ID
     * @param   $name               String        Exam's name
     * @param   $datetime           String        Exam's day and time
     * @param   $desc               String        Exam's description
     * @param   $regStart           String        Exam's registration start day and time
     * @param   $regEnd             String        Exam's registration end day and time
     * @param   $rooms              String        Exam's rooms list
     * @param   $password           String        Exam's new password
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateExamInfo($idExam, $name, $datetime, $desc, $regStart, $regEnd, $rooms, $password=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $queries = array();
        try{
            if($password != null){
                $query = "UPDATE Exams
                          SET
                              password = '$password'
                          WHERE
                               idExam = '$idExam'";
                $this->execQuery($query);
            }else{
                $data = $this->prepareData(array($name, $desc));
                if(($this->qSelect('Exams', 'idExam', $idExam)) && ($examInfo = $this->nextRowAssoc())){
                    if($examInfo['status'] == 'a'){
                        die(ttEExamArchived);
                    }else{
                        $query = "UPDATE Exams
                                  SET
                                      name = '$data[0]',
                                      datetime = '$datetime',
                                      description = '$data[1]',
                                      regStart = $regStart,
                                      regEnd = $regEnd
                                  WHERE
                                       idExam = '$idExam'";
                        array_push($queries, $query);
                        $query = "DELETE
                                  FROM Exams_Rooms
                                  WHERE
                                      fkExam = '$idExam'";
                        array_push($queries, $query);
                        $rooms = json_decode(stripslashes($rooms), true);
                        if(count($rooms) > 0){
                            $query = "INSERT INTO Exams_Rooms (fkExam, fkRoom)
                                      VALUES ";
                            for($index = 0; $index < count($rooms); $index++){
                                $query .= "('$idExam', '$rooms[$index]'),\n";
                            }
                            $query = substr_replace($query , '', -2);
                            array_push($queries, $query);
                        }
                        $query = "SELECT idExam, Exams.name exam, status, Subjects.name subject, TestSettings.name settings, password, datetime, idSubject, idTestSetting
                                  FROM
                                      Exams
                                          LEFT JOIN Subjects ON Exams.fkSubject = Subjects.idSubject
                                          LEFT JOIN TestSettings ON Exams.fkTestSetting = TestSettings.idTestSetting
                                  WHERE idExam = '$idExam'";
                        array_push($queries, $query);
                        $this->execTransaction($queries);
                    }
                }
            }
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qChangeExamStatus
     * @param   $idExam     String      Exam's ID
     * @param   $status     String      Exam's new status
     * @return  Integer
     * @descr   Return true if exam was successfully started, false otherwise
     */
    public function qChangeExamStatus($idExam, $status){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "UPDATE Exams
                      SET status = '$status'
                      WHERE idExam = '$idExam'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qArchiveExam
     * @param   $idExam     String      Exam's ID
     * @return  Boolean
     * @descr   Return true if exam was successfully archived, false otherwise
     */
    public function qArchiveExam($idExam){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            $query = "DELETE
                      FROM Sets
                      WHERE
                         fkExam = '$idExam'";
            array_push($queries, $query);
            $query = "UPDATE Exams
                      SET status = 'a'
                      WHERE idExam = '$idExam'";
            array_push($queries, $query);

            $this->mysqli = $this->connect();
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." ERRORE : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteExam
     * @param   $idExam     String      Exam's ID
     * @return  Boolean
     * @descr   Return true if exam was successfully deleted, false otherwise
     */
    public function qDeleteExam($idExam){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            // Delete exam (innoDB engine and its foreign key do the rest 8-) )
            $query = "DELETE FROM Exams
                      WHERE idExam = '$idExam'";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qExamRegistrationsList
     * @param   $idExam       String        Requested Exam's ID
     * @return  Boolean
     * @descr   Get list of all users registered to requested exam
     */
    public function qExamRegistrationsList($idExam){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT idTest, timeStart, timeEnd, scoreTest, scoreFinal, status, fkUser, name, surname, email
                      FROM Tests
                      JOIN Users
                          ON Tests.fkUser = Users.idUser
                      WHERE
                          fkExam = '$idExam'
                      ORDER BY surname, name";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qShowExamName
     * @return  string
     * @descr   return name of the exam
     */
    public function qShowExamName($idExam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query = "Select name from Exams where idExam='$idExam'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                $row=mysqli_fetch_array($this->result);
                $val=$row['name'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qStudentsNotRegistered
     * @param   $idExam       String        Requested Exam's ID
     * @return  Boolean
     * @descr   Get list of all users not registered to requested exam
     */
    public function qStudentsNotRegistered($idExam,$id){
        global $log;
        global $user;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT subgroup FROM Users WHERE idUser = $id";
            $this->execQuery($query);
            $row = $this->nextRowAssoc();
            $subgroup = $row["subgroup"];
            $query = "SELECT *
                      FROM Users
                      WHERE
                          role LIKE '%s%'
                          AND subgroup = '$subgroup' AND
                          idUser NOT IN (SELECT fkUser
                                         FROM Tests
                                         WHERE
                                            fkExam = '$idExam')";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qCheckRegistration
     * @param   $idExam     String        Requested Exam's ID
     * @param   $idUser     String        Requested Student's ID
     * @return  Boolean
     * @descr   Get the Tests's row with specific exam and student, if exist
     */
    public function qCheckRegistration($idExam, $idUser){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM Tests
                      WHERE
                          fkExam = '$idExam'
                          AND
                          fkUser = '$idUser'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    public function qGetRatingExam($idExam){
      global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
          
            $query = "SELECT Users.name,Users.surname,Users.email,Tests.timeStart,Tests.timeEnd,TIMESTAMPDIFF(SECOND, Tests.timeStart, Tests.timeEnd) as timeDiff,Tests.scoreTest,Tests.scoreFinal
             FROM Tests JOIN Users ON fkUser = idUser 
             WHERE Tests.fkExam = $idExam AND Tests.status='a'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;

    }

/*******************************************************************
*                              Rooms                               *
*******************************************************************/

    /**
     * @name    qRoomsExam
     * @param   $idExam         String      Exam's ID
     * @return  Boolean
     * @descr   Get list of all rooms added for an exam
     */
    public function qRoomsExam($idExam){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM
                          Exams_Rooms
                          JOIN Rooms ON idRoom = fkRoom
                      WHERE
                          fkExam = '$idExam'
                      ORDER BY fkRoom";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewRoom
     * @param   $name           String        Room's name
     * @param   $desc           String        Room's description
     * @param   $ipStart        String        Room's IP start
     * @param   $ipEnd          String        Room's IP End
     * @return  Boolean
     * @descr   Returns true if room was successfully created, false otherwise
     */
    public function qNewRoom($name, $desc, $ipStart, $ipEnd){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($name, $desc));
            $query = "INSERT INTO Rooms (name, description, ipStart, ipEnd)
                      VALUES ('$data[0]', '$data[1]', '$ipStart', '$ipEnd')";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateRoomInfo
     * @param   $idRoom     String        Requested Room's ID
     * @param   $name       String        Room's name
     * @param   $desc       String        Room's description
     * @param   $ipStart    String        Room's IP start
     * @param   $ipEnd      String        Room's IP end
     * @return  Boolean
     * @descr   Returns true if room's infos was successfully saved, false otherwise
     */
    public function qUpdateRoomInfo($idRoom, $name, $desc, $ipStart, $ipEnd){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($name, $desc));
            $query = "UPDATE Rooms
                      SET
                          name = '$data[0]',
                          description = '$data[1]',
                          ipStart = '$ipStart',
                          ipEnd = '$ipEnd'
                      WHERE
                          idRoom = '$idRoom'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteRoom
     * @param   $idRoom         String      Room's ID
     * @return  Boolean
     * @descr   Return true if requested room was successfully deleted, else otherwise
     */
    public function qDeleteRoom($idRoom){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "DELETE
                      FROM
                          Rooms
                      WHERE
                          idRoom = '$idRoom'
                          AND
                          idRoom NOT IN (
                                      SELECT fkRoom
                                          FROM Exams_Rooms
                                              JOIN Exams ON idExam = fkExam
                                          WHERE
                                              fkRoom = '$idRoom'
                                              AND
                                              status != 'a'
                                      )";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                           Test Setting                           *
*******************************************************************/

    /**
     * @name    qShowTopicsForSetting
     * @param   $idTestSetting      String        Requested Test Settings ID
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qShowTopicsForSetting($idTestSetting){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($idTestSetting));
            $query= "SELECT idTopic, Topics.name AS topicName,Topics_TestSettings.numQuestions, Topics_TestSettings.numEasy, Topics_TestSettings.numMedium, Topics_TestSettings.numHard
                     FROM Topics
                         JOIN Topics_TestSettings ON idTopic = fkTopic
                         JOIN TestSettings ON idTestSetting = fkTestSetting
                     WHERE
                         idTestSetting = '".$data[0]."'";
            $this->execQuery($query);
        }
        catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qShowQuestionsForSetting
     * @param   $idTestSetting      String        Requested Test Settings ID
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qShowQuestionsForSetting($idTestSetting){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($idTestSetting));
            $query= "SELECT idQuestion, status, translation, type, difficulty, fkLanguage, name, fkTopic
                     FROM Questions, TranslationQuestions, Topics
                     WHERE
                        idTopic = fkTopic
                        AND
                        idQuestion=fkQuestion
                        AND
                        idQuestion IN (	SELECT Questions_TestSettings.fkQuestion
                                        FROM Questions_TestSettings
                                        WHERE fkTestSetting = '".$data[0]."')";
            $this->execQuery($query);
        }
        catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateTestSettingsInfo
     * @param   $idTestSetting          String          Requested Test Settings ID
     * @param   $completeUpdate         String          Type of update
     * @param   $name                   String          Test setting's name
     * @param   $desc                   String          Test setting's description
     * @param   $scoreType              String          Test setting's score type
     * @param   $scoreMin               String          Test setting's minimum score
     * @param   $bonus                  String          Test setting's bonus
     * @param   $negative               String          Test setting's negative
     * @param   $editable               String          Test setting's editable
     * @param   $duration               String          Test setting's duration
     * @param   $questions              String          Test setting's questions number
     * @param   $distributionMatrix     Array           Test setting's random questions distribution for topic and difficulty
     * @param   $questionsT             Array           Test setting's questions topic distribution
     * @param   $questionsD             Array           Test setting's questions difficulty distribution
     * @param   $questionsM             Array           Test setting's mandatory questions
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateTestSettingsInfo($questionsDistribution,$idTestSetting, $completeUpdate, $name, $desc, $scoreType=null, $scoreMin=null,
                                            $bonus=null, $negative=null, $editable=null,$certificate=null, $duration=null, $questions=null,
                                            $easy=null, $medium=null, $hard=null, $matrixDistribution=null, $mandatQuestionsI=null,
                                            $numTopics=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($name, $desc));
            if($completeUpdate != 'true'){
                $query = "UPDATE TestSettings
                          SET
                              name = '".$data[0]."',
                              description = '".$data[1]."',
                              negative = '$negative',
                              editable = '$editable',
                              certificate = '$certificate'
                          WHERE
                              idTestSetting = '$idTestSetting'";
                $this->execQuery($query);
            }else{
                $queries = array();
                $scale = round($scoreType / $questions, 1);
                $query = "UPDATE TestSettings
                          SET
                              name = '$data[0]',
                              description = '$data[1]',
                              questions = '$questions',
                              scoreType = '$scoreType',
                              scoreMin = '$scoreMin',
                              scale = '$scale',
                              bonus = '$bonus',
                              duration = '$duration',
                              negative = '$negative',
                              editable = '$editable',
                              certificate = '$certificate',
                              numEasy = '$easy',
                              numMedium = '$medium',
                              numHard = '$hard'
                          WHERE
                              idTestSetting = '$idTestSetting'";
                array_push($queries, $query);

                $query = "DELETE FROM Questions_TestSettings
                          WHERE
                              fkTestSetting = '$idTestSetting'";
                array_push($queries, $query);
                foreach($mandatQuestionsI as $idQuestion){
                    if($idQuestion != 0){
                        $query = "INSERT INTO Questions_TestSettings (fkQuestion, fkTestSetting)
                              VALUES ('$idQuestion', '$idTestSetting')";
                        array_push($queries, $query);
                    }
                }

                $query = "DELETE FROM Topics_TestSettings
                          WHERE
                              fkTestSetting = '$idTestSetting'";
                array_push($queries, $query);
                for ($i=0; $i<$numTopics; $i++){
                    $numEasy = $matrixDistribution[$i][0];
                    $numMedium = $matrixDistribution[$i][1];
                    $numHard = $matrixDistribution[$i][2];
                    $idTopic = $matrixDistribution[$i][3];
                    $numQuestions = $numEasy+$numMedium+$numHard;
                    $query = "INSERT INTO Topics_TestSettings (fkTestSetting, fkTopic, numEasy, numMedium, numHard, numQuestions)
                          VALUES($idTestSetting, '$idTopic', '$numEasy', '$numMedium', '$numHard', '$numQuestions')";
                    array_push($queries, $query);
                }
                $query = "DELETE FROM questionsdistribution WHERE fkTestSetting= '$idTestSetting'";
                array_push($queries, $query);
                foreach($questionsDistribution as $question){
                    $query = "INSERT INTO questionsdistribution (fkTestSetting,fkQuestion,counter)
                              VALUES ($idTestSetting, '$question', 0)";
                    array_push($queries,$query);
                }
//                $log->append(var_export($queries, true));
                $this->execTransaction($queries);
            }

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewSettings
     * @param   $idSubject              String          Test setting's ID
     * @param   $name                   String          Test setting's name
     * @param   $scoreType              String          Test setting's score type
     * @param   $scoreMin               String          Test setting's minimum score
     * @param   $bonus                  String          Test setting's bonus
     * @param   $negative               String          Test setting's negative
     * @param   $editable               String          Test setting's editable
     * @param   $duration               String          Test setting's duration
     * @param   $questions              String          Test setting's questions number
     * @param   $desc                   String          Test setting's description
     * @param   $distributionMatrix     Array           Test setting's random questions distribution for topic and difficulty
     * @param   $questionsT             Array           Test setting's questions topic distribution
     * @param   $questionsD             Array           Test setting's questions difficulty distribution
     * @param   $questionsM             Array           Test setting's mandatory questions
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qNewSettings($idSubject, $questionDistribution, $name, $scoreType, $scoreMin, $bonus, $negative, $editable, $certificate, $duration,
                                 $questions, $easy, $medium, $hard, $desc, $matrixDistribution, $mandatQuestions, $numTopics){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $log->append(">>>>>>>>>>>>>>>>>>>> 15.1");
        $log->append($idSubject. $name. $scoreType. $scoreMin. $bonus. $negative. $editable. $duration.
            $questions. $desc);
        try{
            $queries = array();

        $log->append(">>>>>>>>>>>>>>>>>>>> 15.2");
            $data = $this->prepareData(array($name, $desc));
            $scale = round($scoreType / $questions, 1,PHP_ROUND_HALF_UP);
            $query = "INSERT INTO TestSettings (name, description, questions, scoreType, scoreMin, scale, bonus, negative, editable, certificate, duration, numEasy, numMedium, numHard, fkSubject)
                  	  VALUES ('$data[0]', '$data[1]', '$questions', '$scoreType', '$scoreMin', '$scale', '$bonus', '$negative', '$editable','$certificate', '$duration', '$easy', '$medium', '$hard','$idSubject')";
            array_push($queries, $query);
            $query = "SET @settID = LAST_INSERT_ID()";
            array_push($queries, $query);

        $log->append(">>>>>>>>>>>>>>>>>>>> 15.3");
            foreach($mandatQuestions as $idQuestion){
                if($idQuestion != 0){
                    $query = "INSERT INTO Questions_TestSettings (fkQuestion, fkTestSetting)
                              VALUES ('$idQuestion', @settID)";
                    array_push($queries, $query);
                }
            }

        $log->append(">>>>>>>>>>>>>>>>>>>> 15.4");
            for ($i=0; $i<$numTopics; $i++){
                $numEasy = $matrixDistribution[$i][0];
                $numMedium = $matrixDistribution[$i][1];
                $numHard = $matrixDistribution[$i][2];
                $idTopic = $matrixDistribution[$i][3];
                $numQuestions = $numEasy+$numMedium+$numHard;
                $query = "INSERT INTO Topics_TestSettings (fkTestSetting, fkTopic, numEasy, numMedium, numHard, numQuestions)
                          VALUES(@settID, '$idTopic', '$numEasy', '$numMedium', '$numHard', '$numQuestions')";
                array_push($queries, $query);
            }
        $log->append(">>>>>>>>>>>>>>>>>>>> 15.5");

            foreach($questionDistribution as $question){
                    $query = "INSERT INTO questionsdistribution (fkTestSetting,fkQuestion,counter)
                              VALUES (@settID, '$question', 0)";
                    array_push($queries,$query);
            }

        $log->append(">>>>>>>>>>>>>>>>>>>> 15.6");

            $query = "SELECT @settID";
            array_push($queries, $query);

            //********************************************************
//            $log->append(var_export($queries, true));
            $this->execTransaction($queries);
        $log->append(">>>>>>>>>>>>>>>>>>>> 15.7");
        }catch(Exception $ex){
        $log->append(">>>>>>>>>>>>>>>>>>>> 15.8");
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteTestSettings
     * @param   $idTestSetting       String        Requested Settings's ID
     * @return  String
     * @descr   Return true if test settings was successfully deleted, false otherwise
     */
    public function qDeleteTestSettings($idTestSetting){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "DELETE FROM TestSettings
                      WHERE
                          idTestSetting = '$idTestSetting'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                             Students                             *
*******************************************************************/

    /**
     * @name    qNewUser
     * @param   $name           String        User's name
     * @param   $surname        String        User's surname
     * @param   $email          String        User's email
     * @param   $token          String        Token's value
     * @param   $role           String        User's role
     * @param   $password       String        User's password
     * @return  Boolean
     * @descr   Returns true if student was successfully created, false otherwise
     */
    public function qNewUser($name, $surname, $email, $token, $role, $group, $subgroup, $password=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($name, $surname));
            $queries = array();
            if($password == null){      // Creating a teacher or an admin
                $query = "INSERT INTO Users (name, surname, email, `group`,`subgroup`,role)
                          VALUES ('$data[0]', '$data[1]', '$email','$group', '$subgroup', '$role')";
                array_push($queries, $query);
                $query = "INSERT INTO Tokens (email, action, value)
                          VALUES ('$email', 'c', '$token')";
                array_push($queries, $query);
            }else{                      // Creating a student
                $query = "INSERT INTO Users (name, surname, email, password, `group`,`subgroup`, role)
                          VALUES ('$data[0]', '$data[1]', '$email', '$password', '$group', '$subgroup', 's')";
                array_push($queries, $query);
                $query = "SELECT LAST_INSERT_ID()";
                array_push($queries, $query);
            }
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }
    public function qListGroup(){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT *
                          FROM
                              GroupNTC JOIN SubGroup ON idGroup = fkGroup ORDER BY NameGroup";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    public function qListSpecificSubgroup($idGroup){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT *
                          FROM
                               SubGroup WHERE fkGroup = $idGroup ORDER BY NameSubGroup";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    public function qGetSubGroup($id){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT subgroup
                          FROM
                              Users WHERE idUser = $id";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    public function qGetGroup($id){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT `group`
                          FROM
                              Users WHERE idUser = $id";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewToken
     * @param   $email       String        User's email
     * @param   $action      String        Token's action
     * @param   $value       String        Token's value
     * @return  Boolean
     * @descr   Returns true if token was successfully created, false otherwise
     */
    public function qNewToken($email, $action, $value){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            $query = "DELETE
                      FROM Tokens
                      WHERE
                           email = '$email'";
            array_push($queries, $query);
            $query = "INSERT INTO Tokens (email, action, value)
                      VALUES ('$email', '$action', '$value')
                      ON DUPLICATE KEY UPDATE
                          value = '$value'";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateProfile
     * @param   $idUser     String        User's ID
     * @param   $name       String        User's name
     * @param   $surname    String        User's surname
     * @param   $email      String        User's email
     * @param   $password   String        User's password
     * @param   $lang       String        User's language
     * @param   $role       String        User's role
     * @return  Boolean
     * @descr   Returns true if User's profile was successfully updated, false otherwise
     */
    public function qUpdateProfile($idUser, $name=null, $surname=null, $group=null, $subgroup=null,$email=null, $password=null, $lang=null, $role = null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            
            $query = "UPDATE Users
                      SET ";
            if($name != null){
                $data = $this->prepareData(array($name));
                $query .= "name = '$data[0]',";
            }
            if($surname != null){
                $data = $this->prepareData(array($surname));
                $query .= "surname = '$data[0]',";
            }
            if($group != null){
                $query .= "`group` = '$group',";
            }
            if($subgroup != null){
                $query .= "subgroup = '$subgroup',";
            }
            if($email != null){
                $query .= "email = '$email',";
            }
            if($password != null){
                $query .= "password = '".$password."',";
            }
            if($lang != null){
                $query .= "fkLanguage = '$lang',";
            }
            if($role != null){
                $query .= "role = '$role',";
            }
            $query = substr_replace($query , '', -1);       // Remove last coma
            $query .= "WHERE
                          idUser = '".$idUser."'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qTeachers
     * @param   $idSubject          String          Subject's ID
     * @return  Boolean
     * @descr   Returns true if query if successfully executed, false otherwise
     */
    public function qTeachers($idSubject = null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT *
                      FROM
                          Users AS U ";
            if($idSubject == null){
                $query .= "WHERE
                               role IN ('at', 't', 'st','e')";
            }else{
                $query .= " JOIN Users_Subjects AS US ON U.idUser = US.fkUser
                        WHERE
                            role IN ('at', 't', 'st','e')
                            AND
                            US.fkSubject = '$idSubject';";
            }
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                              Tests                               *
*******************************************************************/

    /**
     * @name    qTestDetails
     * @param   $idSet     Integer        Test set's ID
     * @param   $idTest    Integer        Test's ID
     * @return  Boolean
     * @descr   Search all details about test associated with a specific questions set or specific ID
     */
    public function qTestDetails($idSet, $idTest = null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT T.idTest, T.timeStart, T.timeEnd, T.scoreTest, T.scoreFinal, T.status, T.fkSet, T.fkExam, T.bonus AS testBonus,
                             S.idUser, S.name, S.surname, S.email, S.fkLanguage,
                             E.idExam, E.fkSubject,
                             TS.questions, TS.scoreType, TS.scoreMin, TS.scale, TS.bonus, TS.duration, TS.negative, TS.editable
                          FROM
                              Tests AS T
                              JOIN Users AS S ON T.fkUser = S.idUser
                              JOIN Exams AS E ON T.fkExam = E.idExam
                              JOIN TestSettings AS TS ON E.fkTestSetting = TS.idTestSetting
                          WHERE ";
            if($idTest == null){
                $query .= "T.fkSet = '$idSet'";
            }else{
                $query .= "idTest = '$idTest'";
            }
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qTestList
     * @param   $idUser         Integer        Teacher's ID
     * @return  Boolean
     * @descr   Search all test's details for a teacher
     */
    public function qTestsList($idUser){
        global $log, $user;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT idTest, timeStart, timeEnd, T.status, scoreTest, fkExam, fkSubject, idUser, S.name, S.surname, Sub.name AS subName
                      FROM Tests AS T
                          JOIN Exams AS E ON T.fkExam = E.idExam
                          JOIN Users AS S ON T.fkUser = S.idUser
                          JOIN Subjects AS Sub ON E.fkSubject = Sub.idSubject
                      WHERE
                          E.fkSubject IN (SELECT fkSubject
                                          FROM Users_Subjects
                                          WHERE
                                              fkUser = '$idUser')";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateTestStatus
     * @param   $idTest     Integer        Test set's ID
     * @param   $status     String         New test's status
     * @return  Boolean
     * @descr   Return true if status has been successfully updated, false otherwise
     */
    public function qUpdateTestStatus($idTest, $status){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "UPDATE Tests
                      SET status = '$status'
                      WHERE
                          idTest = '$idTest'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qStartTest
     * @param   $idTest         String        Test's ID
     * @param   $datetime       String        Start datetime for test
     * @return  Boolean
     * @descr   Return true if successfully set timeStart and status for user test
     */
    public function qStartTest($idTest, $datetime){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "UPDATE Tests
                      SET
                          timeStart = '$datetime',
                          status = 's'
                      WHERE
                          idTest = '$idTest'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateTestAnswers
     * @param   $idSet          String      Questions set ID
     * @param   $questions      Array       Array of all question's ID
     * @param   $answers        Array       Array of all question's answer/s
     * @return  Boolean
     * @descr   Return true if successfully update all answers for requested test
     */
    public function qUpdateTestAnswers($idSet,$IdLang, $questions, $answers){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            //$query = "UPDATE Sets_Questions SET answer = CASE\n";
            $query = "UPDATE Sets_Questions SET fkIdLanguage = $IdLang , answer = CASE\n"; // inserisco nella tabella anche la lingua utilizzata per eseguire il test

            while(count($questions) > 0){
                $question = array_pop($questions);
                $answer = array_pop($answers);
                $query .= "WHEN (fkSet = $idSet AND fkQuestion = $question) THEN '$answer'\n";
            }
            $query .= "ELSE answer
                       END";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }


/**
     * @name    qUpdateTestAnswers
     * @param   $idSet          String      Questions set ID
     * @param   $question      Array       Array of all question's ID
     * @param   $answer        Array       Array of all question's answer/s
     * @return  Boolean
     * @descr   Return true if successfully update answer for requested test
     */
    public function qUpdateTestAnswer($idSet,$IdLang, $question, $answer){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            //$query = "UPDATE Sets_Questions SET answer = CASE\n";
            $query = "UPDATE Sets_Questions SET fkIdLanguage = $IdLang , answer = CASE\n"; // inserisco nella tabella anche la lingua utilizzata per eseguire il test
            $query .= "WHEN (fkSet = $idSet AND fkQuestion = $question) THEN '$answer'\n";
            $query .= "ELSE answer
                       END";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }


    /**
     * @name    qEndTest
     * @param   $idSet          String      Question set ID
     * @return  Boolean
     * @descr   Return true if test successfully stopped
     */
    public function qEndTest($idSet){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $datetime = date("Y-m-d H:i:s");

            // Get scale and negative from test settings
            $query = "SELECT scale, negative
                      FROM TestSettings AS TS
                          JOIN Exams AS E ON E.fkTestSetting = TS.idTestSetting
                          JOIN Tests AS T ON T.fkExam = E.idExam
                      WHERE
                          T.fkSet = '$idSet'";
            $this->execQuery($query);
            $row = $this->nextRowAssoc();
            $scale = $row['scale'];
            $allowNegative = ($row['negative'] == 0)? false : true;

            // Calculate test's score
            $this->mysqli = $this->connect();
            $score = 0;
            $query = "SELECT idQuestion, type, answer
                      FROM Sets_Questions AS SQ
                           JOIN Questions AS Q ON Q.idQuestion = SQ.fkQuestion
                      WHERE
                          fkSet = '$idSet'";
            $this->execQuery($query);
            $test = $this->getResultAssoc('idQuestion');

            foreach($test as $idQuestion => $setQuestion){
                $question = Question::newQuestion($setQuestion['type'], $setQuestion);
                $scoreTemp = $question->getScoreFromGivenAnswer();
                // If negative score is not allowed and question's score is negative sum 0, sum real score otherwise
                $score2add = (!$allowNegative && $scoreTemp < 0)? 0 : $scoreTemp;
                $score += $score2add;
            }
            $score = round($scale * $score, 2);

            // Update test
            $this->mysqli = $this->connect();
            $query = "UPDATE Tests
                      SET timeEnd = '$datetime',
                          scoreTest = '$score',
                          status = 'e'
                      WHERE
                          fkSet = '$idSet'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }


    /**
     * @name    qEndTest
     * @param   $idSet          String      Question set ID
     * @return  Boolean
     * @descr   Return true if test successfully stopped
     */
    public function qEndTestByTeacher($idTest){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
          $query = "SELECT fkSet
                      FROM Tests 
                      WHERE
                          idTest = '$idTest'";
          $this->execQuery($query);
          $idSet = $this->nextRowAssoc();
          $idSet = $idSet["fkSet"];

            $datetime = date("Y-m-d H:i:s");

            // Get scale and negative from test settings
            $query = "SELECT scale, negative
                      FROM TestSettings AS TS
                          JOIN Exams AS E ON E.fkTestSetting = TS.idTestSetting
                          JOIN Tests AS T ON T.fkExam = E.idExam
                      WHERE
                          T.fkSet = '$idSet'";
            $this->execQuery($query);
            $row = $this->nextRowAssoc();
            $scale = $row['scale'];
            $allowNegative = ($row['negative'] == 0)? false : true;

            // Calculate test's score
            $this->mysqli = $this->connect();
            $score = 0;
            $query = "SELECT idQuestion, type, answer
                      FROM Sets_Questions AS SQ
                           JOIN Questions AS Q ON Q.idQuestion = SQ.fkQuestion
                      WHERE
                          fkSet = '$idSet'";
            $this->execQuery($query);
            $test = $this->getResultAssoc('idQuestion');

            foreach($test as $idQuestion => $setQuestion){
                $question = Question::newQuestion($setQuestion['type'], $setQuestion);
                $scoreTemp = $question->getScoreFromGivenAnswer();
                // If negative score is not allowed and question's score is negative sum 0, sum real score otherwise
                $score2add = (!$allowNegative && $scoreTemp < 0)? 0 : $scoreTemp;
                $score += $score2add;
            }
            $score = round($scale * $score, 2);

            // Update test
            $this->mysqli = $this->connect();
            $query = "UPDATE Tests
                      SET timeEnd = '$datetime',
                          scoreTest = '$score',
                          status = 'e'
                      WHERE
                          fkSet = '$idSet'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack =false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @param   $idTest             String      Test's ID
     * @param   $correctScores      Array       Test's final score
     * @param   $scoreTest          String      Test's final score
     * @param   $bonus              String      Test's bonus score
     * @param   $scoreFinal         String      Test's final score
     * @param   $scale              Float       Test Setting's scale
     * @param   $allowNegative      Bool        True if test allow negative score, else otherwise
     * @param   $status             String      Test's status (if != 'e')
     * @return  bool
     * @descr   Return true if test successfully archived
     */
    public function qArchiveTest($idTest, $correctScores, $scoreTest, $bonus, $scoreFinal, $scale=1.0, $allowNegative=false, $status='a'){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $submitted = ($scoreTest == null)? false : true;
            $corrected = (count($correctScores) == 0)? false : true;
            $queries = array();

            if($submitted){
                $query = "SELECT idQuestion, type, answer
                          FROM Sets_Questions AS SQ
                               JOIN Questions AS Q ON Q.idQuestion = SQ.fkQuestion
                          WHERE
                              fkSet = (SELECT fkSet
                                       FROM Tests
                                       WHERE idTest = '$idTest')";
                $this->execQuery($query);
                $test = $this->getResultAssoc('idQuestion');

                if(!$corrected){         // The test is not been corrected, get scores from given answers
                    foreach($test as $idQuestion => $setQuestion){
                        $question = Question::newQuestion($setQuestion['type'], $setQuestion);
                        $scoreTemp = $question->getScoreFromGivenAnswer();
                        // If negative score is not allowed and question's score is negative sum 0, sum real score otherwise
                        $score2add = (!$allowNegative && $scoreTemp < 0)? 0 : $scoreTemp;
                        $correctScores[$idQuestion] = round(($score2add * $scale), 2);
                    }
                }

                $query = "INSERT INTO History(fkTest, fkQuestion, answer, score)
                          VALUES \n";
                foreach($test as $idQuestion => $questionInfo)
                    $query .= "('$idTest', '".$idQuestion."', '".$questionInfo['answer']."', '".$correctScores[$idQuestion]."'),";

                $query = substr_replace($query , '', -1);       // Remove last coma
                array_push($queries, $query);

                $query = "UPDATE Tests
                          SET
                              scoreTest = '$scoreTest',
                              bonus = '$bonus',
                              scoreFinal = '$scoreFinal',
                              status = 'a'
                          WHERE
                              idTest = '$idTest'";
                array_push($queries, $query);
            }else{
                $now = date("Y-m-d H:i:s");
                $query = "UPDATE Tests
                      SET
                          timeEnd = '$now',
                          scoreTest = '0',
                          bonus = '0',
                          scoreFinal = '0',
                          status = '$status'
                      WHERE
                          idTest = '$idTest'";
                array_push($queries, $query);
            }

            $query = "DELETE
                          FROM Sets
                          WHERE
                              idSet = (SELECT fkSet
                                       FROM Tests
                                       WHERE
                                           idTest = '$idTest')";
            array_push($queries, $query);

            $this->mysqli = $this->connect();
            $this->execTransaction($queries);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }



/*******************************************************************
*                               Sets                               *
*******************************************************************/

    /**
     * @name    qMakeQuestionsSetOld
     * @param   $idExam     String        Exam's ID
     * @param   $idUser     String        Student's ID
     * @return  Boolean
     * @descr   Create a new test, create a question's set and register student in exam
     */
    public function qMakeQuestionsSetOld($idExam, $idUser){
        global $log;
        $ack = true;
        $this->error = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT fkTestSetting
                      FROM
                          Exams
                      WHERE
                          idExam = '$idExam'";
            $this->execQuery($query);
            $examInfo = $this->nextRowAssoc();
            $idTestSetting = $examInfo['fkTestSetting'];

            $questionsSelected = array();
            $query = "SELECT *
			 	 	  FROM
			 		      Questions_TestSettings
			 		  WHERE
			 		      fkTestSetting = '$idTestSetting'";
            $this->execQuery($query);
            while(($question = $this->nextRowAssoc())){
                array_push($questionsSelected, $question['fkQuestion']);
            }

//            $log->append("questionsSelected: ".var_export($questionsSelected, true));

            $topics = array();
            $this->mysqli = $this->connect();
            $query= "SELECT *
					 FROM
					     Topics_TestSettings
					 WHERE
					 	 fkTestSetting = '$idTestSetting'";
            $this->execQuery($query);
            while(($topic = $this->nextRowAssoc())){
                $topics[$topic['fkTopic']] = $topic;
            }

//            $log->append("topics: ".var_export($topics, true));

            $questionsSet = $questionsSelected;
            $allQuestions = array();
            $ok=1;  $topicsBackup=$topics;
            foreach($topics as $idTopic => $topicInfo){
                $difficulties = getSystemDifficulties();
                foreach($difficulties as $difficulty => $difficultyName){
                    $difficultyName = 'num'.ucfirst($difficultyName);
                    $this->mysqli = $this->connect();
                    $query = "SELECT idQuestion
                              FROM
                                  Questions
                              WHERE
                                  fkTopic = '$idTopic'
                                  AND
                                  difficulty = '$difficulty'
                                  AND
                                  status = 'a' ";
                    if(count($questionsSelected) > 0)
                        $query .= "AND
                                   idQuestion NOT IN (".implode(',', $questionsSelected).")";
                    $this->execQuery($query);
                    $allQuestions[$idTopic][$difficultyName] = $this->getResultAssoc();

                    $questionsForDifficulty = $topics[$idTopic][$difficultyName];
                    if($questionsForDifficulty <= count($allQuestions[$idTopic][$difficultyName])){
                        while($questionsForDifficulty > 0){
                            $idToAdd = rand(0, (count($allQuestions[$idTopic][$difficultyName]) - 1));
                            array_push($questionsSet, $allQuestions[$idTopic][$difficultyName][$idToAdd]['idQuestion']);
                            unset($allQuestions[$idTopic][$difficultyName][$idToAdd]);
                            $allQuestions[$idTopic][$difficultyName] = array_values($allQuestions[$idTopic][$difficultyName]);

                            $questionsForDifficulty--;
                        }
                    }else{
                      $ok=0;
                      break 2;
                    }
                }
            }if($ok==0){
              $questionsSet = $questionsSelected;
              $difficulties = getSystemDifficulties();
              $questionsForDifficulty=0;
              $this->mysqli = $this->connect();
              $query = "SELECT numEasy,numMedium,numHard FROM TestSettings WHERE idTestSetting='$idTestSetting'";
              $this->execQuery($query);
              $numberOfQuestions = $this->getResultAssoc();
              $easy=$numberOfQuestions["0"]["numEasy"];
              $medium=$numberOfQuestions["0"]['numMedium'];
              $hard=$numberOfQuestions["0"]['numHard'];
              $ok=1;
              foreach($difficulties as $difficulty => $difficultyName){
                if($ok==1){
                  $difficultyName = 'num'.ucfirst($difficultyName);
                  $this->mysqli = $this->connect();
                  $query = "SELECT idQuestion
                    FROM
                    Questions
                    WHERE
                    difficulty = '$difficulty'
                    AND
                    status = 'a' 
                    AND (";
                  $topics=$topicsBackup;
                  $numTopics = count($topics); 
                  $i=0;$num=0;
                  foreach($topics as $idTopic => $topicInfo){
                    $query .= "fkTopic = '$idTopic' ";
                    if(!(++$i === $numTopics)){ //if it is not the last element...
                      $query .= " OR ";
                    }                            
                  }
                  $query=$query." )";
                  if(count($questionsSelected) > 0)
                    $query .= "AND idQuestion NOT IN (".implode(',', $questionsSelected).")";
                  $this->execQuery($query);
                  $allQuestions[$difficultyName] = $this->getResultAssoc();
                  if($difficulty==1)$questionsForDifficulty = $easy;
                  elseif($difficulty==2)$questionsForDifficulty = $medium;
                  else $questionsForDifficulty = $hard;                
                  if($questionsForDifficulty <= count($allQuestions[$difficultyName])){
                          while($questionsForDifficulty > 0){
                              $idToAdd = rand(0, (count($allQuestions[$difficultyName]) - 1));
                              array_push($questionsSet, $allQuestions[$difficultyName][$idToAdd]['idQuestion']);                           
                              unset($allQuestions[$difficultyName][$idToAdd]);
                              $allQuestions[$difficultyName] = array_values($allQuestions[$difficultyName]);
                              $questionsForDifficulty--;
                          }
                  }else $ok=0;
                }
              }
            }if($ok==0){ // qui mettere il caso in cui prende quello che è possibile !
              die(ttERegFailedQuestions);
            }


            $this->mysqli = $this->connect();
            $queries = array();
            $query = "INSERT INTO Sets (assigned, fkExam)
                      VALUES ('n', '$idExam')";
            array_push($queries, $query);
            $query = "INSERT INTO Sets_Questions (fkSet, fkQuestion, answer)
                      VALUES \n";
            foreach($questionsSet as $idQuestion){
                $query .= "(LAST_INSERT_ID(), '$idQuestion', ''),";
            }
            $query = substr_replace($query , '', -1);       // Remove last coma
            array_push($queries, $query);
            $query = "INSERT INTO Tests (status, fkExam, fkUser)
                      VALUES ('w', '$idExam', '$idUser')";
            array_push($queries, $query);
            $this->execTransaction($queries);

        }catch(Exception $e){
            $ack = false;
            $log->append("Exception: ".$this->getError());
        }

        return $ack;

    }

    /**
     * @name    qMakeQuestionsSet
     * @param   $idExam     String        Exam's ID
     * @param   $idUser     String        Student's ID
     * @return  Boolean
     * @descr   Create a new test, create a question's set and register student in exam
     */
    public function qMakeQuestionsSet($idExam, $idUser){
        global $log;
        $ack = true;
        $this->error = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT fkTestSetting
                      FROM
                          Exams
                      WHERE
                          idExam = '$idExam'";
            $this->execQuery($query);
            $examInfo = $this->nextRowAssoc();
            $idTestSetting = $examInfo['fkTestSetting'];

            $questionsSelected = array();
            $query = "SELECT *
			 	 	  FROM
			 		      Questions_TestSettings
			 		  WHERE
			 		      fkTestSetting = '$idTestSetting'";
            $this->execQuery($query);
            while(($question = $this->nextRowAssoc())){
                array_push($questionsSelected, $question['fkQuestion']);
            }

//            $log->append("questionsSelected: ".var_export($questionsSelected, true));

            $topics = array();
            $this->mysqli = $this->connect();
            $query= "SELECT *
					 FROM
					     Topics_TestSettings
					 WHERE
					 	 fkTestSetting = '$idTestSetting'";
            $this->execQuery($query);
            while(($topic = $this->nextRowAssoc())){
                $topics[$topic['fkTopic']] = $topic;
            }

//            $log->append("topics: ".var_export($topics, true));

            $questionsSet = $questionsSelected;
            foreach($topics as $idTopic => $topicInfo){
                $difficulties = getSystemDifficulties();
                foreach($difficulties as $difficulty => $difficultyName){
                    $allQuestions = array();
                    $difficultyName = 'num'.ucfirst($difficultyName);
                    $this->mysqli = $this->connect();
                    $query = "SELECT Questions.idQuestion, questionsdistribution.counter
                              FROM
                                  Questions JOIN questionsdistribution 
                                  ON Questions.idQuestion = questionsdistribution.fkQuestion
                              WHERE
                                  Questions.fkTopic = '$idTopic'
                                  AND
                                  questionsdistribution.fkTestSetting = '$idTestSetting'
                                  AND
                                  Questions.difficulty = '$difficulty'
                                  AND
                                  Questions.status = 'a' ";
                    if(count($questionsSelected) > 0)
                        $query .= "AND
                                   idQuestion NOT IN (".implode(',', $questionsSelected).")";
                    $this->execQuery($query);
                    while(($question = $this->nextRowAssoc())){
                        $allQuestions[$question['idQuestion']] = $question['counter'];
                    }
                    $questionsForDifficulty = $topics[$idTopic][$difficultyName];
                    if($questionsForDifficulty <= count($allQuestions)){
                        while($questionsForDifficulty > 0){
                            $minCounterQuestions = $this->getMinCounterQuestions($allQuestions);
                            $idToAdd = rand(0, (count($minCounterQuestions) - 1));
                            array_push($questionsSet, $minCounterQuestions[$idToAdd]);
                            unset($allQuestions[$minCounterQuestions[$idToAdd]]);
                            $questionsForDifficulty--;
                        }
                    }
                }
            }
            $this->mysqli = $this->connect();
            $queries = array();
            $query = "INSERT INTO Sets (assigned, fkExam)
                      VALUES ('n', '$idExam')";
            array_push($queries, $query);

            $query = "INSERT INTO Sets_Questions (fkSet, fkQuestion, answer)
                  VALUES \n";
            foreach ($questionsSet as $idQuestion) {
                $query .= "(LAST_INSERT_ID(), '$idQuestion', ''),";
            }
            $query = substr_replace($query, '', -1);// Remove last coma

            array_push($queries, $query);

            $query = "INSERT INTO Tests (status, fkExam, fkUser)
                      VALUES ('w', '$idExam', '$idUser')";
            array_push($queries, $query);

            $query = "UPDATE questionsdistribution SET counter = counter+1
                      WHERE fkTestSetting = '$idTestSetting' AND
                      fkQuestion IN (".implode(',', $questionsSet).")";
            array_push($queries, $query);

            $this->execTransaction($queries);

        }catch(Exception $e){
            $ack = false;
            $log->append("Exception: ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    getMinCounterQuestions
     * @return  array
     * @descr   Return the array of the questions that have minimum counter
     */
    private function getMinCounterQuestions($allQuestions){
        global $log;
        $questions = array();
        $min = min($allQuestions);
        foreach ($allQuestions as $idQuestion => $counter){
            if($counter == $min){
                array_push($questions,$idQuestion);
            }
        }
        $log->append("Domande: ".var_export($questions, true));
        return $questions;
    }

    /**
     * @name    qAssignSet
     * @param   $idExam        String        Exam's ID
     * @param   $idUser     String        Student's ID
     * @return  Boolean
     * @descr   Return true if set was successfully assigned, false otherwise
     */
    public function qAssignSet($idExam, $idUser){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            $query = "SELECT idSet
                      FROM Sets
                      WHERE
                          assigned = 'n'
                          AND
                          fkExam = '$idExam'
                      LIMIT 1
                      INTO @setID";
            array_push($queries, $query);
            $query = "UPDATE Sets
                      SET
                          assigned = 'y'
                      WHERE
                          idSet = @setID";
            array_push($queries, $query);
            $query = "UPDATE Tests
                      SET
                          fkSet = @setID
                      WHERE
                          fkExam = '$idExam'
                          AND
                          fkUser = '$idUser'";
            array_push($queries, $query);
            $query = "SELECT @setID";
            array_push($queries, $query);
            $this->execTransaction($queries);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qQuestionSet
     * @param   $idSet          Integer        Test set's ID
     * @param   $idLanguage     Integer        Student preferred language's ID
     * @param   $idSubject      Integer        Subject's ID
     * @return  Boolean
     * @descr   Returns true if questions set was successfully readed, false otherwise
     */
    public function qQuestionSet($idSet, $idLanguage=null, $idSubject=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            if($idLanguage != null){
                // Get all the set's questions with student's language
                // UNION
                // All questions with default (subject) language NOT IN previuos group
                $query = "SELECT Q.idQuestion, Q.type, Q.extra, TQ.fkLanguage, TQ.translation, SQ.answer
                          FROM
                              Questions AS Q
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              JOIN Sets_Questions AS SQ ON Q.idQuestion = SQ.fkQuestion
                              WHERE
                                  TQ.fkQuestion IN (
                                                  SELECT fkQuestion
                                                  FROM
                                                      Sets_Questions AS SQ
                                                      WHERE
                                                      SQ.fkSet = '$idSet'
                                                  )
                              AND
                              TQ.fkLanguage = '$idLanguage'
                              AND
                              SQ.fkSet = '$idSet'
                          UNION
                          SELECT Q.idQuestion, Q.type, Q.extra, TQ.fkLanguage, TQ.translation, SQ.answer
                          FROM
                              Questions AS Q
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              JOIN Sets_Questions AS SQ ON Q.idQuestion = SQ.fkQuestion
                              WHERE
                                  TQ.fkQuestion NOT IN (
                                      SELECT fkQuestion
                                      FROM
                                          TranslationQuestions AS TQ
                                          WHERE
                                              TQ.fkQuestion IN (
                                                              SELECT fkQuestion
                                                              FROM
                                                                  Sets_Questions AS SQ
                                                                  WHERE
                                                                  SQ.fkSet = '$idSet'
                                                              )
                                              AND
                                              TQ.fkLanguage = '$idLanguage'
                                  )
                                  AND
                                  TQ.fkQuestion IN (
                                                  SELECT fkQuestion
                                                  FROM
                                                      Sets_Questions AS SQ
                                                      WHERE
                                                      SQ.fkSet = '$idSet'
                                                  )
                                  AND
                                  TQ.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                                  AND
                                  SQ.fkSet = '$idSet'
                          ORDER BY idQuestion";
            }else{
                // Get all the set's questions with default (subject) language
                $query = "SELECT Q.idQuestion, Q.type, Q.type, TQ.translation, SQ.answer
                          FROM
                              Questions AS Q
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              JOIN Sets_Questions AS SQ ON Q.idQuestion = SQ.fkQuestion
                              WHERE
                                  TQ.fkQuestion IN (
                                                  SELECT fkQuestion
                                                  FROM
                                                      Sets_Questions AS SQ
                                                      WHERE
                                                      SQ.fkSet = '$idSet'
                                                  )
                              AND
                              SQ.fkSet = '$idSet'\n";
                if($idSubject!=null)
                    $query .= "AND
                               TQ.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')\n";
                $query .= "ORDER BY Q.idQuestion";
            }
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    public function qQuestionSetpl($idSet, $idLanguage=null, $idSubject=null){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            // Get all the set's questions with student's language
            // UNION
            // All questions with default (subject) language NOT IN previuos group
            $query = "SELECT Q.idQuestion, Q.type, Q.type, Q.shorttext, SQ.idAnswer
                          FROM
                              Questions AS Q
                               JOIN Sub_Questions AS TQ ON Q.idQuestion = TQ.fkQuestions
                               JOIN Answers AS SQ ON SQ.fksub = TQ. sub_questions
                               ";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qViewArchivedTest
     * @param   $idTest         Integer        Test's ID
     * @param   $idLanguage     Integer        Student preferred language's ID
     * @param   $idSubject      Integer        Subject's ID
     * @return  Boolean
     * @descr   Returns true if questions set was successfully readed, false otherwise
     */
    public function qViewArchivedTest($idTest, $idLanguage = null, $idSubject){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            if($idLanguage != null){
                // Get all the set's questions with student's language
                // UNION
                // All questions with default (subject) language NOT IN previuos group
                $query = "SELECT Q.idQuestion, Q.type, TQ.fkLanguage, TQ.translation, H.answer, H.score
                          FROM
                              History AS H
                              JOIN Questions AS Q ON Q.idQuestion = H.fkQuestion
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              WHERE
                                  TQ.fkLanguage = '$idLanguage'
                                  AND
                                  H.fkTest = '$idTest'
                          UNION
                          SELECT Q.idQuestion, Q.type, TQ.fkLanguage, TQ.translation, H.answer, H.score
                          FROM
                              History AS H
                              JOIN Questions AS Q ON Q.idQuestion = H.fkQuestion
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              WHERE
                                  H.fkQuestion NOT IN (SELECT fkQuestion
                                                       FROM TranslationQuestions AS TQ
                                                       WHERE
                                                           TQ.fkQuestion IN (SELECT fkQuestion
                                                                             FROM
                                                                                 History AS H
                                                                                 WHERE
                                                                                 H.fkTest = '$idTest')
                                                           AND
                                                           TQ.fkLanguage = '$idLanguage')
                                  AND
                                  TQ.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                                  AND
                                  H.fkTest = '$idTest'
                          ORDER BY Q.idQuestion";
            }else{
                // Get all the set's questions with default (subject) language
                $query = "SELECT Q.idQuestion, Q.type, TQ.translation, H.answer, H.score
                          FROM
                              History AS H
                              JOIN Questions AS Q ON Q.idQuestion = H.fkQuestion
                              JOIN TranslationQuestions AS TQ ON Q.idQuestion = TQ.fkQuestion
                              WHERE
                                  TQ.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                                  AND
                                  H.fkTest = '$idTest'
                          ORDER BY Q.idQuestion";
            }
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    public function  qSubAnswer($idQuestion,$idSubQuestion,$lang){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "SELECT score,translation,idAnswer FROM Answers JOIN TranslationAnswers ON fkAnswer = idAnswer JOIN Questions ON idQuestion = fkQuestion
            WHERE idQuestion ='$idQuestion' AND  fksub = '$idSubQuestion' AND fkLanguage = '$lang'";
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    public function qDeleteSubQuestion($idSubQuestion){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "DELETE FROM Sub_Questions WHERE sub_questions='$idSubQuestion'";
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    /**
     * @name    qAnswerSet
     * @param   $idQuestion     Integer        Test set's ID
     * @param   $idLanguage     Integer        Student preferred language's ID
     * @param   $idSubject      Integer        Subject's ID
     * @return  Boolean
     * @descr   Returns true if answers set was successfully readed, false otherwise
     */
    public function qAnswerSet($idQuestion, $idLanguage = null, $idSubject){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            if($idLanguage != null){
                // Get all the answers of question with student's language
                // UNION
                // All answers with default (subject) language NOT IN previuos group
                $query = "SELECT *
                          FROM
                              Answers AS A
                              JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                              WHERE
                                  A.fkQuestion = '$idQuestion'
                              AND
                                  TA.fkLanguage = '$idLanguage'
                          UNION
                          SELECT *
                          FROM
                              Answers AS A
                              JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                              WHERE
                                  A.idAnswer NOT IN (
                                                    SELECT A.idAnswer
                                                    FROM
                                                        Answers AS A
                                                        JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                                                        WHERE
                                                            A.fkQuestion = '$idQuestion'
                                                            AND
                                                            TA.fkLanguage = '$idLanguage'
                                                    )
                                  AND
                                  A.fkQuestion = '$idQuestion'
                                  AND
                                  TA.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                          ORDER BY idAnswer";
            }else{
                // Get all the answers of question with default (subject) language
                $query = "SELECT *
                          FROM
                              Answers AS A
                              JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                              WHERE
                                  A.fkQuestion = '$idQuestion'
                              AND
                                  TA.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                          ORDER BY A.idAnswer";
            }
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    public function qAnswerSetPL($sub, $idLanguage,$idSubject){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
        if(!(is_numeric($idLanguage))){
          $query = "SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject'";
          $this->execQuery($query);
          $row = $this->getAllAssoc();
          foreach ($row as $x) {
            $idLanguage = $x[0];
          }
        }
            // Get all the answers of question with student's language
            // UNION
            // All answers with default (subject) language NOT IN previuos group
          $query = "SELECT *
                          FROM
                              Answers AS A
                              JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                              JOIN Sub_Questions AS TTA ON    A.fksub = TTA.sub_questions
                              WHERE
                                  TA.fkLanguage = '$idLanguage'
                              AND
                                   A.fksub = '$sub'
                          UNION
                          SELECT *
                          FROM
                              Answers AS A
                              JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                              JOIN Sub_Questions AS TTA ON    A.fksub = TTA.sub_questions
                              WHERE
                                  A.idAnswer NOT IN (
                                                    SELECT A.idAnswer
                                                    FROM
                                                        Answers AS A
                                                        JOIN TranslationAnswers AS TA ON A.idAnswer = TA.fkAnswer
                                                        JOIN Sub_Questions AS TTA ON    A.fksub = TTA.sub_questions
                                                        WHERE
                                                            TA.fkLanguage = '$idLanguage'
                                                            AND
                                                            A.fksub = '$sub'
                                                    )
                                  AND
                                  A.fksub = '$sub'
                                  AND
                                  TA.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')
                          ORDER BY idAnswer";


            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
	/**

     */
    public function qsubquestionsetPL($idQuestion){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT *
                          FROM
                              Sub_Questions AS A JOIN Questions  WHERE
                              A.sub_questions = '$idQuestion'";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
    /**

     */
    public function qsubquestionsettestPL($idQuestion,$idLang,$idSubject){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        if(!(is_numeric($idLang))){
          $query = "SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject'";
          $this->execQuery($query);
          $row = $this->getAllAssoc();
          foreach ($row as $x) {
            $idLang = $x[0];
          }
        }
        
        try{
            $query = "SELECT *
                          FROM
                              Sub_Questions AS SQ
                              JOIN TranslationSubQuestion AS TQ ON TQ.fkSubQuestion = SQ.sub_questions 
                              WHERE SQ.fkQuestions = '$idQuestion' AND TQ.fkLanguage = '$idLang'
                          UNION
                          SELECT *
                          FROM
                              Sub_Questions AS SQ
                              JOIN TranslationSubQuestion AS TQ ON TQ.fkSubQuestion = SQ.sub_questions 
                              WHERE
                                  SQ.sub_questions NOT IN (
                                                SELECT sub_questions FROM
                                                Sub_Questions AS SQ
                                                JOIN TranslationSubQuestion AS TQ ON TQ.fkSubQuestion = SQ.sub_questions 
                                                WHERE SQ.fkQuestions = '$idQuestion' AND TQ.fkLanguage = '$idLang'
                                                    )
                                  AND
                                  SQ.fkQuestions = '$idQuestion'
                                  AND
                                  TQ.fkLanguage = (SELECT fkLanguage FROM Subjects WHERE idSubject = '$idSubject')";
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    public function qsubquestionseatPL($idQuestion){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT text
                          FROM
                              Sub_Questions  JOIN Questions  WHERE
                              A.sub_questions = '$idQuestion'";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }
/*******************************************************************
*                              Utils                               *
*******************************************************************/

    /**
     * @name    qSelect
     * @param   $tableName      String          Table to search
     * @param   $columnName     String          Field to search
     * @param   $value          String|Array    Value to search
     * @param   $order          String          Value to order by
     * @return  Boolean
     * @descr   Search into a table a specific value for a column
     */
    public function qSelect($tableName, $columnName = '', $value = '', $order = ''){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $newValue = (is_array($value))? implode(',', $value) : $value;

            $data = $this->prepareData(array($tableName, $columnName, $newValue, $order));

            $query = "SELECT * FROM $data[0]";
            if(($columnName != '') && (is_array($value)))
                $query .= " WHERE $data[1] IN ($data[2])";
            elseif(($columnName != '') && ($value != ''))
                $query .= " WHERE $data[1] = '$data[2]'";

            if($order != ''){
                $query .= " ORDER BY $data[3]";
            }
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }






    /**
     * @name    qSelectTwoArgs
     * @param   $tableName      String          Table to search
     * @param   $columnName     String          Field to search
     * @param   $value          String|Array    Value to search
     * @param   $columnName2     String          Field to search
     * @param   $value2          String|Array    Value to search
     * @param   $order          String          Value to order by
     * @return  Boolean
     * @descr   Search into a table a specific value for a column
     */
    public function qSelectTwoArgs($tableName, $columnName = '', $value = '',$columnName2 = '', $value2 = '', $order = ''){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $newValue = (is_array($value))? implode(',', $value) : $value;
            $newValue2 = (is_array($value2))? implode(',', $value2) : $value2;

            $data = $this->prepareData(array($tableName, $columnName, $newValue, $columnName2, $newValue2, $order));

            $query = "SELECT * FROM $data[0]";
            if(($columnName != '') && (is_array($value)))
                $query .= " WHERE $data[1] IN ($data[2])";
            elseif(($columnName != '') && ($value != ''))
                $query .= " WHERE $data[1] = '$data[2]'";

            if(($columnName2 != '') && (is_array($value2)))
                $query .= " AND $data[3] IN ($data[4])";
            elseif(($columnName2 != '') && ($value2 != ''))
                $query .= " AND $data[3] = $data[4] ";



            if($order != ''){
                $query .= " ORDER BY $data[5]";
            }
            $log->append($query);

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;

            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDelete
     * @param   $tableName      String        Table to search
     * @param   $columnName     String        Field to search
     * @param   $value          String        Value to delete
     * @return  Boolean
     * @descr   Deletes a specified row(s) in table
     */
    public function qDelete($tableName, $columnName, $value){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $data = $this->prepareData(array($tableName, $columnName, $value));

            $query = "DELETE
                          FROM $data[0]
                      WHERE
                          $data[1] = '$data[2]'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

/*******************************************************************
*                            Languages                             *
*******************************************************************/

    /**
     * @name    qGetAllLanguages
     * @return  Array
     * @descr   Returns an associative array for all system languages
     */
    public function qGetAllLanguages(){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $langs = array();
        try{
            $query = "SELECT *
                      FROM Languages
                      ORDER BY alias";
            $this->execQuery($query);
            while($row = $this->nextRowAssoc()){
                $langs[$row['idLanguage']] = $row['alias'];
            }
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $langs;
    }

    /**
     * @name    qCreateLanguage
     * @param   $alias          String      Language's alias
     * @param   $description    String      Language's description
     * @return  Boolean
     * @descr   Returns true if language was successfully created, false otherwise
     */
    public function qCreateLanguage($alias, $description){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        $langs = array();
        try{
            $data = $this->prepareData(array($alias, $description));
            $query = "INSERT INTO Languages (alias, description)
                      VALUES ('$data[0]', '$data[1]')";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }



    /**
     * @name    qAddImportFlag
     * @return  Boolean
     * @descr   add Import Flag to 1
     */
    public function qUpdateImportFlag(){
        global $log, $user;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $queries = array();
            $query = "Update Flag_Import set done=1";
            $this->execQuery($query);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }
    /*******************************************************************
     *                              Creport                              *
     *******************************************************************/
    /**
     * @name    qShowExams
     * @return  boolean
     * @descr   show searched result in Assesment's select tag
     */
    public function qShowExams($letter,$idUser){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query = "Select * from Subjects JOIN Users_Subjects ON fkSubject = idSubject where name like '".$letter."%' AND fkUser = $idUser";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    echo "<option value='$row[name]'>".$row['name']."</option>";
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowStudentCreport
     * @return  boolean
     * @descr   show participant in exam selected on Coaching Report
     */

    public function qShowStudentCreport($exam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            //check if minscore and maxscore are set
            if(($minscore!=-1)&&($maxscore!=-1)){
                $query="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and Exams.status='a'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (Tests.timeStart BETWEEN '$datein' and '$datefn')";
                $this->execQuery($query);
                if ($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        echo "<option value=".$row['idUser'].">".$row['surname']."&nbsp;".$row['name']."</option>";
                    }
                }
            }
            else{
                $query="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.timeStart BETWEEN '$datein' and '$datefn')";
                $this->execQuery($query);
                if ($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        echo "<option value=".$row['idUser'].">".$row['surname']."&nbsp;".$row['name']."</option>";
                    }
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowTestsCreport
     * @return  boolean
     * @descr   show all tests on creport
     */
    public function qShowTestsCreport($user,$exam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){//minscore and maxscore set
                    $query = "SELECT Users.idUser,Subjects.name AS materia, Users.name, Users.surname,
                        Tests.scoreFinal, DATE_FORMAT(Tests.timeStart,'%d-%m-%Y %H:%i:%s') AS dateTaken,
                        Users.group, Users.subgroup, Tests.status, Tests.idTest
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
                        ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and Exams.status='a'
                        and Users.idUser='$user'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        ORDER BY Tests.timeStart";
                    $this->execQuery($query);
                    echo "<tbody>";
                    if($this->numResultRows()>0){
                        $i=1;
                        while($row=mysqli_fetch_array($this->result)){
                            echo "<tr onclick=showCreportDetails() id=".$row['idTest'].">
                            <td>".$i."</td>
                            <td class=scoreFinal>".$row['scoreFinal']."</td>
                            <td class=dateTaken>".$row['dateTaken']."</td>";
                            if (($row['status']=="a")or($row['status']=="e")) {
                                echo "<td class=status>".ttFinishedNormal."</td>";
                            }
                            if (($row['status']=="b")) {
                                echo "<td class=status>".ttBlocked."</td>";
                            }
                            echo"</tr>";
                            $i++;
                        }
                    }
                    echo "</tbody>";
            }else{
                    $query = "SELECT Users.idUser,Subjects.name AS materia, Users.name, Users.surname,
                        Tests.scoreFinal, DATE_FORMAT(Tests.timeStart,'%d-%m-%Y %H:%i:%s') AS dateTaken,
                        Users.group, Users.subgroup, Tests.status, Tests.idTest
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
                        ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and Users.idUser='$user'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')
                        ORDER BY Tests.timeStart";
                    $this->execQuery($query);
                    echo "<tbody>";
                    if($this->numResultRows()>0){
                        $i=1;
                        while($row=mysqli_fetch_array($this->result)){
                            echo "<tr onclick=showCreportDetails() id=".$row['idTest'].">
                            <td>".$i."</td>
                            <td class=scoreFinal>".$row['scoreFinal']."</td>
                            <td class=dateTaken>".$row['dateTaken']."</td>";
                            if (($row['status']=="a")or($row['status']=="e")) {
                                echo "<td class=status>".ttFinishedNormal."</td>";
                            }
                            if (($row['status']=="b")) {
                                echo "<td class=status>".ttBlocked."</td>";
                            }
                            echo"</tr>";
                            $i++;
                        }
                    }
                    echo "</tbody>";
            }

        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qLoadGroup
     * @return  string
     * @descr   print participant group
     */
    public function qLoadGroup($userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select distinct NameSubGroup,NameGroup
                      from Users AS U JOIN GroupNTC ON idGroup = U.group
                      JOIN SubGroup ON idSubGroup = U.subGroup
                      where U.idUser='$userparam'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val=$row['NameGroup']."  ".$row['NameSubGroup'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }



    /**
     * @name    qLoadTimeUsed
     * @return  string
     * @descr   print test time used
     */
    public function qLoadTimeUsed($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select TIMEDIFF(timeEnd,timeStart) AS time_used
                      from Tests
                      where Tests.idTest='$idTest'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $row=mysqli_fetch_array($this->result);
                $val=$row['time_used'];
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTestTimeLimit
     * @return  string
     * @descr   print test time lmit
     */
    public function qLoadTestTimeLimit($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select TestSettings.duration
                      from Tests JOIN (Exams JOIN TestSettings on Exams.fkTestSetting=TestSettings.idTestSetting)
                      on Tests.fkExam=Exams.idExam
                      where Tests.idTest='$idTest'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $row=mysqli_fetch_array($this->result);
                $val=$row['duration'];
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTestTopic
     * @return  string
     * @descr   print topic name of specific test
     */
    public function qLoadTestTopic($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select Topics.name
                      from Tests JOIN (Exams JOIN (Topics JOIN Topics_TestSettings on Topics.idTopic=Topics_TestSettings.fkTopic)
                      on Exams.fkTestSetting=Topics_TestSettings.fkTestSetting)
                      on Tests.fkExam=Exams.idExam
                      where Tests.idTest='$idTest'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $row=mysqli_fetch_array($this->result);
                $val=$row['name'];
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTestQuestions
     * @return  array
     * @descr   load array of all id questions of the test
     */
    public function qLoadTestQuestions($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select History.fkQuestion
                      from History
                      where History.fkTest='$idTest'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $i=1;
                while($row=mysqli_fetch_array($this->result)){
                    $val[$i]=$row['fkQuestion'];
                    $i++;
                }
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTestNumQuestions
     * @return  array
     * @descr   number of question presented & answered
     */
    public function qLoadTestNumQuestions($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "SELECT COUNT(fkQuestion) as qpresented
                      FROM History
                      WHERE fkTest='$idTest'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                while($row=mysqli_fetch_array($this->result)){
                    $num['qpresented']=$row['qpresented'];
                }
            }

            $query = "SELECT COUNT(fkQuestion) as qanswered
                      FROM History
                      WHERE fkTest='$idTest' and answer is not NULL";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                while($row=mysqli_fetch_array($this->result)){
                    $num['qanswered']=$row['qanswered'];
                }
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $num;
    }


    /**
     * @name    qShowQuestionsDetails
     * @return  array
     * @descr   return array contain all data relative to a question
     */
    public function qShowQuestionsDetails($idTest,$idLang,$idQuestion){
        global $log;
        $this->result=null;
        $this->mysqli=$this->connect();
        $val=array();
        try{
            $query="select Distinct Questions.idQuestion,Topics.name, TranslationQuestions.translation, History.answer,
                    Questions.difficulty, History.score, Questions.type
                    FROM Topics JOIN (History JOIN (TranslationQuestions JOIN Questions
                    on TranslationQuestions.fkQuestion=Questions.idQuestion)
                    on History.fkQuestion=TranslationQuestions.fkQuestion)
                    on Topics.idTopic=Questions.fkTopic
                    where History.fkTest='$idTest'
                    and History.fkQuestion='$idQuestion'
                    and TranslationQuestions.fkLanguage='$idLang'";

            $this->execQuery($query);
            if($this->numResultRows()==0){
              $query="select Distinct Questions.idQuestion,Topics.name, TranslationQuestions.translation, History.answer,
                Questions.difficulty, History.score, Questions.type
                FROM Topics JOIN (History JOIN (TranslationQuestions JOIN Questions
                on TranslationQuestions.fkQuestion=Questions.idQuestion)
                on History.fkQuestion=TranslationQuestions.fkQuestion)
                on Topics.idTopic=Questions.fkTopic
                where History.fkTest='$idTest'
                and History.fkQuestion='$idQuestion'
                and TranslationQuestions.fkLanguage=1";
                $this->execQuery($query);
            }
            if ($this->numResultRows()>0){
              $row=mysqli_fetch_array($this->result);

              $val['questionText']=strip_tags($row['translation']);
              $val['questionText'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['questionText']);
              if($row['difficulty'] == '1'){
                $val['difficulty'] = 'Easy';
              }elseif ($row['difficulty'] == '2') {
                $val['difficulty'] = 'Medium';
              }else{
                $val['difficulty'] = 'Hard';
              }
              $val['idQuestion'] = $row['idQuestion'];
              $val['score']=$row['score'];
              $val['qtype']=$row['type'];
              $val['qtopic']=$row['name'];
              //echo $val['qtype']."  ";
              //risposte scelte dagli studenti
              $text = $row['answer'];
              $text = str_replace('[','',$text);
              $text = str_replace(']','',$text);
              $text = str_replace('"','',$text);
              if($found=strpos($text,",")){
                $arr = explode(",", $text);
                $i = 0;
                foreach ($arr as $answerID) {
                  $query="select translation as textanswer
                     from TranslationAnswers
                     where fkAnswer='$answerID'
                     and fkLanguage='$idLang'";
                  $this->execQuery($query);
                  if($this->numResultRows()>0){
                    $row=mysqli_fetch_array($this->result);
                    if($i == 0){
                      $val['answerNum'] = strip_tags($row['textanswer']);
                      $val['answerNum'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerNum']);
                    }else{
                      $val['answerNum'].= "\n ".preg_replace("/&#?[a-z0-9]+;/i","", strip_tags($row['textanswer']));
                    }    
                  }else{// if language active not present load english answer
                    $query="select translation as textanswer
                    from TranslationAnswers
                    where fkAnswer='$answerID'
                    and fkLanguage='1'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                      $row=mysqli_fetch_array($this->result);
                      if($i == 0){
                        $val['answerNum'] = strip_tags($row['textanswer']);
                        $val['answerNum'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerNum']);
                      }else{
                        $val['answerNum'].= "\n ".preg_replace("/&#?[a-z0-9]+;/i","", strip_tags($row['textanswer']));
                      } 
                    }else{//in caso di risposta true/false o yes/no stampo not ok invece di ok(dato da default)
                      $val['answerNum']="not ok";
                    }
                  }
                  $i++;
                }
              }else{
                $answerID = (int)$text;
                $query="select translation as textanswer
                     from TranslationAnswers
                     where fkAnswer='$answerID'
                     and fkLanguage='$idLang'";
                  $this->execQuery($query);
                  if($this->numResultRows()>0){
                    $row=mysqli_fetch_array($this->result);
                    $val['answerNum']=strip_tags($row['textanswer']);
                    $val['answerNum'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerNum']);
                  }else{// if language active not present load english answer
                    $query="select translation as textanswer
                    from TranslationAnswers
                    where fkAnswer='$answerID'
                    and fkLanguage='1'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                      $row=mysqli_fetch_array($this->result);
                      $val['answerNum']=strip_tags($row['textanswer']);
                      $val['answerNum'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerNum']);
                    }else{//in caso di risposta true/false o yes/no stampo not ok invece di ok(dato da default)
                      $val['answerNum']="not ok";
                    }
                  }
              }
              //risposte totali delle domande

              $query= "select translation
              from TranslationAnswers JOIN Answers ON idAnswer = fkAnswer JOIN Questions ON idQuestion = fkQuestion
              where idQuestion = '$idQuestion' and fkLanguage='$idLang'";
              $this->execQuery($query);
              if($this->numResultRows()>0){
                $answers = $this->getAllAssoc();
                $i = 0;
                foreach ($answers as $answer) {
                  if($i == 0){
                    $val['answerText'] = $i.") ".strip_tags($answer[0]);
                    $val['answerText'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerText']);
                  }else{
                    $val['answerText'] .= "\n".$i.") ".preg_replace("/&#?[a-z0-9]+;/i","",strip_tags($answer[0]));
                  }
                  $i++;
                }
              }else{
                $query= "select translation
                from TranslationAnswers JOIN Answers ON idAnswer = fkAnswer JOIN Questions ON idQuestion = fkQuestion
                where idQuestion = '$idQuestion' and fkLanguage='1'";
                $this->execQuery($query);
                $answers = $this->getAllAssoc();
                $i = 0;
                foreach ($answers as $answer) {
                  if($i == 0){
                    $val['answerText'] = $i.") ".strip_tags($answer[0]);
                    $val['answerText'] = preg_replace("/&#?[a-z0-9]+;/i","", $val['answerText']);
                  }else{
                    $val['answerText'] .= "\n".$i.") ".preg_replace("/&#?[a-z0-9]+;/i","",strip_tags($answer[0]));
                  }
                  $i++;
                }
              }
              if($val['qtype'] == 'ES'){
                $val['answerText'] = "";
              }
           }


            //load number of questions
            if($val['qtype'] == 'ES'){
              $val['maxScore']="";
            }else{
              $query="SELECT COUNT(fkQuestion) as questions
                    FROM History where fkTest='$idTest'";
              $this->execQuery($query);
              if ($this->numResultRows() > 0){
                  $row=mysqli_fetch_array($this->result);
                  $totquestions=$row['questions'];
              }
              $totquestions = 1;
              //load max score question of the test
              $query="select distinct scoreType
                      from Tests JOIN (Exams JOIN TestSettings on Exams.fkTestSetting=TestSettings.idTestSetting)
                      on Tests.fkExam=Exams.idExam
                      where Tests.idTest='$idTest'";
             $this->execQuery($query);
              if ($this->numResultRows() > 0){
                $row=mysqli_fetch_array($this->result);
                  $maxscoretest=$row['scoreType'];
                }
              $maxscoretest = 1;
              $val['maxScore']=$maxscoretest/$totquestions;
            }
            
        }
        catch(Exceptin $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }
	

/*******************************************************************
     *                              AOReport                              *
     *******************************************************************/

    /**
     * @name    qShowGroup
     * @return  boolean
     * @descr   show groups in groups area
     */

    public function qShowGroups($letter,$exams,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            //check if minscore and maxscore are set
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check if date interval has set
                if (($datein=="")&&($datefn=="")){//dates not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group) {
                            foreach($subgroups as $subgr){
                                $x = 0;
                                while ($exams[$x]!=""){
                                    $query2="SELECT DISTINCT Users.group, Users.sugroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' Users.subgroup='$subgr' and Users.role='s'
                        and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=false;
                                    }
                                    $x++;
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }

                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group){
                            foreach($subgroups as $subgr){
                                foreach($allexams as $exam){
                                    $query2="SELECT DISTINCT Users.group, Users.subgroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr'
                        and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                    //echo $query2."\n\n";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exam]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exam]=false;
                                    }
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group) {
                            foreach($subgroups as $subgr) {
                                $x = 0;
                                while ($exams[$x]!=""){
                                    $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.groups='$group' and Users.subgroup='$subgr'
                        and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                                    echo $query2."\n";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=false;
                                    }
                                    $x++;
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($groups as $group){
                            foreach($subgroups as $subgr){
                                foreach($allexams as $exam){
                                    $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr'
                        and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                                    //echo $query2."\n\n";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exam]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exam]=false;
                                    }
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }
                    }
                }

            }
            else{
                //check if date interval has set
                if (($datein=="")&&($datefn=="")){//date not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group) {
                            foreach($subgroups as $subgr){
                                $x = 0;
                                while ($exams[$x]!=""){
                                    $query2="SELECT DISTINCT Users.group, Users.subgroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr' and Users.role='s'
                        and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')";
                                    echo $query2."\n";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=false;
                                    }
                                    $x++;
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                                //print_r($trovato);
                            }

                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $query="Select Subjects.name from Subjects";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($groups as $group){
                            foreach($subgroups as $subgr){
                                foreach($allexams as $exam){
                                    $query2="SELECT DISTINCT Users.group, Users.subgroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr' and Users.role='s'
                        and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exam]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exam]=false;
                                    }
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }

                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group) {
                            foreach($subgroups as $subgr){
                                $x = 0;
                                while ($exams[$x]!=""){
                                    $query2="SELECT DISTINCT Users.group, Users.subgroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr'
                        and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                    //  echo $query2."\n";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exams[$x]]=false;
                                    }
                                    $x++;
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="SELECT distinct Users.group
                    FROM Users
                    WHERE role='s' and Users.group like '$letter%'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $groups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $groups[$i]=$row['group'];
                                $i++;
                            }
                        }

                        $query="SELECT distinct Users.subgroup
                    FROM Users
                    WHERE role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $subgroups=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $subgroups[$i]=$row['subgroup'];
                                $i++;
                            }
                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($groups as $group){
                            foreach($subgroups as $subgr){
                                foreach($allexams as $exam){
                                    $query2="SELECT DISTINCT Users.group, Users.subgroup
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.group='$group' and Users.subgroup='$subgr'
                        and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                    $this->execQuery($query2);
                                    if ($this->numResultRows()>0){
                                        $trovato[$group.'-'.$subgr][$exam]=true;
                                    }
                                    else{
                                        $trovato[$group.'-'.$subgr][$exam]=false;
                                    }
                                }
                                if (in_array(false,$trovato[$group.'-'.$subgr])){
                                    $notpresent[$d]=true;
                                }
                                else{
                                    $row=mysqli_fetch_array($this->result);
                                    echo "<option value='$row[group]-$row[subgroup]'>".$row['group']."-".$row['subgroup']."</option>";
                                    $notpresent[$d]=false;
                                }
                                $d++;
                            }
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportGroupNotPresent."</option>";
                        }
                    }
                }

            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowStudent
     * @return  boolean
     * @descr   show searched result in Partecipant's select tag
     */
    public function qShowStudent($exams,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            //check if minscore and maxscore are set
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check if date interval has set
                if (($datein=="")&&($datefn=="")){//dates not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }
                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                // echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                                echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }
                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                // echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }
                    }
                }

            }
            else{
                //check if date interval has set
                if (($datein=="")&&($datefn=="")){//date not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')";
                                //  echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }
                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                // echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }

                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //  echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }

                    }
                    else{
                        //all exams should be controlled
                        $query="Select idUser from Users where role='s'";
                        $this->execQuery($query);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $students=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $students[$i]=$row['idUser'];
                                $i++;
                            }
                        }
                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                // echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>".ttReportStudentNotPresent."</option>";
                        }
                    }
                }

            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowStudentGroup
     * @return  boolean
     * @descr   show searched result in Partecipant's select tag filter by group
     */
    public function qShowStudentGroup($groups,$exams,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            //check if minscore and maxscore are set
            if(($minscore!=-1)&&($maxscore!=-1)) {
                //check if date interval has set
                if (($datein=="")&&($datefn=="")){//dates not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }

                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            echo $gval."\n";
                            foreach($subgroups as $sbgr){
                                echo $sbgr."\n";
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        //search students that done tests of assesment selected in group selected
                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                //echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }


                    }
                    else{
                        //all exams should be controlled
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            foreach($subgroups as $sbgr){
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                //  echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a') and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            echo $gval."\n";
                            foreach($subgroups as $sbgr){
                                echo $sbgr."\n";
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        //search students that done tests of assesment selected in group selected
                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }


                    }
                    else{
                        //all exams should be controlled
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            foreach($subgroups as $sbgr){
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                //  echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }
                    }
                }

            }
            else{
                if (($datein=="")&&($datefn=="")){//date not set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            echo $gval."\n";
                            foreach($subgroups as $sbgr){
                                echo $sbgr."\n";
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        //search students that done tests of assesment selected in group selected
                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')";
                                //echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }


                    }
                    else{
                        //all exams should be controlled
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            foreach($subgroups as $sbgr){
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            foreach($allexams as $exam){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }
                    }
                }
                else{//date set
                    // 2 cases, 1 exams are selected specifically; 2 all exams to control
                    if (($exams[0]!="") or ($exams[0]!=null)){
                        //exams are selected in this case
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            echo $gval."\n";
                            foreach($subgroups as $sbgr){
                                echo $sbgr."\n";
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        //search students that done tests of assesment selected in group selected
                        $d=0;
                        foreach($students as $student) {
                            $x = 0;
                            while ($exams[$x]!=""){
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exams[$x]' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //echo $query2."\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exams[$x]]=true;
                                }
                                else{
                                    $trovato[$student][$exams[$x]]=false;
                                }
                                $x++;
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }


                    }
                    else{
                        //all exams should be controlled
                        $g=0;
                        $gr=Array();
                        $subgroups=Array();
                        //divide group and subgroup for search students
                        while ($groups[$g]!=""){
                            $parts=explode("-",$groups[$g]);
                            if(in_array($parts[0],$gr)){
                                //do nothing
                            }
                            else{
                                $gr[$g]=$parts[0];
                            }
                            if(in_array($parts[1],$subgroups)){
                                //do nothing
                            }
                            else{
                                $subgroups[$g]=$parts[1];
                            }

                            $g++;
                        }


                        $students=array();
                        $i=0;
                        foreach($gr as $gval){
                            foreach($subgroups as $sbgr){
                                $query="Select Users.idUser from Users where Users.role='s' and Users.group='$gval' and Users.subgroup='$sbgr'";
                                $this->execQuery($query);
                                if ($this->numResultRows()>0){
                                    while($row=mysqli_fetch_array($this->result)){
                                        $students[$i]=$row['idUser'];
                                        $i++;
                                    }
                                }
                            }

                        }

                        $query2="Select Subjects.name from Subjects";
                        $this->execQuery($query2);
                        if ($this->numResultRows()>0){
                            $i=0;
                            $allexams=array();
                            while($row=mysqli_fetch_array($this->result)){
                                $allexams[$i]=$row['name'];
                                $i++;
                            }
                        }
                        $d=0;
                        foreach($students as $student){
                            //echo $student."\n";
                            foreach($allexams as $exam){
                                //  echo $exam."\n";
                                $query2="SELECT DISTINCT Users.idUser, Users.name, Users.surname
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Users.idUser='$student' and Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) between '$datein' and '$datefn')";
                                //echo $query2."\n\n";
                                $this->execQuery($query2);
                                if ($this->numResultRows()>0){
                                    $trovato[$student][$exam]=true;
                                }
                                else{
                                    $trovato[$student][$exam]=false;
                                }
                            }
                            if (in_array(false,$trovato[$student])){
                                $notpresent[$d]=true;
                            }
                            else{
                                $row=mysqli_fetch_array($this->result);
                                echo "<option value='$row[idUser]'>".$row['surname']."&nbsp;".$row['name']."</option>";
                                $notpresent[$d]=false;
                            }
                            $d++;
                            //print_r($trovato);
                        }
                        if ((in_array(false,$notpresent))){
                        }
                        else{
                            echo "<option>Nessuno studente presente</option>";
                        }
                    }
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qAddStudent
     * @return  boolean
     * @descr   Add the selected student in the realative textarea
     */
    public function qAddStudent($userid){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $sql="Select distinct Users.idUser, Users.surname, Users.name from Users JOIN (Tests JOIN Exams ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser where Users.idUser='$userid'";
            $this->execQuery($sql);
            if ($this->numResultRows()>0){
                $row=mysqli_fetch_array($this->result);
                echo $row['surname']."&nbsp;".$row['name'];
            }
        } catch (Exception $ex) {
            $ack = false;
            $log->append(__FUNCTION__ . " : " . $this->getError());
        }
        return $ack;
    }
    /**
     * @name    qShowStudentDetails
     * @return  boolean
     * @descr   show userid and email of the student selected in the lightbox
     */
    public function qShowStudentDetails($userid){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query="Select email, idUser from Users where idUser='$userid'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    echo "<option value='$row[idUser]'>User_".$row['idUser']."</option>";
                    echo "<option value='$row[email]'>".$row['email']."</option>";
                }
            }
            else{
                echo "<option>".ttReportErrorDetail."</option>";
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowAssesmentName
     * @return  boolean
     * @descr   print assesment name
     */
    public function qShowAssesmentName($exam){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query="Select distinct Exams.name
             from Exams
             where Exams.name='$exam'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    echo $row['name']."\n";
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qShowAssesmentID
     * @return  string
     * @descr   print assesment ID
     */
    public function qShowAssesmentID($exam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query="Select Subjects.idSubject
             from Subjects
             where Subjects.name='$exam'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    $val=$row['idSubject'];
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentAuthor
     * @return  string
     * @descr   print assesment author
     */
    public function qShowAssesmentAuthor($exam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $query="select Users.name, Users.surname
                    FROM Users JOIN (Users_Subjects JOIN Subjects ON Users_Subjects.fkSubject=Subjects.idSubject)
                    ON Users.idUser=Users_Subjects.fkUser
                    where Subjects.name='$exam' ";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    return $row['surname']." ".$row['name'];
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
    }

    /**
     * @name    qShowAssesmentDateTimeFirstTaken
     * @return  string
     * @descr   print assesment date/time first taken
     */
    public function qShowAssesmentDateTimeFirstTaken($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if (($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['first'];
                            }
                        }
                    }
                }

            }


        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentDateTimeLastTaken
     * @return  boolean
     * @descr   print assesment date/time last taken
     */
    public function qShowAssesmentDateTimeLastTaken($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam' and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam' and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                    else{
                        $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['last'];
                            }
                        }
                    }
                }

            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentNumberStarted
     * @return  string
     * @descr   print number of times started of the exam
     */
    public function qShowAssesmentNumberStarted($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            //check if the minscore or the maxscore are selected and execute the relative query
            if(($minscore!=-1) && ($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['numberstart'];
                            }
                        }
                    }
                }

            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentNumberNotFinished
     * @return  string
     * @descr   print number of times not finished of the exam
     */
    public function qShowAssesmentNumberNotFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.idUser='$userparam' and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.email='$userparam' and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['notfinished'];
                            }
                        }
                    }
                }
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentNumberFinished
     * @return  string
     * @descr   print number of times finished of the exam
     */
    public function qShowAssesmentNumberFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                    else{
                        $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finished'];
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMinScoreFinished
     * @return  string
     * @descr   print minimum score
     */
    public function qShowAssesmentMinScoreFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }

            //print max score for this assesment
            $found=strpos($userparam,"@");
            if ($found==false) {
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMaxScoreFinished
     * @return  string
     * @descr   print max score
     */
    public function qShowAssesmentMaxScoreFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }
            //print max score for this assesment
            $found=strpos($userparam,"@");
            if ($found==false) {
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMedScoreFinished
     * @return  string
     * @descr   print medium score
     */
    public function qShowAssesmentMedScoreFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['finalscore'];
                            }
                        }
                    }
                }

            }

            //print max score for this assesment
            $found=strpos($userparam,"@");
            if ($found==false) {
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentLeastTimeFinished
     * @return  string
     * @descr   print least time of assesment finished
     */
    public function qShowAssesmentLeastTimeFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                    else{
                        $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['mintime'];
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMostTimeFinished
     * @return  string
     * @descr   print medium score
     */
    public function qShowAssesmentMostTimeFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                    else{
                        $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['maxtime'];
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMediumTimeFinished
     * @return  string
     * @descr   print medium score
     */
    public function qShowAssesmentMediumTimeFinished($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['medtime'];
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentStdDeviation
     * @return  string
     * @descr   print std deviation
     */
    public function qShowAssesmentStdDeviation($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                    else{
                        $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            while($row=mysqli_fetch_array($this->result)){
                                $val=$row['stddeviation'];
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentDateTimeFirstTakenGroup
     * @return  string
     * @descr   print assesment date/time first taken
     */
    public function qShowAssesmentDateTimeFirstTakenGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['first'];
                        }
                    }
                }
                else{//dates set
                    $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['first'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['first'];
                        }
                    }
                }
                else{//dates set
                    $query="select DATE_FORMAT(MIN(Tests.timeStart),'%d-%m-%Y %H:%i') AS first
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['first'];
                        }
                    }
                }


            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentDateTimeLastTakenGroup
     * @return  string
     * @descr   print assesment date/time last taken
     */
    public function qShowAssesmentDateTimeLastTakenGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['last'];
                        }
                    }
                }
                else{//dates set
                    $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['last'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['last'];
                        }
                    }
                }
                else{//dates set
                    $query="select DATE_FORMAT(MAX(Tests.timeStart),'%d-%m-%Y %H:%i') AS last
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['last'];
                        }
                    }
                }


            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentDateTimeLastTakenGroup
     * @return  string
     * @descr   print assesment number of times started
     */
    public function qShowAssesmentNumberStartedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]' and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['numberstart'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['numberstart'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['numberstart'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.timeStart) AS numberstart
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['numberstart'];
                        }
                    }
                }


            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentNumberNotFinished
     * @return  string
     * @descr   print number of times not finished of the exam
     */
    public function qShowAssesmentNumberNotFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b'
                        and Users.group='$groups[0]' and Users.group='$groups[1]' and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['notfinished'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b'
                        and Users.group='$groups[0]' and Users.group='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['notfinished'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.group='$groups[0]' and Users.group='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['notfinished'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.idTest) AS notfinished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and Tests.status='b' and Users.group='$groups[0]' and Users.group='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['notfinished'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentNumberFinishedGroup
     * @return  string
     * @descr   print number of times finished of the exam
     */
    public function qShowAssesmentNumberFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finished'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finished'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finished'];
                        }
                    }
                }
                else{//dates set
                    $query="select COUNT(Tests.idTest) AS finished
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finished'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMinScoreFinishedGroup
     * @return  string
     * @descr   print min score of finished assesment
     */
    public function qShowAssesmentMinScoreFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select MIN(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }

            //print max score for this assesment
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
            and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/" . $row['scoreType'];
                }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMaxScoreFinishedGroup
     * @return  string
     * @descr   print max score of finished assesment
     */
    public function qShowAssesmentMaxScoreFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select MAX(Tests.scoreFinal) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }

            //print max score for this assesment
            $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
            and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
            $this->execQuery($sql);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val .= "/" . $row['scoreType'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMedScoreFinishedGroup
     * @return  string
     * @descr   print med score of finished assesment
     */
    public function qShowAssesmentMedScoreFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(AVG(Tests.scoreFinal),2) AS finalscore
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['finalscore'];
                        }
                    }
                }

            }

            //print max score for this assesment
            $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM Users JOIN (Tests JOIN(Subjects JOIN (Exams JOIN TestSettings ON Exams.fkTestSetting=TestSettings.idTestSetting)
            ON Subjects.idSubject=Exams.fkSubject)
            ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
            WHERE Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
            and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
            $this->execQuery($sql);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val .= "/" . $row['scoreType'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentLeastTimeFinishedGroup
     * @return  string
     * @descr   print least time of assesment
     */
    public function qShowAssesmentLeastTimeFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['mintime'];
                        }
                    }
                }
                else{//dates set
                    $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['mintime'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['mintime'];
                        }
                    }
                }
                else{//dates set
                    $query="select MIN(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS mintime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['mintime'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMostTimeFinishedGroup
     * @return  string
     * @descr   print most time of assesment
     */
    public function qShowAssesmentMostTimeFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['maxtime'];
                        }
                    }
                }
                else{//dates set
                    $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['maxtime'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['maxtime'];
                        }
                    }
                }
                else{//dates set
                    $query="select MAX(TIMEDIFF(Tests.timeEnd, Tests.timeStart)) AS maxtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['maxtime'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentMediumTimeFinishedGroup
     * @return  string
     * @descr   print medium time of assesment
     */
    public function qShowAssesmentMediumTimeFinishedGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['medtime'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['medtime'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['medtime'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(AVG(TIMEDIFF(Tests.timeEnd, Tests.timeStart))/60,2) AS medtime
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['medtime'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowAssesmentStdDeviationGroup
     * @return  string
     * @descr   print medium time of assesment
     */
    public function qShowAssesmentStdDeviationGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['stddeviation'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['stddeviation'];
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['stddeviation'];
                        }
                    }
                }
                else{//dates set
                    $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        while($row=mysqli_fetch_array($this->result)){
                            $val=$row['stddeviation'];
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTopicUsers
     * @return  array
     * @descr   load an array of topics correlate to user selected's tests
     */
    public function qLoadTopicUser($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam'
                        and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam'
                        and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $usertopics=array();
                            $index=0;
                            while($row=mysqli_fetch_array($this->result)){
                                $usertopics[$index]=$row['name'];
                                $index++;
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $usertopics;
    }

    /**
     * @name    qShowTopicMedScore
     * @return  string
     * @descr   show med score of participant's topic
     */
    public function qShowTopicMedScore($topic,$userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $found=strpos($userparam,"@");
            if ($found==false){
                $query="SELECT ROUND(AVG (Tests.scoreFinal),2) as avgtopic 
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.idUser='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['avgtopic'];
                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $query="SELECT ROUND(AVG (Tests.scoreFinal),2) as avgtopic 
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.email='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){

                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['avgtopic'];
                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicMinScore
     * @return  string
     * @descr   show min score of participant's topic
     */
    public function qShowTopicMinScore($topic,$userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $found=strpos($userparam,"@");
            if ($found==false){
                $query="select MIN(Tests.scoreFinal) AS mintopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.idUser='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['mintopic'];
                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $query="select MIN(Tests.scoreFinal) AS mintopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.email='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){

                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['mintopic'];

                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicMaxScore
     * @return  string
     * @descr   show max score of particitant's topics
     */
    public function qShowTopicMaxScore($topic,$userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $found=strpos($userparam,"@");
            if ($found==false){
                $query="select MAX(Tests.scoreFinal) AS maxtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.idUser='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['maxtopic'];
                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.idUser='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
            else{
                $query="select MAX(Tests.scoreFinal) AS maxtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.email='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){

                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['maxtopic'];

                    }
                }
                //print max for this topic
                $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.email='$userparam'";
                $this->execQuery($sql);
                if ($this->numResultRows() > 0) {
                    $row = mysqli_fetch_array($this->result);
                    $val .= "/".$row['scoreType'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicStdDeviation
     * @return  string
     * @descr   show std deviation of particitant's topics
     */
    public function qShowTopicStdDeviation($topic,$userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            $found=strpos($userparam,"@");
            if ($found==false){
                $query="select ROUND(STD(Tests.scoreFinal),2) AS stdtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.idUser='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){
                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['stdtopic'];
                    }
                }
            }
            else{
                $query="select ROUND(STD(Tests.scoreFinal),2) AS stdtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.email='$userparam'";
                $this->execQuery($query);
                if($this->numResultRows()>0){

                    while($row=mysqli_fetch_array($this->result)){
                        $val=$row['stdtopic'];

                    }
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadTopicGroup
     * @return  array
     * @descr   load an array of topics correlate to group selected's tests
     */
    public function qLoadTopicGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $topics=array();
                        $index=0;
                        while($row=mysqli_fetch_array($this->result)){
                            $topics[$index]=$row['name'];
                            $index++;
                        }
                    }
                }
                else{//dates set
                    $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $topics=array();
                        $index=0;
                        while($row=mysqli_fetch_array($this->result)){
                            $topics[$index]=$row['name'];
                            $index++;
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $topics=array();
                        $index=0;
                        while($row=mysqli_fetch_array($this->result)){
                            $topics[$index]=$row['name'];
                            $index++;
                        }
                    }
                }
                else{//dates set
                    $query="select distinct Topics.name
                        FROM Subjects JOIN (Users JOIN (Topics JOIN (Topics_TestSettings JOIN
                        (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                        on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                        on Topics.idTopic=Topics_TestSettings.fkTopic) on Users.idUser=Tests.fkUser)
                        on Subjects.idSubject=Exams.fkSubject
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $topics=array();
                        $index=0;
                        while($row=mysqli_fetch_array($this->result)){
                            $topics[$index]=$row['name'];
                            $index++;
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $topics;
    }

    /**
     * @name    qShowTopicMedScoreGroup
     * @return  string
     * @descr   show med score of participant's topic
     */
    public function qShowTopicMedScoreGroup($topic,$groupparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            $query="select ROUND(AVG (Tests.scoreFinal),2) AS avgtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.group='$groups[0]'
                and Users.subgroup='$groups[1]'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    $val=$row['avgtopic'];
                }
            }

            //print max for this topic
            $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
            $this->execQuery($sql);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val .= "/".$row['scoreType'];
            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicMindScoreGroup
     * @return  string
     * @descr   show min score of participant's topic
     */
    public function qShowTopicMinScoreGroup($topic,$groupparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            $query="select MIN(Tests.scoreFinal) AS mintopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.group='$groups[0]'
                and Users.subgroup='$groups[1]'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    $val=$row['mintopic'];
                }
            }
            //print max for this topic
            $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
            $this->execQuery($sql);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val .= "/".$row['scoreType'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicMaxScoreGroup
     * @return  string
     * @descr   show max score of participant's topic
     */
    public function qShowTopicMaxScoreGroup($topic,$groupparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            $query="select MAX(Tests.scoreFinal) AS maxtopic
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.group='$groups[0]'
                and Users.subgroup='$groups[1]'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    $val=$row['maxtopic'];
                }
            }
            //print max for this topic
            $sql = "SELECT DISTINCT TestSettings.scoreType
            FROM TestSettings JOIN (Users JOIN (Topics JOIN (Subjects JOIN (Exams JOIN Tests ON Exams.idExam=Tests.fkExam)
            ON Subjects.idSubject=Exams.fkSubject) ON Topics.fkSubject=Subjects.idSubject)
            ON Users.idUser=Tests.fkUser) ON TestSettings.idTestSetting=Exams.fkTestSetting
            WHERE Topics.name='$topic' and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
            $this->execQuery($sql);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
                $val .= "/".$row['scoreType'];
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qShowTopicStdDeviationGroup
     * @return  string
     * @descr   show std deviation of participant's topic
     */
    public function qShowTopicStdDeviationGroup($topic,$groupparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            $query="select ROUND(STD(Tests.scoreFinal),2) AS stddeviation
                FROM Users JOIN (Topics JOIN (Topics_TestSettings JOIN 
                (Exams JOIN Tests on Exams.idExam=Tests.fkExam) 
                on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                on Topics.idTopic=Topics_TestSettings.fkTopic)
                on Users.idUser=Tests.fkUser
                where Topics_TestSettings.numQuestions > 0
                and Topics.name='$topic'
                and Users.group='$groups[0]'
                and Users.subgroup='$groups[1]'";
            $this->execQuery($query);
            if($this->numResultRows()>0){
                while($row=mysqli_fetch_array($this->result)){
                    $val=$row['stddeviation'];
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadAssesmentScores
     * @return  array
     * @descr   load an array of assesments scores
     */
    public function qLoadAssesmentScores($exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.idUser='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                    else{
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a') and Users.email='$userparam'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $index=1;
                            while($row=mysqli_fetch_array($this->result)){
                                $assesmentsdata[$index]=$row['scoreFinal'];
                                $index++;
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $assesmentsdata;
    }

    /**
     * @name    qLoadAssesmentScoresGroup
     * @return  array
     * @descr   load an array of assesments scores
     */
    public function qLoadAssesmentScoresGroup($exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $index=1;
                        while($row=mysqli_fetch_array($this->result)){
                            $assesmentsdata[$index]=$row['scoreFinal'];
                            $index++;
                        }
                    }
                }
                else{//dates set
                    $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $index=1;
                        while($row=mysqli_fetch_array($this->result)){
                            $assesmentsdata[$index]=$row['scoreFinal'];
                            $index++;
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $index=1;
                        while($row=mysqli_fetch_array($this->result)){
                            $assesmentsdata[$index]=$row['scoreFinal'];
                            $index++;
                        }
                    }
                }
                else{//dates set
                    $query="select Tests.scoreFinal
                        FROM Users JOIN (Tests JOIN(Exams JOIN Subjects ON Exams.fkSubject=Subjects.idSubject)
                        ON Tests.fkExam=Exams.idExam) ON Users.idUser=Tests.fkUser
                        where Subjects.name='$exam' and (Tests.status='e' or Tests.status='a')
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if($this->numResultRows()>0){
                        $index=1;
                        while($row=mysqli_fetch_array($this->result)){
                            $assesmentsdata[$index]=$row['scoreFinal'];
                            $index++;
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $assesmentsdata;
    }

    /**
     * @name    qLoadTopicScores
     * @return  array
     * @descr   load an array of topic scores for the histogram
     */
    public function qLoadTopicScores($usertopics,$exam,$userparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.idUser='$userparam'
                            and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }

                    }
                    else{
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.email='$userparam'
                            and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.idUser='$userparam'
                            and (Tests.scoreFinal between '$minscore' and '$maxscore')
                            and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }

                    }
                    else{
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.email='$userparam'
                            and (Tests.scoreFinal between '$minscore' and '$maxscore')
                            and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.idUser='$userparam'";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }

                    }
                    else{
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.email='$userparam'";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }
                    }
                }
                else{//dates set
                    $found=strpos($userparam,"@");
                    if ($found==false){
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.idUser='$userparam'
                            and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }

                    }
                    else{
                        foreach($usertopics as $topic){
                            $query="SELECT Tests.scoreFinal
                            FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                            and Topics.name='$topic'
                            and Subjects.name='$exam'
                            and Users.email='$userparam'
                            and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                            $this->execQuery($query);
                            if($this->numResultRows()>0){
                                $i=1;
                                while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                                }
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $topicsdata;
    }

    /**
     * @name    qLoadTopicScoresGroup
     * @return  array
     * @descr   load an array of topic scores
     */
    public function qLoadTopicScoresGroup($grouptopics,$exam,$groupparam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        $groups=explode("-",$groupparam);
        try {
            if (($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    foreach($grouptopics as $topic){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Topics.name='$topic'
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $i=1;
                            while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                            }
                        }
                    }
                }
                else{//dates set
                    foreach($grouptopics as $topic){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                            where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Topics.name='$topic'
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (Tests.scoreFinal between '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $i=1;
                            while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                            }
                        }
                    }
                }


            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    foreach($grouptopics as $topic){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Topics.name='$topic'
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $i=1;
                            while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                            }
                        }
                    }
                }
                else{//dates set
                    foreach($grouptopics as $topic){
                        $query="select Tests.scoreFinal
                        FROM Users JOIN (Subjects JOIN (Topics JOIN
                            (Topics_TestSettings JOIN (Exams JOIN Tests on Exams.idExam=Tests.fkExam)
                            on Topics_TestSettings.fkTestSetting=Exams.fkTestSetting)
                            on Topics.idTopic=Topics_TestSettings.fkTopic)
                            on Subjects.idSubject=Exams.fkSubject)
                            on Users.idUser=Tests.fkUser
                        where Topics_TestSettings.numQuestions > 0
                        and Subjects.name='$exam' and Topics.name='$topic'
                        and Users.group='$groups[0]' and Users.subgroup='$groups[1]'
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                        $this->execQuery($query);
                        if($this->numResultRows()>0){
                            $i=1;
                            while($row=mysqli_fetch_array($this->result)){
                                    $topicindex=$topic;
                                    $topicindex .="_".$i;
                                    $topicsdata[$topicindex]=$row['scoreFinal'];
                                    $i++;
                            }
                        }
                    }
                }

            }

        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $topicsdata;
    }

    /**
     * @name    qLoadStudent
     * @return  string
     * @descr   print name of student by selected parameter
     */
    public function qLoadStudent($userparam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $found=strpos($userparam,"@");
            if ($found==false) {
                $query = "select Users.name,Users.surname
                          from Users
                          where Users.idUser='$userparam'";
                $this->execQuery($query);
                if ($this->numResultRows() > 0) {
                    while ($row = mysqli_fetch_array($this->result)) {
                        $val=$row['name']." ".$row['surname'];
                    }
                }
            }
            else{
                $query = "select Users.name,Users.surname
                          from Users
                          where Users.email='$userparam'";
                $this->execQuery($query);
                if ($this->numResultRows() > 0) {
                    while ($row = mysqli_fetch_array($this->result)) {
                        $val=$row['name']." ".$row['surname'];
                    }
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qLoadAllStudent
     * @return  array
     * @descr   load all user
     */
    public function qLoadAllStudent($exam,$minscore,$maxscore,$datein,$datefn){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            if(($minscore!=-1)&&($maxscore!=-1)){
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query ="SELECT DISTINCT Users.idUser
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')";
                    $this->execQuery($query);
                    if ($this->numResultRows() > 0) {
                        $students=array();
                        $i=0;
                        while ($row = mysqli_fetch_array($this->result)) {
                            $students[$i]=$row['idUser'];
                            $i++;
                        }
                    }
                }
                else{//dates set
                    $query ="SELECT DISTINCT Users.idUser
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (Tests.scoreFinal BETWEEN '$minscore' and '$maxscore')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if ($this->numResultRows() > 0) {
                        $students=array();
                        $i=0;
                        while ($row = mysqli_fetch_array($this->result)) {
                            $students[$i]=$row['idUser'];
                            $i++;
                        }
                    }
                }

            }
            else{
                //check dates interval has set
                if(($datein=="")&&($datefn=="")){//dates not set
                    $query ="SELECT DISTINCT Users.idUser
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')";
                    $this->execQuery($query);
                    if ($this->numResultRows() > 0) {
                        $students=array();
                        $i=0;
                        while ($row = mysqli_fetch_array($this->result)) {
                            $students[$i]=$row['idUser'];
                            $i++;
                        }
                    }
                }
                else{//dates set
                    $query ="SELECT DISTINCT Users.idUser
                        FROM Users JOIN (Subjects JOIN(Exams JOIN Tests ON Exams.idExam=Tests.fkExam) ON Subjects.idSubject=Exams.fkSubject)
                        ON Users.idUser=Tests.fkUser
                        WHERE Subjects.name='$exam' and (Exams.status='e' or Exams.status='a')
                        and (DATE(Tests.timeStart) BETWEEN '$datein' and '$datefn')";
                    $this->execQuery($query);
                    if ($this->numResultRows() > 0) {
                        $students=array();
                        $i=0;
                        while ($row = mysqli_fetch_array($this->result)) {
                            $students[$i]=$row['idUser'];
                            $i++;
                        }
                    }
                }

            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $students;
    }

    /**
     * @name    qLoadExams
     * @return  array
     * @descr   load array of all exams
     */
    public function qLoadExams(){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select distinct Subjects.name
                      from Subjects";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $i=0;
                while ($row = mysqli_fetch_array($this->result)) {
                    $val[$i]=$row['name'];
                    $i++;
                }
            }
        }
        catch(Exception $ex){
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $val;
    }

    /**
     * @name    qInsertTemplate
     * @return  boolean
     * @descr   insert a Report Template
     */
    public function qInsertTemplate($name,$assesmentName,$assesmentID,$assesmentAuthor,$assesmentDateTimeFirst,$assesmentDateTimeLast,$assesmentNumberStarted,$assesmentNumberNotFinished,$assesmentNumberFinished,$assesmentMinscoreFinished,$assesmentMaxscoreFinished,$assesmentMediumFinished,$assesmentLeastTimeFinished,$assesmentMostTimeFinished,$assesmentMediumTimeFinished,$assesmentStdDeviation,$topicAverageScore,$topicMinimumScore,$topicMaximumScore,$topicStdDeviation,$graphicHistogram,$graphicTopicScore){
        global $log,$user;
        $this->result = null;
        $ack=true;
        $this->mysqli = $this->connect();
        try{
            $query = "INSERT INTO ReportTemplate (name,assesmentName,assesmentID,assesmentAuthor,assesmentDateTimeFirst,
                      assesmentDateTimeLast,assesmentNumberStarted,assesmentNumberNotFinished,assesmentNumberFinished,
                      assesmentMinscoreFinished,assesmentMaxscoreFinished,assesmentMediumFinished,assesmentLeastTimeFinished,
                      assesmentMostTimeFinished,assesmentMediumTimeFinished,assesmentStdDeviation,topicAverageScore,topicMinimumScore,
                      topicMaximumScore,topicStdDeviation, graphicHistogram,graphicTopicScore,fkUser)
                     VALUES ('$name','$assesmentName','$assesmentID','$assesmentAuthor','$assesmentDateTimeFirst',
                     '$assesmentDateTimeLast','$assesmentNumberStarted','$assesmentNumberNotFinished',
                     '$assesmentNumberFinished','$assesmentMinscoreFinished','$assesmentMaxscoreFinished','$assesmentMediumFinished',
                     '$assesmentLeastTimeFinished','$assesmentMostTimeFinished','$assesmentMediumTimeFinished','$assesmentStdDeviation',
                     '$topicAverageScore','$topicMinimumScore',
                     '$topicMaximumScore','$topicStdDeviation','$graphicHistogram','$graphicTopicScore','$user->id')";
            $this->execQuery($query);
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qLoadReportTemplate
     * @return  string
     * @descr   load template already save by the current user
     */
    public function qLoadReportTemplate(){
        global $log,$user;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "SELECT ReportTemplate.name
                    FROM ReportTemplate
                    WHERE ReportTemplate.fkUser='$user->id'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                echo "<option>".ttReportSelectTemplate."</option>";
                while ($row = mysqli_fetch_array($this->result)) {
                    echo "<option value=".$row['name'].">".$row['name']."</option>";
                }
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qLoadCheckboxTemplate
     * @return  array
     * @descr   load checkbox depends to Template selected
     */
    public function qLoadCheckboxTemplate($tname){
        global $log;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "SELECT *
                    FROM ReportTemplate
                    WHERE ReportTemplate.name='$tname'";
            $this->execQuery($query);
            if ($this->numResultRows() > 0) {
                $row = mysqli_fetch_array($this->result);
            }
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $row;
    }

/**
     * @name    qDeleteReportTemplate
     * @return  boolean
     * @descr   delete a template
     */
    public function qDeleteReportTemplate($template_name){
        global $log,$user;
        $ack=true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "DELETE FROM ReportTemplate
                    WHERE ReportTemplate.fkUser='$user->id'
		    AND ReportTemplate.name='$template_name'";
            $this->execQuery($query);
        }
        catch(Exception $ex){
            $ack=false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
        return $ack;
    }

    /**
     * @name    qStudents
     * @return  boolean
     * @descr   select students
     */

    public function qStudents(){
        global $log;

        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
                $query = "SELECT DISTINCT *
                          FROM
                              Users
                          WHERE 
                              Users.role = 's'
                          
                          ORDER BY Users.surname";
                $this->execQuery($query);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }
    public function qAdminsTeachers(){
        global $log;

        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{
            $query = "SELECT DISTINCT *
                          FROM
                              Users
                          WHERE 
                              Users.role = 't'
                          OR 
                              Users.role= 'a'
                          OR 
                              Users.role = 'at'
                          OR 
                              Users.role = 'e'
                          OR 
                              Users.role = 'er'
                          
                          ORDER BY Users.surname";
            $this->execQuery($query);

        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateStudentInfo
     * @param   $idStudent       String        Requested Student's ID
     * @param   $name            String        Student's name
     * @param   $surname         String        Student's surname
     * @param   $email           String        Student's email
     * @param   $group           String        Student's group
     * @param   $subgroup        String        Student's subgroup
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateStudentInfo($idUser, $name,$surname,$email,$group,$subgroup,$role){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{

            $query = "UPDATE Users
                      SET
                          name = '$name',
                          surname = '$surname',
                          email = '$email',
                          `group` = '$group',
                          `subgroup` = '$subgroup',
                          role = '$role'
                      WHERE
                          idUser = '$idUser'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateTeacherInfo
     * @param   $idTeacher       String        Requested Teacher's ID
     * @param   $name            String        Teacher's name
     * @param   $surname         String        Teacher's surname
     * @param   $email           String        Teacher's email
     * @param   $group           String        Teacher's group
     * @param   $subgroup        String        Teacher's subgroup
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qUpdateTeacherInfo($idUser, $name,$surname,$email,$group,$subgroup,$role){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{

            $query = "UPDATE Users
                      SET
                          name = '$name',
                          surname = '$surname',
                          email = '$email',
                          `group` = '$group',
                          `subgroup` = '$subgroup',
                          role = '$role'
                      WHERE
                          idUser = '$idUser'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qDeleteUser
     * @param   $idStudent       String        Requested Student's ID
     * @param   $name            String        Student's name
     * @param   $surname         String        Student's surname
     * @param   $email           String        Student's email
     * @param   $group           String        Student's group
     * @param   $subgroup        String        Student's subgroup
     * @return  Boolean
     * @descr   Returns true if info was saved successfully, false otherwise
     */
    public function qDeleteUser($idUser){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "DELETE FROM `Users` 
                      WHERE `idUser`='$idUser'";
            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

















    /**
     * @name    qListOnlyGroup
     * @return  Boolean
     * @descr   Returns List Group
     */
    public function qListOnlyGroup(){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "SELECT *
                          FROM
                              GroupNTC ORDER BY NameGroup";

            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qNewGroup
     * @return  Boolean
     * @descr   Insert a new group
     */
    public function qNewGroup($groupName){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "INSERT INTO GroupNTC (NameGroup)
                          VALUES ('$groupName')";
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }
        return $ack;
        
    }

    /**
     * @name    qNewSubgroup
     * @return  Boolean
     * @descr   Insert a new Subgroup
     */
    public function qNewSubgroup($group,$subgroupName){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "INSERT INTO SubGroup (NameSubGroup, fkGroup)
                          VALUES ('$subgroupName','$group')";
            $this->execQuery($query);
        }catch (Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__.' : '.$this->getError());
        }
        return $ack;

    }

    /**
     * @name    qUpdateGroupInfo
     * @return  Boolean
     * @descr   Update a group
     */
    public function qUpdateGroupInfo($idGroup,$groupName){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{

            $query = "UPDATE GroupNTC
                      SET
                          NameGroup = '$groupName'
                      WHERE
                          idGroup = '$idGroup'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qUpdateSubgroupInfo
     * @return  Boolean
     * @descr   Update a subgroup
     */
    public function qUpdateSubgroupInfo($idSubgroup,$subgroupName,$fkGroup){
        global $log;
        $ack = true;
        $this->result = null;
        $this->mysqli = $this->connect();

        try{

            $query = "UPDATE SubGroup
                      SET
                          NameSubGroup = '$subgroupName',
                          fkGroup = $fkGroup
                      WHERE
                          idSubGroup = '$idSubgroup'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

        return $ack;
    }

    /**
     * @name    qcalcScore
     * @return  Boolean
     * @descr   Returns the score type of a test
     */
    public function qcalcScore($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "select distinct scoreType
                      from Tests JOIN (Exams JOIN TestSettings on Exams.fkTestSetting=TestSettings.idTestSetting)
                      on Tests.fkExam=Exams.idExam
                      where Tests.idTest='$idTest'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }

    }

    /**
     * @name    qGetCertificate
     * @return  Boolean
     * @descr   Returns certificate value of the test
     */
    public function qGetCertificate($idExam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "select TestSettings.certificate
                      from TestSettings JOIN Exams 
                      on TestSettings.idTestSetting=Exams.fkTestSetting
                      where Exams.idExam='$idExam'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
    }

    /**
     * @name    qGetSubjectExam
     * @return  Boolean
     * @descr   Returns the subject of the exam
     */
    public function qGetSubjectExam($idExam){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "select Subjects.name
                      from Subjects JOIN Exams 
                      on Subjects.idSubject=Exams.fkSubject
                      where Exams.idExam='$idExam'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
    }

    /**
     * @name    qGetUserTest
     * @return  Boolean
     * @descr   Returns info about the users who did the test
     */
    public function qGetUserTest($idTest){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{

            $query = "select Users.surname, Users.name, Users.email, Users.group, SubGroup.NameSubGroup, GroupNTC.NameGroup
                      from Tests JOIN ((Users JOIN SubGroup on Users.subgroup = SubGroup.idSubGroup)
                      JOIN GroupNTC on Users.group = GroupNTC.idGroup)
                      on Users.idUser=Tests.fkUser
                      where Tests.idTest='$idTest'";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
    }

    /**
     * @name    getMandatQuestionsInfo
     * @return  Boolean
     * @descr   Returns informations about mandatory questions selected
     */
    public function getMandatQuestionsInfo($mandatQuestions){
        global $log;
        $this->result = null;
        $this->mysqli = $this->connect();
        try{
            $query = "select idQuestion, difficulty, fkTopic
                      from Questions
                      where idQuestion In('";
            for($i = 0 ; $i< count($mandatQuestions)-1; $i++){
                $query .= $mandatQuestions[$i]."','";
            }
            $query .= $mandatQuestions[count($mandatQuestions)-1]."')";

            $this->execQuery($query);
        }catch(Exception $ex){
            $ack = false;
            $log->append(__FUNCTION__." : ".$this->getError());
        }
    }



/*******************************************************************
*                              mysqli                              *
*******************************************************************/

    /**
     * @name    connect
     * @return  mysqli|null    $mysqli   MySqli   Database connection
     * @descr   Define a database connection
     */
    public function connect() {
        global $log;
        // MySql connection using mysqli object
        $mysqli = null;
        //if (!$this->active) {
        $mysqli = new mysqli($this->dbHost,
            $this->dbUsername,
            $this->dbPassword,
            $this->dbName);
        $mysqli->set_charset("utf8");
        if (mysqli_connect_errno()) {
            $log->append('Connection to MySQL denied');
            die(mysqli_connect_error());
        } else {
            //$log->append('Connection to MySQL succeeded');
            $this->active = true;
        }
        //}
        return $mysqli;
    }

    /**
     * @name    prepareData
     * @param   $data       Array       All data string to prepare
     * @return  Array       $data       String ready
     * @descr   Trim and escape all data to prepare an update query
     */
    private function prepareData($data){
        $index = 0;
        while($index < count($data)){
            $data[$index] = str_replace('"', "'", $data[$index]);
            $data[$index] = trim($this->mysqli->real_escape_string($data[$index]));
            $data[$index] = str_replace("\\\\", "\\", $data[$index]);
            $index++;
        }
        return $data;
    }

    /**
     * @name    execQuery
     * @param   $query      String        Query statement
     * @throws  Exception
     * @descr   Execute a simple query
     */
    public function execQuery($query){
        global $log;
// ******************************************************************* //
       //$log->append($query); //DAMIANO QUI QUERY LOG 
// ******************************************************************* //
        if(!($this->result = $this->mysqli->query($query)))
            throw new Exception("Error");
    }

    /**
     * @name    execTransaction
     * @param   $queries      Array        Array of queries
     * @throws  Exception
     * @descr   Execute a simple query
     */
    private function execTransaction($queries){
        global $log;

        $this->mysqli->autocommit(FALSE);           // Set autocommit to OFF to make a secure transaction
        try{
            while(count($queries) > 0){
                $query = array_shift($queries);
              //  $log->append($query);
                $this->execQuery($query);           // Execute queries one by one as long as there isn't error
            }
            $this->mysqli->commit();
        }catch(Exception $ex){
            $ack = false;
            $this->error = $this->getError();
            $this->mysqli->rollback();
            throw new Exception("Error");
        }
        $this->mysqli->autocommit(TRUE);            // Reset autocommit to ON
    }

    /**
     * @name    nextRowAssoc
     * @return  $row     null|Array    Row result
     * @descr   Fetch the next row in result in associative array
     */
    public function nextRowAssoc(){
        global $log;
        $row = null;
        if(($row = $this->result->fetch_assoc()) == null){
//            $this->result->close();
            $log->append($row);
            $this->close();
        }
        return $row;
    }

    /**
     * @name    getResultAssoc
     * @param   $column    String         Column to use as array's index
     * @return  array
     * @descr   Fetch entire result set into associative array
     */
    public function getResultAssoc($column=null){
        global $log;
        $result = array();
        $row = null;
        $index = 0;
        if($column==null)
            while(($row = $this->nextRowAssoc())){
                $result[$index] = $row;
                $index++;
            }
        else{
            while(($row = $this->nextRowAssoc())){
                $result[$row[$column]] = $row;
            }
        }
        return $result;
    }

    /*
     * @name    getAllAssoc
     * @return  array
     * @descr   Fetch entire result set into associative array
     */
    public function getAllAssoc(){
        $result = array();
        $index = 0;
        if(($result = $this->result->fetch_all()) == null){
            $this->result->close();
            $this->close();
        }
        return $result;
    }

    /**
     * @name    nextRowEnum
     * @return  $row     null|Array    Row result
     * @descr   Fetch the next row in result in enumerated array
     */
    public function nextRowEnum(){
        $row = null;
        if(($row = $this->result->fetch_row()) == null){
            $this->result->close();
            $this->close();
        }
        return $row;
    }

    /**
     * @name    numResultRows
     * @return  $num        Integer     Number of row
     * @descr   Fetch the row's number in result set
     */
    public function numResultRows(){
        $num = $this->result->num_rows;
        return $num;
    }

    /**
     * @name    numAffectedRows
     * @return  $num        Integer     Number of row
     * @descr   Fetch the affected row's number in previuos MySQL query
     */
    public function numAffectedRows(){
        $num = $this->mysqli->affected_rows;
        return $num;
    }

    /**
     * @name    close
     * @descr   Close the mysqli connection
     */
    public function close(){
        if(isset($this->mysqli)) $this->mysqli->close();
    }

    /**
     * @name    getError
     * @return  null|String     String of last mysqli error
     * @descr   Return last mysqli error if exists
     */
    public function getError(){
        $error = '';
        if(isset($this->mysqli)){
            $error = $this->mysqli->error;
            if($error == ''){
                $error = $this->error;
            }
        }
        return $error;
    }

}


