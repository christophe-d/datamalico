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
	, 'save_history' => false
));

// #############################################################
// Trace with a handy display (This uses the mil_ help library):
//echo trace2web($_SESSION, "_SESSION");
//echo trace2web($_POST, "_POST");
//echo trace2web($_GET, "_GET");
//echo trace2web($this_mil_page, "this_mil_page");
//echo $this_mil_page->debugDisplay_current_user_keys();
//
// $current_reg_id = $this_mil_page->current_user_keys['website']['reg_id'];
// $offer_id = $this_mil_page->page_params['offer_id'];
// $offer_id = $this_mil_page->page_params['master_page_params']['offer_id']; 	// if this page is a server page, getting info from the calling client page: http://mydomain.fr/mypage.php?offer_id=118

// #############################################################
// Main, do the job on the server and returns a JSON output (if mil_page::ajax===true):

$ajaxReturn = get_data_from_db ($this_mil_page);
$this_mil_page->output = $ajaxReturn;

echo trace2web($ajaxReturn, "ajaxReturn");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_data_from_db ($this_mil_page)
{
	$dco_ajax = new datamalico_server_ajax ( array (
		'page_params' => array  ($_GET, $_POST)
	));

	$sql = $dco_ajax->research_build_select_query ( array (
		'SELECT_members' => array (
			"starwars_data_character.fullname"
			, "starwars_data_character.description"
			, "starwars_config_type.type_name"
		)
		, 'FROM_tables' => array (
			"starwars_data_character"
			, "starwars_config_attribute"
			, "starwars_config_type"
		)
		, 'WHERE_authorized' => array ()
		, 'GROUP_BY' => "starwars_data_character.char_id"
		, 'ORDER_BY' => "starwars_data_character.fullname"
	));
	//trace2file("", "", __FILE__, true);
	//trace2file($sql, "sql", __FILE__);


	$dco = new datamalico_server_dbquery ();
	$dco->select(array (
		'sql' => $sql
		, 'frontend_access' => array ()
			, 'pagination' => $this_mil_page->page_params['pagination']
			//, 'runas' => "CODER"          // I use here runas, because of the SUM() fn. Without runas CODER, the backend_access wouldn't give access.
			, 'calling_FILE' => __FILE__
			, 'calling_LINE' => __LINE__
		));


	return $dco->output;
}

?>
