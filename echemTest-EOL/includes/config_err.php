<?php
/**
 * File: config.php
 * User: Masterplan
 * Date: 3/15/13
 * Time: 11:36 AM
 * Desc: Configuration file for EOL2 webapp
 */

/*----------------------------------*
 *  All system configurations       *
 *----------------------------------*/
session_start();
// System version
$config['systemVersion'] = '1.0';
// System title
$config['systemTitle'] = 'LibreEOL - Exams On Line';
// System home website (used for emails)
$config['systemHome'] = 'http://'. $_SERVER["SERVER_NAME"].'/';
// System comunication email
$config['systemEmail'] = 'no-reply@eol.org';
// Default system language (watch Languages table in db)
$config['systemLang'] = 'English';
// Default system time zone (watch php documentation from time zone available)
$config['systemTimeZone'] = 'Europe/Rome';

$config['systemLogo'] = '';
// Default controller for students, teachers and admins
$config['controller']['a'] = 'Admin';
$config['controller']['e'] = 'Teacher';
$config['controller']['t'] = 'Teacher';
$config['controller']['s'] = 'Student';
$config['controller']['at'] = 'Teacher';
$config['controller']['er'] = 'Teacher';
// System directories
$config['systemControllersDir'] = '../controllers/';
$config['systemQuestionTypesClassDir'] = '../questionTypes/';
$config['systemViewsDir'] = '../views/';
$config['systemLibsDir'] = 'libs/';
$config['systemLangsDir'] = 'langs/';
$config['systemQuestionTypesLibDir'] = $config['systemLibsDir'].'questionTypes/';
$config['systemLangsXml'] = '../resources/languages/';
$config['systemExtraDir'] = 'extra/';
$config['systemFpdfDir'] = 'fpdf/';
$config['systemPhpGraphLibDir'] = 'phpgraphlib-master/';
$config['systemFileManagerDir'] = 'filemanager/';

//ImportQM directory
$config['importQMDir']='../../QUESTIONS';
$config['topicResQM']='../../';

// System log files directory
$config['logDir'] = '../logs/';
// System log files
$config['systemLog'] = $config['logDir'].'system.log';
// Main upload directory
$config['systemUploadDir'] = '/fileman/Uploads/Images';
// Datatable text column length
$config['datatablesTextLength'] = 100;
// Ellipsis
$config['ellipsis'] = ' [...]';

/*----------------------------------*
 *  All database configurations     *
 *----------------------------------*/

// Database type (mysql | ...)
$config['dbType'] = 'MySQL';
// Database web address
$config['dbHost'] = 'localhost';
// Database port
$config['dbPort'] = '3306';
// Database name
if(isset($_SESSION['dbNameChanged'])){
    $config['dbName']=$_SESSION['dbNameChanged'];
}else{
	$config['dbName'] = 'echemtest';
}
// Database access username
$config['dbUsername'] = 'root';
// Database access password
$config['dbPassword'] = 'Wp7c57o';

/*----------------------------------*
 *  All themes configurations       *
 *----------------------------------*/

// Themes directory
$config['themesDir'] = 'themes/';
// Theme name (equals to theme folder)
$config['themeName'] = 'default';
if(isset($_SESSION['dbNameChanged'])){
    $config['themeName'] = 'default';
}else{
	$config['themeName'] = 'default';
}
// Theme directory
$config['themeDir'] = $config['themesDir'].$config['themeName'].'/';
// Theme's images directory
$config['themeImagesDir'] = $config['themeDir'].'images/';
// Theme's flags directory
$config['themeFlagsDir'] = $config['themeDir'].'flags/';
