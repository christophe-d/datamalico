<?php

// AJAX :
if ($GLOBALS['ajax']) 
{
	// always modified
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

	// HTTP/1.1
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);

	header('Content-type: application/json; charset=utf-8');
	ob_start();
	//new mil_Exception ("debugMessage" . __LINE__, "AJAX_DEBUG", "WARN", __FILE__ .":". __LINE__ );
}
else
{
	header('Content-type: text/html; charset=utf-8');
}


$ajaxReturn['metadata']['returnCode'] = "ERROR";
$ajaxReturn['metadata']['returnMessage'] = "ERROR";
$ajaxReturn['metadata']['debugMessage'];
$ajaxReturn['metadata']['sql_query'];
$ajaxReturn['metadata']['records_number'];
$ajaxReturn['metadata']['insert_id'];
$ajaxReturn['metadata']['affected_rows'];
$ajaxReturn['results']['records'];
$ajaxReturn['results']['field_structure'];
$ajaxReturn['results']['html'];
$ajaxReturn['results']['script'];			// eval($("#script_to_be_executed_on_client_side").html());


?>
