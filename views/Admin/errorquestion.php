<?php
/**
 * File: errorquestion.php
 * User: GMarsi
 * Date: 14/10/2015
 * Time: 4:13 PM
 * Desc: Shows error segnalation
 */

global $user, $log;
?>

<div id="navbar">
    <?php printMenu(); ?>
</div>

<div id="main">
    <div class="clearer"></div>
    <?php openBox(ttErrorquestion, 'small', 'profile') ?>
    <form class="infoEdit" onsubmit="return false;">

        <label class="b2Space"><?= ttQuestion ?> : </label>
        <input class="writable" type="text" id="idquestion">
        <div class="clearer"></div>

        <label class="b2Space"><?= ttNote ?> : </label>
        <textarea  id="notes"> </textarea>
        <div class="clearer"></div>

<br>

        <div>
            <a class="normal button" id="saveProfile" onclick="errorEmail();"><?= ttInvia?></a>
        </div>
    </form>
    <?php closeBox() ?>
</div>