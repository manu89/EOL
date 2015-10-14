<?php
/**
 * File: profile.php
 * User: Masterplan
 * Date: 5/30/13
 * Time: 4:13 PM
 * Desc: Shows profile page of user's account
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