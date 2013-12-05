<?php

// #############################################################
// Page Config (mil_page)
$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
//include_once $doc_root."/1001_addon/library/config/your_app_website.conf.php";		// if ever you want to add more confif for your own purpose
include_once $doc_root."/1001_addon/library/mil_/mil_.conf.php";				// mil_ library needed
include_once $doc_root."/1001_addon/library/datamalico/datamalico_server_dbquery.lib.php";	// of course, datamalico library is needed

$this_mil_page = new mil_page (	array (
	'original_file' => __FILE__
	, 'ajax' => false
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
// Main, render an HTML page:

//$this_mil_page->page_params = $this_mil_page->get_data_from_GET_POST ($_GET, $_POST);
$this_mil_page->render_html_page ( array (
	'template_id' => "main_template"
));
$this_mil_page->output = local_set_dynamic_values_into_template ($this_mil_page->output);
echo $this_mil_page->output;

return;

// #############################################################
// #############################################################
// #############################################################
// Functions

function local_set_dynamic_values_into_template ($tpl_with_lang)
{
	#######################
	// DATA : Prepare all params you need before rendering the page according to good params
	/*$mil_v_data = get_mil_v_data  (
		array(
			"demand_id" => $_GET['demand_id']
			, "reg_id" => $reg_id
			, "service_type_name" => "Architecture"
		)
	);*/


	// Prepare page for the edit mode.
	$a_placeholder = "A value set in this page";
	$js_file = dirname(__FILE__) . "/lang/js/";

	// PLACEHOLDERS of personal vars
	//foreach ($mil_v_data as $field_name => $value) $tpl_with_lang = str_replace("[+$field_name+]", $mil_v_data["$field_name"], $tpl_with_lang);
	$tpl_with_lang = str_replace("[+a_placeholder+]", $a_placeholder, $tpl_with_lang);

	return $tpl_with_lang;
}

?>
