<?php

include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_log.class.php";

class mil_Exception extends Exception
{
	var $now;
	var $admin_message;
	var $errorId;
	var $previous;
	var $client_message;
	var $file_and_line;



	public function __construct($admin_message, $errorId = 0, $errorLevel, $file_and_line = "", Exception $previous = null)
	{
		//trace("admin_message: $admin_message");
		//trace("errorId: $errorId");
		//trace("errorLevel: $errorLevel");
		//trace("file_and_line: $admin_message");
		//trace("previous: $previous");

		$this->now = date("Y-m-dTH:i:s");

		// Custom actions
		$mymil_Log = new mil_Log ($errorId, $errorLevel, "$admin_message - " . mysql_error(), "occured at : $file_and_line");
		$this->client_message = "There is an environmental error. The technical administrator of the website has just been informed. We apologize for this error. Please try again later.<hr />";

		//echo "$this->client_message";

		// Not needed but... is preferable.
		//parent::__construct($admin_message, $errorId);
	}


	// custom string representing the object
	public function __toString()
	{
		return "ERROR: " . __CLASS__ . ": [{$this->code}] at $this->now: {$this->client_message}\n";
	}

	public function inform_client()
	{
		echo $this;
		die();
	}
}

?>
