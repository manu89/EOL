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
    <div class="clearer"></div>
        <?php
        $db=new sqlDB();
        openBox(ttReportCoaching, 'center', 'report');

        ?>

        <?php
        closeBox();
        ?>



</div>
