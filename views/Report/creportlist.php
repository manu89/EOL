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

<div id="navbar">
    <?php printMenu(); ?>
</div>

<div id="main">

        <?php
        $db=new sqlDB();
        openBox(ttReportCoaching, 'normal', 'report');

        ?>
    <h2>Select one Test to see detailed information</h2>
    <table class="crlist" id="crtests">
        <thead>
        <tr>
            <th class="bold title"><?=ttReportPartecipant?></th>
            <th class="bold title"><?=ttReportAssesmentName?></th>
            <th class="bold title"><?=ttScore?></th>
            <th class="bold title"><?=ttGroup?></th>
            <th class="bold title"><?=ttReportDateTaken?></th>
            <th class="bold title"><?=ttStatus?></th>
        </tr>
        </thead>
        <!-- All tests will be print here-->
    </table>

        <?php
        closeBox();
        ?>


    <div class="clearer"></div>
</div>
