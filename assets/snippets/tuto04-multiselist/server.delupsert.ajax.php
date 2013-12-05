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
//trace2file ($this_mil_page, "this_mil_page", __FILE__);

// #############################################################
// Main, do the job on the server and returns a JSON output (if mil_page::ajax===true):

// ##### (optional, just to give you ideas):
// On the server side, before the INSERT, UPDATE or DELETE, you can force the addition of any data into the record
// by adding a fake param _GET or _POST param. Regarding the format of this array, see http://5sens6com.com/1001_addon/documentation/datamalico/html/classdatamalico__server__ajax.html#details
$_POST['delupsert']['input_div_2'] = $_POST['delupsert']['input_div'];
unset($_POST['delupsert']['input_div_2']['f']['fullname']);
$_POST['delupsert']['input_div_2']['f']['description'] = "A new Jedi";

// #####
// datamalico_server_ajax object creation
$dco_ajax = new datamalico_server_ajax ( array (
	'page_params' => array  ($_GET, $_POST)		// Creates the datamalico ajax object and create a nice accessible list of GET and POST params.
));

// #####
// Precise security as you want targeting only some accessible records (See horizontal security in backend_acce.conf.php in the documentation):
$dco_ajax->set_horizontal_access ( array (
	'custom_horizontal_access_function' => "my_set_horizontal_access"
	, 'custom_horizontal_access_args' => array (
		'this_mil_page' => $this_mil_page
		, 'char_id' => get_char_id_on_client_ip()
	)
));		//echo trace2web($dco_ajax->input['delupsert_list'], "set_horizontal_access");

// #####
$dco_ajax->input_data_validator ();	//echo trace2web($dco_ajax->input['delupsert_list'], "input_data_validator");
$dco_ajax->input_data_validator_add_custom (get_custom_data_validation($dco_ajax, $this_mil_page));
//if ($dco_ajax->are_there_invalid_data () === false) $isok=true;	// ok


// ##### (optional, just to give you ideas):
// Add this field to any of the record you insert or update (like a key to identify records belonging to your IP address).
$dco_ajax->input['delupsert_list']['update']['input_div']['data_itself']['fields']['owner_ip'] = $_SERVER["REMOTE_ADDR"];

//trace2file ($dco_ajax->input['delupsert_list'], "dco_ajax->input['delupsert_list']", __FILE__);

$dco_ajax->delupsert();
$this_mil_page->output = $dco_ajax->output;

//trace2file ($this_mil_page->output, "this_mil_page->output", __FILE__);
echo trace2web ($this_mil_page->output, "this_mil_page->output");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property

return;

// #############################################################
// #############################################################
// #############################################################
// Local Functions

function my_set_horizontal_access ($one_record_selector, $custom_horizontal_access_args)
{
	//echo trace2web($one_record_selector, "one_record_selector"); // Trace with a handy display (This uses the mil_ help library)
	$client_ip = $_SERVER["REMOTE_ADDR"];

	$fn_return = array ();          // by default, the ['horizontal_access'] will be false;
	$table_name = $one_record_selector['table_name'];

	// Horizontal security, precising the record selector:
	if ($table_name === "starwars_data_character")
	{
		$fn_return['conditions'] = array (
			'owner_ip' =>  $client_ip // allow only INSERT, UPDATE and DELETE on the records tagged with your IP-address.
			// This is the horizontal security. See the backend_access.conf.php documentation and search the word "security"
			// (http://datamalico.org/1001_addon/documentation/datamalico/html/backend__access_8conf_8php.html#details).
		);
		$fn_return['horizontal_access'] = true;
	}
	else if ($table_name === "starwars_data_character2attribute")
	{
		$fn_return['conditions'] = array (
			'char_id' => $custom_horizontal_access_args['char_id'] // allow only INSERT, UPDATE and DELETE on the records tagged with your IP-address.
			// This is the horizontal security. See the backend_access.conf.php documentation and search the word "security"
			// (http://datamalico.org/1001_addon/documentation/datamalico/html/backend__access_8conf_8php.html#details).
			);
		$fn_return['horizontal_access'] = true;
	}

	//echo trace2web($fn_return, "fn_return");
	return $fn_return;
}

/*
 * Returns an array of errors to be sent as parameter of datamalico_server_ajax::input_data_validator_add_custom()
 * 	http://datamalico.org/1001_addon/documentation/datamalico/html/classdatamalico__server__ajax.html#ada33e163f12c51f56281d7492d081cfd
 */
function get_custom_data_validation (&$dco_ajax, $this_mil_page)
{
	$array_of_errors = array();


	// ###### Trace if necessary in a sibling file to this one called server.delupsert.ajax.php.trace.log.txt (This uses the mil_ help library)
	//trace2file ("", "", __FILE__, true);
	//trace2file ($dco_ajax, "dco_ajax", __FILE__);
	//trace2file ($this_mil_page, "this_mil_page", __FILE__);


	// ######
	// Process a first verification and return an error if necessary:
	$fullname = $dco_ajax->input['delupsert_list']['update']['input_div']['data_itself']['fields']['fullname'];
	$fullname = $this_mil_page->page_params['delupsert']['input_div']['f']['fullname'];

	$findme = 'Jabba';
	$is_found = stripos($fullname, $findme); // Is 'Jabba' found in the fullname?

	$another_check = true;
	$still_another_check = true;

	if (
		$is_found !== false
		&& $another_check === true
		&& $still_another_check === true
	)
	{
		$metadata['valid'] = false;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['starwars_data_character.fullname.solo_error'];

		$array_of_errors[] = array (
			'html_container' => "input_div"
			, 'metadata' => array (
				'valid' => false
				, 'returnMessage' => $GLOBALS['mil_lang_common']['starwars_data_character.fullname.jabba_error']
			)
		);
	}


	// ######
	// Process a 2nd verification and return an 2nd error if necessary:
	// $array_of_errors[] = array();
	// ...


	// ######
	// TIP:
	// in order to return several errors, you can use the PHP function array_merge().


	return $array_of_errors;
}

/*
 * This function returns the character id of the client IP address.
 */
function get_char_id_on_client_ip()
{
	$char_id = 0;
	$client_ip = $_SERVER["REMOTE_ADDR"];
	$sql = "
		SELECT char_id
		FROM starwars_data_character
		WHERE
		owner_ip = '$client_ip'
		ORDER BY change_date DESC
		";


	// ... and use this occasion to simplify your work when querying a MySQL database and use mil_mysqli
	$myquery = $GLOBALS['dbcon']->qexec( array (
		'sql' => $sql
		, 'expected_affected_rows' => "0:inf"
		, 'get_field_structure' => false
		, 'script_place' => __FILE__.":".__LINE__
	));

	// if there is one result, so if there is one character with the client IP address:
	if ($myquery['metadata']['affected_rows'] === 1)
	{
		$char_id = $myquery['results']['records'][1]['char_id'];		
	}

	return $char_id;
}
?>
