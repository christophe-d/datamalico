<?php


// #############################################################
// Config (access, ajax...)

$GLOBALS['ajax'] = true;
$this_page_access = array(
	"connection_type_access" => "EVERYBODY_GRANTED"		// "MANAGER_GRANTED", "WEBSITE_CONNECTED_GRANTED", "EVERYBODY_GRANTED"
);


// ###############################################################################################################################
// ###############################################################################################################################
// Init : Don't touch it !													##

//$this_file = __FILE__; include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/general_01.php";				##

include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/ajax/ajax_01.php";			// ajax, buffering, and ajaxReturn vars
include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_.conf.php";			// needed espacially for ajax file
//$GLOBALS['mil_lang'] = mil_get_lang_file ($this_file);						// get lang files
$access_forbidden = mil_check_security_access ($this_page_access);					// check if the current user can access this file
//nclude_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/datamalico/data_validator/data_validator.conf.php";	// needed to validate data to feed the db
//dco_get_pagination_params ();
// ###############################################################################################################################
// ###############################################################################################################################


// #############################################################
// Main

$_GET = mixed_stripslashes ($_GET);
$_POST = mixed_stripslashes ($_POST);

new mil_Exception ("JS mil_Exception: " . $_POST["adminMessage"], $_POST["errorId"], $_POST["errorLevel"], $_POST["fullURL"]);

if (isset_notempty_notnull ($_POST["userMessage"]))
{
	$ajaxReturn['metadata']['returnCode'] = "DISPLAY_USER_MESSAGE";
	$ajaxReturn['metadata']['returnMessage'] = $_POST["userMessage"];	
}
else
{
	$ajaxReturn['metadata']['returnCode'] = "NO_MSG_TO_USER";
	$ajaxReturn['metadata']['returnMessage'] = "";
}


//echo debugDisplayTable($GLOBALS['security']['current_user_keys'], "current_user_keys");
//echo debugDisplayTable($ajaxReturn, "ajaxReturn");
//echo debugDisplayTable($_SESSION, "_SESSION");
//echo debugDisplayTable($_POST, "_POST");
//echo debugDisplayTable($_GET, "_GET");

// ###############################################################################################################################
// ###############################################################################################################################
// Init : Don't touch it !													##
#include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/general_02.php";							##
include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/ajax/ajax_02.php";	// ajax, buffering
// ###############################################################################################################################
// ###############################################################################################################################




// #############################################################
// #############################################################
// #############################################################
// Functions


function get_mil_d_demand_histo ()
{
}



?>


