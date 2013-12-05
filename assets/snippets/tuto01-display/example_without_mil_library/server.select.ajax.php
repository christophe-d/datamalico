<?php

// #############################################################
// Page Config (mil_page)
$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
include_once $doc_root."/1001_addon/library/mil_/mil_.conf.php";
include_once $doc_root."/1001_addon/library/datamalico/datamalico_server_dbquery.lib.php";

// #############################################################
// Main

$ajaxOutput = true;

//echo trace2web ($_POST, "_POST");
//echo trace2web ($_GET, "_GET");



// #####################################
// HTTP header for an ajax output or not
if ($ajaxOutput)
{
	// always modified
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

	// HTTP/1.1
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);

	header('Content-type: application/json; charset=utf-8');
	ob_start();	// lock the output buffering

}
else
{
	@header('Content-type: text/html; charset=utf-8');
}




// #####################################
// DO THE JOB:
$ajaxReturn = get_data_from_db ($this_mil_page);


// #####################################
// Output for an ajax output or not
if ($ajaxOutput) 
{
	$ajaxReturn = json_encode($ajaxReturn); // is actually the ajaxReturn
	echo $ajaxReturn;
}
else
{
	echo trace2web($ajaxReturn, "ajaxReturn");	// is active only when $this_mil_page->ajax === false. See mil_page class, ajax property
}

return;


// #############################################################
// #############################################################
// #############################################################
// Local Functions

function get_data_from_db ($this_mil_page)
{
	$dco = new datamalico_server_dbquery ();
	$dco->select( array (
		'sql' => "SELECT * FROM  starwars_data_character ORDER BY starwars_data_character.fullname"
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
