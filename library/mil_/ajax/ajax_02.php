<?php

// AJAX :
if ($GLOBALS['ajax']) 
{
	ob_end_clean();
	//$ajaxReturn = mixed_htmlentities($ajaxReturn);
	$ajaxReturn = json_encode($ajaxReturn);

	//$ajaxReturn = json_encode($ajaxReturn);
	//$ajaxReturn = mixed_rawurlencode($ajaxReturn); // --> o.responseText gets some nul values if there are some chars : éàç...
	//$ajaxReturn = mixed_convert_uuencode($ajaxReturn);	// makes a js bug: Uncaught TypeError: Object [object Window] has no method 'is_scalar' 

	echo $ajaxReturn;
}
else
{
	//echo debugDisplayTable($ajaxReturn, "ajaxReturn");
}

?>
