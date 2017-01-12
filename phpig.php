<?php

// log error to a specific file
ini_set("error_reporting", 'E_ALL');
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");

register_shutdown_function('shutdown');

//includes --- spyc for yaml parsing
require_once "spyc.php";
//error_log( "phpig///////////////////");
$pp_server = $_SERVER['SERVER_NAME'];
$pp_docroot = $_SERVER['DOCUMENT_ROOT'];

//error_log($pp_docroot . "/.phpig");

if (is_writable("/var/www/phpig/phpig-default.conf")) {
    error_log('WARNING: /var/www/phpig/phpig-default.conf is writable');
}

// loading main config
$config = Spyc::YAMLLoad("/var/www/phpig/phpig-default.conf");

//error_log("config: " . print_r($config, true));
//error_log($config['locked files']['extensions']);

// if .phpig file exists, read it, parse it, merge the array with the one of the config
if (file_exists($pp_docroot . "/phpig.conf")) {

    if (is_writable($pp_docroot . "/phpig.conf")) {
        error_log("WARNING: " . $pp_docroot . "/phpig.conf is writable");
    }
    
    $user_config = Spyc::YAMLLoad($pp_docroot . "/phpig.conf");
    //error_log("user config: " . print_r($user_config, true));

    $config = array_replace_recursive($config, $user_config);
}

// conditional logging
if ($pp_server == "www.centroascoltopsicologico.com") {
//if (true) {
    error_log("config: " . print_r($config, true));
    error_log("Enabled = " . $config["enabled"]);
}

// restrict working directory PER SITE --- ALWAYS
ini_set("open_basedir", $pp_docroot);


/////////////////////////////////////////////////////////////////////////////
// is enabled?
/////////////////////////////////////////////////////////////////////////////

if ( ($config["enabled"]) && !isset($_COOKIE["phpig"]) ) {

    error_log("PHPIG 0.3: enabled | SERVER: " . $pp_server . " | FILE: " . $_SERVER['PHP_SELF'] . " | IP: " . $_SERVER['REMOTE_ADDR']);

    // let's remove some nasty functions
    runkit_function_remove("exec");
    runkit_function_remove("shell_exec"); //also disable backtick operator `
    runkit_function_remove("passthru");
    runkit_function_remove("system");

    runkit_function_rename('include','include_ori');
    runkit_function_rename('include_mod','include');

    runkit_function_rename('mysqli_query','mysqli_query_ori');
    runkit_function_rename('mysqli_query_mod','mysqli_query');

    runkit_function_rename('ini_set','ini_set_ori');
    runkit_function_rename('ini_set_mod','ini_set');

    runkit_function_rename('file_put_contents','file_put_contents_ori');
    runkit_function_rename('file_put_contents_mod','file_put_contents');

    runkit_function_rename('unlink','unlink_ori');
    runkit_function_rename('unlink_mod','unlink');

    runkit_function_rename('fopen','fopen_ori');
    runkit_function_rename('fopen_mod','fopen');

    runkit_function_rename('touch','touch_ori');
    runkit_function_rename('touch_mod','touch');

} else {
    if ($config["enabled"] == false) {
        $reason = " by resulting active configuration";
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
//    error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " POLICY VIOLATION: trying to change error reporting mode");
//    error_log("ini_set(" . $a .", " . $b . ")");
    return ini_set_ori($a, $b);
//    return false;
}

function include_mod($file) {
    //die;
    error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " | NOTIFICATION: included file: " . $file);

    return include_ori($file);
}

function unlink_mod($file, $context) {


//    error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " | NOTIFICATION: unlinking: " . $file);

//    //init
//    $pp_violation = false;
//
//    // violation checks
//    if (endsWith($file, ".php")) {
//        $pp_violation = true;
//    }
//
//    // error log&die or normal function call
//    if ($pp_violation) {
//        // corrective action
//        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " | FILE: " . $_SERVER['PHP_SELF'] .  " | IP: " . $_SERVER['REMOTE_ADDR'] . ": POLICY VIOLATION: trying to unlink .php file: " . $file);
//        die;
//    } else {
        return unlink_ori($file, $context);
//    }
}

function fopen_mod($file, $mod) {
    //init

//    error_log("--- working on file:$file");
    $backtrace = debug_backtrace();
//    error_log("fopen called from:" .  $backtrace[1]['file']);

//    $out1 = `getattr`;
//    error_log("testing getattr, output is=$out1");

    if ($mod != "r") {
        if (strpos($file, "/var/www/movisol.org/wp-includes")) {
            error_log("file:$file, written by: $backtrace[1]['file']");
        }
    }


    global $config;

    $pp_violation = false;

    // violation checks

    // not a .php file
    if (endsWith($file, ".php") && ($mod != "r")) {
        $pp_violation = true;
    }

    // not a file with a forbidden extension
    $file_parts = pathinfo($file);
    $extension = $file_parts['extension'];
//    $pos = strpos($config["locked"]["files"]["extensions"], $extension);
//    error_log("pos:$pos");
//    if ( ($pos !== false) && ($mod != "r") ) {
//        $pp_violation = true;
//    }

    // allow this (waiting to implement sanitized dirs)
    if (strpos($file, "cache/Gantry")) {
        $pp_violation = false;
    }

    // error log&die or normal function call
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " | FILE: " . $_SERVER['PHP_SELF'] .  " | IP: " . $_SERVER['REMOTE_ADDR'] . ": POLICY VIOLATION: trying to fopen in write mode ." . $extension . " file: " . $file);
        die;
    } else {
        return fopen_ori($file, $mod);
    }
}

function file_put_contents_mod($file, $data, $flags, $content) {
    //init

    $backtrace = debug_backtrace();

    if (strpos($file, "/var/www/movisol.org/wp-includes/e5b84a541ba41780fc744d6cf7e1a869")) {
        error_log("file:$file, written by: $backtrace[1]['file']");
    }

    global $config;

    $pp_violation = false;

    // violation checks

    // not a .php file
    if (endsWith($file, ".php")) {
        $pp_violation = true;
    }

    // not a file with a forbidden extension in write mode
    $file_parts = pathinfo($file);
    $extension = $file_parts['extension'];
    $pos = strpos($config["locked"]["files"]["extensions"], $extension);
//    error_log("pos:$pos");
    if ( ($pos !== false) && ($mod != "r") ) {
        $pp_violation = true;
    }

    // error log&die or normal function call
    if ($pp_violation) {
        // corrective action
        error_log("phpig: SERVER: " . $_SERVER['SERVER_NAME'] . " | FILE: " . $_SERVER['PHP_SELF'] .  " | IP: " . $_SERVER['REMOTE_ADDR'] . ": POLICY VIOLATION: trying to file_put_contents " . $extension . " file: " . $file);
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