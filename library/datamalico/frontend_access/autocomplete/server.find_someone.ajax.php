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
		, 'authorized_roles' => array (
			"Administrator" 						// MANAGER_GRANTED		-->	"Editor", "Publisher", "Administrator", "mil_commercial"
			//"Customer", "Volunteer", "Professional", "INTERNAL_STAFF" 	// WEBSITE_CONNECTED_GRANTED 	--> 	"Customer", "Volunteer", "Professional", "INTERNAL_STAFF"
			// 								// EVERYBODY_GRANTED 		--> 	nothing
		)
	)
	, 'include_sibling_lang' => false
	, 'save_history' => false
));

//echo trace2web ($_SESSION, "_SESSION");
//echo trace2web ($_POST, "_POST");
//echo trace2web ($_GET, "_GET");
//echo trace2web ($this_mil_page, "this_mil_page");
//echo trace2web ($GLOBALS['security']['current_user_keys'], "current_user_keys");
//
// $current_reg_id = $this_mil_page->current_user_keys['website']['reg_id'];
// $offer_id = $this_mil_page->page_params['offer_id'];
// $offer_id = $this_mil_page->page_params['master_page_params']['offer_id']; 	// if this page is a server page, getting info from the calling client page: http://mydomain.fr/mypage.php?offer_id=118

// #############################################################
// Main, render an HTML page:
//sleep(2);
$term = strtolower($this_mil_page->page_params['term']);
//$term = strtolower($_GET['term']);


// ##################################################
// Prepare SQL conditions:
// Search on several fields + make an intelligent condition
$fields_to_search_on = array (
	"mil_d_registered.firstname"
	, "mil_d_registered.lastname"
	, "mil_d_registered.companyname"
	, "mil_d_registered.companynum"
	, "mil_d_registered.reg_id"
	, "modx_web_users.username"
);
$a_conditions = array ();
foreach ($fields_to_search_on as $index => $full_field_name)
{
	// collect per field, an intelligent condition (that is to say as many as expressions are)
	if ($full_field_name === "mil_d_registered.reg_id")
	{
		$a_conditions = array_merge (
			get_one_intelligent_condition ( array (
				'full_field_name' => $full_field_name
				, 'op' => "="
				, 'expr' => $term
				, 'quote' => "'"	// needs it because otherwise, you can have "mil_d_registered.reg_id = chr" and it bugs because, chars are not quoted.
				, 'oper_opt' => array ('exact_word' => true)
			))
			, $a_conditions
		);
	}
	else
	{
		$a_conditions = array_merge (
			get_one_intelligent_condition ( array (
				'full_field_name' => $full_field_name
				, 'op' => "LIKE"
				, 'expr' => $term
				, 'quote' => "'"
			))
			, $a_conditions
		);
	}
}
echo trace2web ($a_conditions, "a_conditions");
$s_conditions = implode ("\nOR ", $a_conditions);

// ##################################################
// Define the SQL query:
$sql = "
	SELECT
	mil_c_country.lang as category 
	, mil_c_country.french as label
	, mil_c_country.country_id as db_store
	FROM  `mil_c_country`
	WHERE mil_c_country.french LIKE '%$term%'
	ORDER BY mil_c_country.lang
	LIMIT 10
	";
$sql = "
	SELECT
	modx_web_users.username as label
	, mil_d_registered.reg_id as db_store
	FROM mil_d_registered
	INNER JOIN modx_web_users ON modx_web_users.id = mil_d_registered.webuser_id
	WHERE
	$s_conditions
	ORDER BY modx_web_users.username ASC
	LIMIT 100
	";
//echo trace2web ($sql, "sql");

$myq = $GLOBALS['dbcon']->qexec( array (
	'sql' => $sql
	, 'expected_affected_rows' => "0:inf"
	, 'get_field_structure' => false
	, 'script_place' => __FILE__.":".__LINE__
));

//trace2file ($sql, "sql", __FILE__, true);
//trace2file ($myq, "myq", __FILE__);

//echo trace2web ($myq, "myq");

//$this_mil_page->output = $myq['results']['records'];


// ######################################
// Re-order rows, starting at row 0 and not 1:
$results;
foreach ($myq['results']['records'] as $row_num => $row)
{
	$results[] = $row;
}
$this_mil_page->output = $results;
echo trace2web ($this_mil_page->output, "this_mil_page->output");





return;

// #############################################################
// #############################################################
// #############################################################
// Functions

?>
