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
//echo trace2web ($_SESSION, "_SESSION");
//echo trace2web ($_POST, "_POST");
//echo trace2web ($_GET, "_GET");
//echo trace2web ($this_mil_page, "this_mil_page");

//trace2file ("", "", __FILE__, true);

// #############################################################
// Main, do the job on the server and returns a JSON output (if mil_page::ajax===true):

$client_ip = $_SERVER["REMOTE_ADDR"];
$sql = "
	SELECT *
	FROM starwars_data_character
	WHERE
	owner_ip = '$client_ip'
	ORDER BY change_date DESC
	";

$ajaxReturn =  array (
	'character' => get_data_from_db ($this_mil_page, $sql)
	, 'attribute' => get_multiselist_attribute_from_db ($this_mil_page, $sql)
);

$this_mil_page->output = $ajaxReturn;

//trace2file ($this_mil_page->output, "this_mil_page->output", __FILE__);
//echo trace2web($ajaxReturn, "ajaxReturn");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_data_from_db ($this_mil_page, $sql)
{	
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

	foreach ($dco->output['results']['records'] as $key => $value)
	{
		if ($key > 1) unset ($dco->output['results']['records'][$key]);
	}
	return $dco->output;
}

function get_multiselist_attribute_from_db ($this_mil_page, $sql)
{
	$entity_id = 0;

	// Get the character_id of the user with the client IP address, using mil_mysqli:
	// ... and use this occasion to simplify your work when querying a MySQL database and use mil_mysqli
	$myquery = $GLOBALS['dbcon']->qexec( array (
		'sql' => $sql
		, 'expected_affected_rows' => "0:inf"
		, 'get_field_structure' => false
		, 'script_place' => __FILE__.":".__LINE__
	));

	// if there is one result, so if there is one character with the client IP address:
	if ($myquery['metadata']['affected_rows'] >= 1)
	{
		$entity_id = $myquery['results']['records'][1]['char_id'];		
	}


	//trace2file ($myquery, "myquery", __FILE__);

	// Get the associated attribute multiselist
	$dco = new datamalico_server_dbquery ();
	$dco->select_multiselist ( array (
		'entity_table' => "starwars_data_character"
		, 'entity_id' => $entity_id
		//, 'temp_insert_id' => $this_mil_page->page_params['temp_insert_id']
		, 'list_table' => "starwars_config_attribute"
		, 'frontend_access' => array (
			'char_id' => array (
				'field_label' => "char_id"
				, 'accesses' => array (
					'rights' => "write"
					, 'behavior' => "onready"
				)
				, 'form_field_type' => "checkbox_multiselist"
			)
			, 'attr_id' => array (
				'accesses' => array ('rights' => "hidden")
			)
			, 'char2attr_id' => array (
				'accesses' => array ('rights' => "hidden")
			)
			, 'enabled' => array (
				'accesses' => array ('rights' => "hidden")
			)
			, 'sort_index' => array (
				'accesses' => array ('rights' => "hidden")
			)
		) 
		, 'action' => array (
			'save_mode' => "global_save"
		)
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	));
	return $dco->output;
}

?>
