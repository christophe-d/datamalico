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

$this_mil_page->output = array (
	'character' => get_character_form ($this_mil_page, $role_target_and_desc_valuelist)
	//, 'type' => get_type_from_db ($this_mil_page)		// no need of the this element, because, as a one_to_many relation (a simple selection list) it is embeded in the character research form.
	, 'attribute' => get_attribute_form ($this_mil_page)
);


echo trace2web($this_mil_page->output, "this_mil_page->output");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_character_form ($this_mil_page)
{
	$dco_ajax = new datamalico_server_ajax ( array (
		'page_params' => array  ($_GET, $_POST)
	));

	$dco_ajax->research_get_form_structure( array (
		// 'SELECT_fields_to_search_on' => array ( ... ) ,
		'FROM_tables' => array (
			"starwars_data_character"
		)	
		, 'frontend_access' => array (
			'fullname' => array (
				'accesses' => array ('rights' => "write")
				, 'valuelist' => $role_target_and_desc_valuelist
			)
			, 'change_date' => array (
				'accesses' => array ('rights' => "write")
			)
			, 'owner_ip' => array (
				'accesses' => array ('rights' => "write")
			)
			, 'description' => array (
				'accesses' => array ('rights' => "write")
			)
			, 'type_id' => array (
				'accesses' => array ('rights' => "write")
			)
		)
	));
	return $dco_ajax->output;

}

function get_attribute_form ($this_mil_page)
{
	$dco_ajax = new datamalico_server_ajax (array (
		'page_params' => array  ($_GET, $_POST)
	));
	$dco_ajax->research_multiselist_get_form_structure (array (
		'entity_table' => "starwars_data_character"
		, 'list_table' => "starwars_config_attribute"
		, 'frontend_access' => array (
			'char_id' => array (
				'accesses' => array (
					'rights' => "write"
					, 'behavior' => "onready"
				)
				, 'form_field_type' => "checkbox_multiselist" // Here, the jointable.entity_id must be prefered as "checkbox_multiselist" or "radio_singleselist"
				, 'research_operators' => array (
					'cond_group' => array (
						'name' => "professions" 
						, 'parent' => "default"
						, 'join_op' => "OR"
					)
				)
			)
			, 'attr_id' => array ( // Here, the jointable.list_id must be present in the result but you can hide it.
				'accesses' => array (
					'rights' => "hidden"
				)
			)
			, 'char2attr_id' => array (
				'accesses' => array (
					'rights' => "hidden"
				)
			)
			, 'sort_index' => array (
				'accesses' => array (
					'rights' => "hidden"
				)
			)
		)
		, 'calling_FILE' => __FILE__
		, 'calling_LINE' => __LINE__
	));

	return $dco_ajax->output;
}

?>
