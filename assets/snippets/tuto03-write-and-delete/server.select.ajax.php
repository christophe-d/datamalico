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

// #############################################################
// Trace with a handy display (This uses the mil_ help library):
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
	$client_ip = $_SERVER["REMOTE_ADDR"];
	$sql = "
		SELECT *
		FROM starwars_data_character
		WHERE
		fullname like '%Skywalker%'
		OR fullname like '%R2D2%'
		OR fullname like '%Chewbakka%'
		OR owner_ip = '$client_ip'
		ORDER BY starwars_data_character.char_id
		";
	$dco = new datamalico_server_dbquery ();
	$dco->select( array (
		'sql' => $sql
		, 'frontend_access' => array (
			'fullname' => array (
				'field_label' => "What great characters are they!"
				, 'accesses' => array (
					'rights' => "write"
					, 'behavior' => "onready"
				)
				, 'form_field_type' => "text"
			)
		)
		, 'pagination' => array ( // By default the pagination params are automatic, so you can remove this param, but you can also set it this way: (See the pagination.class.php for more info)
			'page' => 1
			, 'perpage' => 999
		)
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	));
	return $dco->output;
}

?>
