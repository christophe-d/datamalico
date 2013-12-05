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

// #############################################################
// Main, do the job on the server and returns a JSON output (if mil_page::ajax===true):

$ajaxReturn =  array (
	'character' => get_data_from_db ($this_mil_page)
	, 'attribute' => get_multiselist_attribute_from_db ($this_mil_page)
);

$this_mil_page->output = $ajaxReturn;

echo trace2web($this_mil_page->output, "this_mil_page->output");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property
//trace2file ("", "", __FILE__, true);
//trace2file ($this_mil_page->output, "this_mil_page->output", __FILE__);

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_data_from_db ($this_mil_page)
{
	$dco = new datamalico_server_dbquery ();
	$dco->select_empty( array (
		'sql' => "SELECT char_id, fullname, owner_ip FROM starwars_data_character" // Necessary to add the primary key char_id
		, 'frontend_access' => array (
			'fullname' => array (
				'field_label' => "Fill in this field with your name and become a Star Wars character!"
				, 'accesses' => array (
					'rights' => "write"
					, 'behavior' => "onready"
				)
				, 'form_field_type' => "text"
			)
			, 'owner_ip' => array (
				'field_label' => "Your IP"
				, 'accesses' => array (
					'rights' => "write"
					, 'behavior' => "onready"
				)
				, 'form_field_type' => "text"
			)
		)
		, 'temp_insert_id' => $this_mil_page->page_params['temp_insert_id']	// related_insertions : temp_insert_id, slave (ou FK) join table.
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	));
	return $dco->output;
}

function get_multiselist_attribute_from_db ($this_mil_page)
{
	$dco = new datamalico_server_dbquery ();
	$dco->select_multiselist ( array (
		'entity_table' => "starwars_data_character"
		, 'temp_insert_id' => $this_mil_page->page_params['temp_insert_id']
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
