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
    <div id="reportHomepage">
        <?php
        openBox(ttReport, 'normal', 'report');
        echo ttAdminWelcome;

        ?>

        <table id="reportTable">
            <tr><td></td><td><?= ttTeachers ?></td></tr>
            <tr><td></td><td><?= ttETeachers ?></td></tr>

        </table>

        <?php
        closeBox();
        ?>
        <div class="clearer"></div>
    </div>
</div>