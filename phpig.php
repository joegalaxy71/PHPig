<?php

// log error to a specific file
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");



//error_log( "phpig///////////////////");
$pp_server = $_SERVER['SERVER_NAME'];
$pp_docroot = $_SERVER['DOCUMENT_ROOT'];

//print_r($_SERVER);
//error_log(ini_get("open_basedir"));

// restrict working directory PER SITE
error_log("DOCROOT: " . $pp_docroot);
ini_set("open_basedir", $pp_docroot);

/////////////////////////////////////////////////////////////////////////////
// is enabled?
/////////////////////////////////////////////////////////////////////////////

if (file_exists($pp_docroot."/.phpig")) {

error_log("phpig: REQUEST RECEIVED: SERVER: " . $pp_server . " FILE: " . $_SERVER['PHP_SELF'] . " IP: " . $_SERVER['REMOTE_ADDR']);


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

    runkit_function_rename('include','include_ori');
    runkit_function_rename('include_mod','include');

    runkit_function_rename('mysqli_query','mysqli_query_ori');
    runkit_function_rename('mysqli_query_mod','mysqli_query');

    runkit_function_rename('ini_set','ini_set_ori');
    runkit_function_rename('ini_set_mod','ini_set');


    runkit_function_rename('fopen','fopen_ori');
    runkit_function_rename('fopen_mod','fopen');
}

/////////////////////////////////////////////////////////////////////////////
// modified functions (ends in _mod)
/////////////////////////////////////////////////////////////////////////////

function mysqli_query_mod($con, $query) { 
    //die;

    return mysqli_query_ori($con, $query);
    //return false;
}

function ini_set_mod() {
    //die;
    error_log("phpig: SERVER: " . $GLOBALS["$pp_server"] . " POLICY VIOLATION: trying to change error reporting mode");

    return false;
}

function include_mod($file) {
    //die;
    error_log("phpig: SERVER: " . $GLOBALS["$pp_server"] . " NOTIFICATION: included file: " . $file);

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

    // check if violation occurs
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $GLOBALS["$pp_server"] . ": POLICY VIOLATION: trying to fopen in write mode .php file: " . $file);
        die;
    } else {
        return fopen_ori($file, $mod);
    }
}

////////////////////////////////////////////////////////////////////////////
// pure functions
///////////////////////////////////////////////////////////////////////////

function endsWith($haystack, $needle, $case=true) {
    if($case)
    {
        return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
        return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
	}
?>