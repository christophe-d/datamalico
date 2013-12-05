<?php

// #############################################################
// Page Config (mil_page)
$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
//include_once $doc_root."/1001_addon/library/config/your_app_website.conf.php";		// if ever you want to add more confif for your own purpose
include_once $doc_root."/1001_addon/library/mil_/mil_.conf.php";				// mil_ library needed
include_once $doc_root."/1001_addon/library/datamalico/datamalico_server_dbquery.lib.php";	// of course, datamalico library is needed

$this_mil_page = new mil_page (	array (
	'original_file' => __FILE__
	, 'ajax' => true
	, 'page_access' => array (
		'connection_type_access' => "EVERYBODY_GRANTED" // MANAGER_GRANTED WEBSITE_CONNECTED_GRANTED EVERYBODY_GRANTED
		//, 'authorized_roles' => array ()
	)
));

//echo trace2web ($_SESSION, "_SESSION");
//echo trace2web ($_POST, "_POST");
//echo trace2web ($_GET, "_GET");
//echo trace2web ($this_mil_page, "this_mil_page");

// #############################################################
// Main, do the job on the server and returns a JSON output (if mil_page::ajax===true):

$ajaxReturn = get_data_from_db ($this_mil_page);
$this_mil_page->output = $ajaxReturn;

//echo trace2web($ajaxReturn, "ajaxReturn");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_data_from_db ($this_mil_page)
{
	$dco = new datamalico_server_dbquery ();
	$dco->select( array (
		'sql' => "SELECT * FROM  starwars_data_character ORDER BY starwars_data_character.fullname"
		, 'pagination' => $this_mil_page->page_params['pagination'] 
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	));
	return $dco->output;
}

?>
