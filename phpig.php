<?php

// log error to a specific file
ini_set("error_reporting", 'E_ALL');
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");

register_shutdown_function('shutdown');

//includes --- spyc for yaml parsing
require_once "spyc.php";

// if .phpig file exists, read it, parse it, merge the array with the one of the config
if (file_exists($pp_docroot . "/.phpig")) {
    error_log("wow");
    $config = Spyc::YAMLLoad($pp_docroot . "/.phpig");
    error_log(print_r($config));
}

//error_log( "phpig///////////////////");
$pp_server = $_SERVER['SERVER_NAME'];
$pp_docroot = $_SERVER['DOCUMENT_ROOT'];

//print_r($_SERVER);
//error_log(ini_get("open_basedir"));

// restrict working directory PER SITE --- ALWAYS
//error_log("DOCROOT: " . $pp_docroot);
ini_set("open_basedir", $pp_docroot);


/////////////////////////////////////////////////////////////////////////////
// is enabled?
/////////////////////////////////////////////////////////////////////////////

if ( !file_exists($pp_docroot . "/.phpig-disable") && !isset($_COOKIE["phpig"]) ) {

    error_log("PHPIG: enabled | SERVER: " . $pp_server . " | FILE: " . $_SERVER['PHP_SELF'] . " | IP: " . $_SERVER['REMOTE_ADDR']);


    // let's remove some nasty functions
    runkit_function_remove("exec");
    runkit_function_remove("shell_exec");
    runkit_function_remove("passthru");
    runkit_function_remove("system");



//    ini_set("open_basedir", $pp_docroot);

//    $options = array(
//        'safe_mode'=>true,
//        'open_basedir'=>$pp_docroot,
//        'allow_url_fopen'=>'false',
//        'disable_functions'=>'exec,shell_exec,passthru,system');
//        //'disable_classes'=>'myAppClass');
//    $sandbox = new Runkit_Sandbox($options);
//    /* Non-protected ini settings may set normally */
//    $sandbox->ini_set('html_errors',false);

//    runkit_function_rename('include','include_ori');
//    runkit_function_rename('include_mod','include');

    runkit_function_rename('mysqli_query','mysqli_query_ori');
    runkit_function_rename('mysqli_query_mod','mysqli_query');

    runkit_function_rename('ini_set','ini_set_ori');
    runkit_function_rename('ini_set_mod','ini_set');

    runkit_function_rename('file_put_contents','file_put_contents_ori');
    runkit_function_rename('file_put_contents_mod','file_put_contents');

    runkit_function_rename('fopen','fopen_ori');
    runkit_function_rename('fopen_mod','fopen');

    runkit_function_rename('touch','touch_ori');
    runkit_function_rename('touch_mod','touch');

} else {
    if (file_exists($pp_docroot."/.phpig-disabled")) {
        $reason = ".phpig-disabled present on site root";
    }
    if (isset($_COOKIE["phpig"])) {
        $reason = "phpig cookie present";
    }

    error_log("PHPIG: disabled, " . $reason . " | SERVER: " . $pp_server . " | FILE: " . $_SERVER['PHP_SELF'] . " | IP: " . $_SERVER['REMOTE_ADDR']);
}
/////////////////////////////////////////////////////////////////////////////
// modified functions (ends in _mod)
/////////////////////////////////////////////////////////////////////////////

function mysqli_query_mod($con, $query) { 
    //die;

    return mysqli_query_ori($con, $query);
    //return false;
}

function ini_set_mod($a, $b) {
    //die;
    //error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " POLICY VIOLATION: trying to change error reporting mode");
    //error_log("ini_set(" . $a .", " . $b . ")");
    return ini_set_ori($a, $b);
}

function include_mod($file) {
    //die;
    error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " NOTIFICATION: included file: " . $file);

    return include_ori($file);
}

function fopen_mod($file, $mod) {
    //init
    $pp_violation = false;


    // violation checks
    if (endsWith($file, ".php") && ($mod != "r")) {
        $pp_violation = true;
    }

    if (strpos($file, "cache/Gantry")) {
        $pp_violation = false;
    }

    // error log&die or normal function call
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . ": POLICY VIOLATION: trying to fopen in write mode .php file: " . $file);
        die;
    } else {
        return fopen_ori($file, $mod);
    }
}

function file_put_contents_mod($file, $data, $flags, $content) {
    //init
    $pp_violation = false;


    // violation checks
    if (endsWith($file, ".php")) {
        $pp_violation = true;
    }

    // error log&die or normal function call
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . ": POLICY VIOLATION: trying to file_put_contents in write mode .php file: " . $file);
        die;
    } else {
        return file_put_contents_ori($file, $data, $flags, $content);
    }
}

function touch_mod($file, $time, $atime)
{
    //init
    $pp_violation = false;

    // violation checks
    if ((endsWith($file, ".php")) && ($time = "")) {
        $pp_violation = true;
    }

    if (strpos($file, "cache/Gantry")) {
        $pp_violation = false;
    }

    // error log&die or normal function call
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . ": POLICY VIOLATION: trying to touch with arbitrary time a .php file: " . $file);
        die;
    } else {
        return touch_ori($file, $time, $atime);
    }
}

////////////////////////////////////////////////////////////////////////////
// common functions
///////////////////////////////////////////////////////////////////////////

function shutdown() {
    //error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " NOTIFICATION: shutting down");
//    if ($_SERVER['SERVER_NAME'] == "muricciaglia.com") {
//        error_log("////////////////////////////////////////////");
//        $included_files = get_included_files();
//
//        foreach ($included_files as $filename) {
//            error_log("INCLUDED:" . $filename);
//        }
//        error_log("////////////////////////////////////////////");
//
//    }

}

////////////////////////////////////////////////////////////////////////////
// pure functions
///////////////////////////////////////////////////////////////////////////

function endsWith($haystack, $needle, $case=true) {
    if ($case) {
        return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
    }
}
?>