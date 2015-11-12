<?php
/**
 * File: index.php
 * User: Masterplan
 * Date: 3/21/13
 * Time: 8:44 PM
 * Desc: Admin's Homepage
 */

global $config, $user;

?>

        <?php
        openBox(ttReport, 'normal-70.1%', 'partecipants');

        ?>

    <form name="aoreport" method="post">
    <h3><?= ttReportAOSelectAssesment ?></h3>
    <p><?=ttReportAODescription?></p>

    <div class="col-left">
        <h4><?=ttReportSearchByLetter?></h4>
        <select id="letter" size="1" onchange="printAssesments(this.value)">
            <option><?=ttReportSelectLetter?></option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>

        </select>
        <br/>
        <br/>
        <!--<select id="assesment" size="1">

        </select>-->
    </div>

    <div class="col-left">
        <h4><?=ttReportTyping?><br></h4>
        <input type="text" name="word" oninput="printAssesments(this.value)">
    </div>
    <div class="col-center">
        <h4><?=ttReportSearched?><br></h4>
        <select size="5" id="searched" class="select">

        </select>
    </div>
    <div class="col-left">
        <a class="normal button right rSpace" id="add" onclick="addAssessment(searched.value)"><?=ttAdd?></a>
    </div>
    <div class="col-center">
        <h4><?=ttReportSelected?><br></h4>
        <select size="5" id="selected" class="select">

        </select>
    </div>
    <div class="col-center">
        <a class="normal button rSpace" id="remove" onclick="removeAssessment(selected.value)"><?=ttRemove?></a>
        <br>

        <a class="normal button rSpace" id="removeall" onclick="clearAssessments()"><?=ttRemoveAll?></a>
    </div>
    </form>
    <br/>
    <hr class="divider"/>
    <div id="tabsbutton">
        <a class="normal button rSpace" id="next" onclick="closePartecipant()"><?=ttNext?></a>
    </div>
        <div class="clearer"></div>
        <?php
        closeBox();
        ?>