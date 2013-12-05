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
		'connection_type_access' => "MANAGER_GRANTED" // MANAGER_GRANTED WEBSITE_CONNECTED_GRANTED EVERYBODY_GRANTED
		, 'authorized_roles' => array (
			"Administrator" 						// MANAGER_GRANTED		-->	"Editor", "Publisher", "Administrator", "mil_commercial"
			//"Customer", "Volunteer", "Professional", "INTERNAL_STAFF" 	// WEBSITE_CONNECTED_GRANTED 	--> 	"Customer", "Volunteer", "Professional", "INTERNAL_STAFF"
			// 								// EVERYBODY_GRANTED 		--> 	nothing
		)
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
	$lang = $GLOBALS['config_ini']['lang'];
	$service_name_valuelist = get_valuelist ("SELECT service_type_id, $lang FROM mil_c_service_type WHERE enabled = 1 ORDER BY sort_index");
	$frontend_access = array (
		'profession_id' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
			, 'form_field_type' => "text"
		)
		, 'profession_name' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'english' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'french' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'spanish' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'german' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, $lang => array (
			'field_label' => "Métier"
			, 'accesses' => array (
				'rights' => "read"
			)
		)
		, 'service_type_id' => array (
			'accesses' => array (
				'rights' => "read"
			)
			, 'valuelist' => $service_name_valuelist
		)
		, 'enabled' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'sort_index' => array (
			'accesses' => array (
				'rights' => "hidden"
			)
		)
		, 'reg_id' => array (
			'field_label' => "reg_id"
			, 'accesses' => array (
				'rights' => "write"
				, 'behavior' => "onready"
			)
			, 'form_field_type' => "checkbox_multiselist"
		)
	);
	$action = array (
		'save_mode' => "global_save"
	);
	$config = array (
		'entity_table' => "mil_d_registered"
		//, 'entity_id' => $entity_id
		, 'temp_insert_id' => $this_mil_page->page_params['temp_insert_id']
		, 'list_table' => "mil_c_profession"
		, 'frontend_access' => $frontend_access
		, 'action' => $action
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	);
	//$results = dco_select_multiselist ($config);

	$dco = new datamalico_server_dbquery ();
	$dco->select_multiselist ($config);
	return $dco->output;
}

?>
