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

$dco_ajax = new datamalico_server_ajax ( array (
	'page_params' => array  ($_GET, $_POST)
));
$dco_ajax->set_horizontal_access ( array (
	'custom_horizontal_access_function' => "my_set_horizontal_access"
	, 'custom_horizontal_access_args' => array (
		'this_mil_page' => $this_mil_page
	)
));		//echo trace2web($dco_ajax->input['delupsert_list'], "set_horizontal_access");
$dco_ajax->input_data_validator ();	//echo trace2web($dco_ajax->input['delupsert_list'], "input_data_validator");
$dco_ajax->input_data_validator_add_custom (get_custom_data_validation($dco_ajax, $this_mil_page));
//if ($dco_ajax->are_there_invalid_data () === false) $isok=true;	// ok

$dco_ajax->delupsert();
$this_mil_page->output = $dco_ajax->output;

trace2file ($this_mil_page->output, "this_mil_page->output");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function my_set_horizontal_access ($one_record_selector, $custom_horizontal_access_args)
{
	// See the FAKE_my_set_horizontal_access_FAKE() in datamalico_server_ajax.lib.php
}

function get_custom_data_validation (&$dco_ajax, $this_mil_page)
{
	$array_of_errors = array ();

	//echo trace2web($this_mil_page, "this_mil_page");
	//echo trace2web($dco_ajax->input['delupsert_list'], "dco_ajax->input['delupsert_list']"); //['update'][{html_container}]['data_itself']['fields'][{fieldname}]

	//firstname
	if (!exists_and_not_empty($dco_ajax->input['delupsert_list']['update']['firstname']['data_itself']['fields']['firstname']))
	{
		$array_of_errors[] = array (
			'html_container' => "firstname"
			, 'metadata' => array (
				'valid' => false
				, 'returnMessage' => $GLOBALS['mil_lang_common']['firstname_must_be_filled']
			)
		);
	}

	//zipcode check_zipcode_according_to_country
	$array_of_errors = array_merge(
		$array_of_errors
		, check_zipcode_according_to_country ($dco_ajax, $this_mil_page)
	);


	//echo trace2web($array_of_errors, "array_of_errors");
	return $array_of_errors;
}

?>
