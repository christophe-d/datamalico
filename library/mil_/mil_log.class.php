<?php

/** 
 * \file
 * \brief This file contains the class mil_Log
 * 
 * \author tech_admin
 * \version 1.0
 * \date    2011-12
 */
//---------------------------------------------------------------------------

//---------------------------------------------------------------------------
/** 
 * \class mil_Log
 * \brief is the class relative to the mil_Log handling
 * \author tech_admin
 * \version 1.0
 * \date    2011-12
 * 
 * Has to manage the preferences of the user, such as themes...
 */
//---------------------------------------------------------------------------


include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_.conf.php";
include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_email.class.php";


// While coding, in order to generate a uniqLogNumber, please use /Users/zigzag/_serveurdedie/workingcopy/admin/_overall/uniqLogNumberGenerator.sh
class mil_Log 
{
	var $log_level_ini; // General config log level.
	var $email_alert_level_ini;

	var $logNumber;
	var $level_txt;
	var $level_number;
	var $message;
	var $file_and_line;

	var $now;
	var $exec_context;
	var $theSuperGlobals;


	/** 
	 * \fn function mil_Log ($security, $userTheme)
	 * \brief is the constructor.
	 * \param $security
	 * \param $userThem
	 * i.e : $mymil_Log = new mil_Log($logNumber, $level, $message);
	 */
	function mil_Log ($logNumber, $level, $message = "", $file_and_line = "")
	{
		$this->read_ini_logs_values ();

		$this->logNumber = $logNumber;
		$this->level_txt = $level;
		$this->set_level_number();
		$this->message = $message;
		$this->file_and_line = $file_and_line;

		$this->now = date("Y-m-dTH:i:s");
		//$this->exec_context = print_r(debug_backtrace(), true); // The function debug_print_backtrace() displays its result on the web page, even if wou don't display it expressly.
		$this->theSuperGlobals = $theSuperGlobals;


		/*
		$_GET
		$_POST
		$_SESSION
		$_COOKIE
		$_FILES
		$_SERVER
		$_ENV
		 */
		$this->theSuperGlobals = '$_GET :' . print_r($_GET, true);
		$this->theSuperGlobals .= ' $_POST : ' . print_r($_POST, true);
		$this->theSuperGlobals .= ' $_SESSION : ' . print_r($_SESSION, true);
		$this->theSuperGlobals .= ' $_COOKIE : ' . print_r($_COOKIE, true);
		$this->theSuperGlobals .= ' $_FILES : ' . print_r($_FILES, true);
		//$this->theSuperGlobals .= ' $_SERVER : ' . print_r($_SERVER, true);
		//$this->theSuperGlobals .= ' $_ENV : ' . print_r($_ENV, true);

		if ($this->level_number <= $this->log_level_ini)
		{
			$this->logIntoFile();
		}

		if ($this->level_number <= $this->email_alert_level_ini)
		{
			$this->alert_by_email();
		}
	}




	function logIntoFile ()
	{
		global $config_ini;

		$patterns = array();
		$patterns[] = "/\n/";
		$patterns[] = "/\r/";
		$patterns[] = "/\t/";
		$replacements = array();
		$replacements[] = '{LF}';
		$replacements[] = '{CR}';
		$replacements[] = '{TAB}';

		$message = preg_replace($patterns, $replacements, $this->message);

		global $config_ini;//$config_ini = parse_ini_file("1001_addon/assets/decorons.ini", true); // This is here necessary to load the ini file, because when you load from outside the class, the $config_ini is not known inside the class because using GLOBALS has never work on this server.
		if ($config_ini['logs']['log_with_full_details'] == "TRUE")
		{
			$exec_context = preg_replace($patterns, $replacements, $this->exec_context);
			$theSuperGlobals = preg_replace($patterns, $replacements, $this->theSuperGlobals);
		} else {
			$exec_context = "";
			$theSuperGlobals = "";
		}

		$file_full_path = $config_ini['site_root']."/1001_addon/logs/1001logs." . date("Y-m-d") . ".log";
		$logfile = fopen($file_full_path, "a");

		chmod($file_full_path, 0600);

		flock($logfile, LOCK_EX);
		fputs($logfile, $this->now . "\t" . $this->level_txt . "\t" . $this->logNumber . "\t" . $message . "\t" . $this->file_and_line . "\t" . $exec_context . "\t" . $theSuperGlobals . "\n");
		//fputs($logfile, $this->now . "\t" . $this->level_txt . "\t" . $this->logNumber . "\n");
		flock($logfile, LOCK_UN);

		fclose($logfile);
	}


	function alert_by_email ()
	{
		global $config_ini;//$config_ini = parse_ini_file("1001_addon/assets/decorons.ini", true); // This is here necessary to load the ini file, because when you load from outside the class, the $config_ini is not known inside the class because using GLOBALS has never work on this server.
		$to = $config_ini['administration']['tech_admin_email'];
		$subject = "cms decorons, $this->level_txt at $this->now, number: $this->logNumber";
		$message = <<<EOT
			<html>
			<head>
			<title>cms decorons, $this->level_txt at $this->now, number: $this->logNumber</title>
			</head>
			<body>
			cms decorons<hr />

			<table>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">level</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">$this->level_txt</td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">logged at</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">$this->now</td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">lognumber</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">$this->logNumber</td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">logmessage</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">$this->message</td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">occured at</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">$this->file_and_line</td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">exec_context</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;"><pre>$this->exec_context</pre></td>
			</tr>

			<tr>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;">theSuperGlobals</td>
			<td style="border-style:solid; border-width:1px; vertical-align:text-top;"><pre>$this->theSuperGlobals</pre></td>
			</tr>

			</table>

			</body>
			</html>
EOT;

		$mymil_Email = new mil_Email($to, $subject, $message, $GLOBALS['config_ini']['administration']['tech_admin_email']);
	}




	/** 
	 * \fn function read_ini_logs_values ()
	 * \brief Sets 2 properties of this object: log_level_ini and email_alert_level_ini.
	 * i.e : $this->read_ini_logs_values ();
	 */
	function read_ini_logs_values ()
	{
		global $config_ini;//$config_ini = parse_ini_file("1001_addon/assets/decorons.ini", true); // This is here necessary to load the ini file, because when you load from outside the class, the $config_ini is not known inside the class because using GLOBALS has never work on this server.

		//FATAL=1, ERROR=2, WARN=3, INFO=4, DEBUG=5, TRACE=6
		switch ($config_ini['logs']['log_level']) { // Here I use a switch, because defining a constant doesn't work, and using GLOBALS has never work on this server.
		case "FATAL": $this->log_level_ini = 1; break;
		case "ERROR": $this->log_level_ini = 2; break;
		case "WARN": $this->log_level_ini = 3; break;
		case "INFO": $this->log_level_ini = 4; break;
		case "DEBUG": $this->log_level_ini = 5; break;
		case "TRACE": $this->log_level_ini = 6; break;
		default: $this->log_level_ini = 2;
		}
		settype($this->log_level_ini, "integer");


		//FATAL=1, ERROR=2, WARN=3, INFO=4, DEBUG=5, TRACE=6
		switch ($config_ini['logs']['email_alert_level']) { // Here I use a switch, because defining a constant doesn't work, and using GLOBALS has never work on this server.
		case "FATAL": $this->email_alert_level_ini = 1; break;
		case "ERROR": $this->email_alert_level_ini = 2; break;
		case "WARN": $this->email_alert_level_ini = 3; break;
		case "INFO": $this->email_alert_level_ini = 4; break;
		case "DEBUG": $this->email_alert_level_ini = 5; break;
		case "TRACE": $this->email_alert_level_ini = 6; break;
		default: $this->email_alert_level_ini = 2;
		}
		settype($this->email_alert_level_ini, "integer");

		return TRUE;
	}


	/** 
	 * \fn function set_level_number ()
	 * \brief Sets the property set_level_txt of this object.
	 * i.e : $this->set_level_number ();
	 */
	function set_level_number ()
	{
		switch ($this->level_txt) {
		case "FATAL": $this->level_number = 1; break;
		case "ERROR": $this->level_number = 2; break;
		case "WARN": $this->level_number = 3; break;
		case "INFO": $this->level_number = 4; break;
		case "DEBUG": $this->level_number = 5; break;
		case "TRACE": $this->level_number = 6; break;
		default:
			$this->level_txt = "ATTENTION wrong loglevel spelling for this log: " . $this->level_txt;
			$this->alert_by_email();
		}
		settype($this->level_number, "integer");

		return TRUE;
	}


}  // end of the mil_Log class

?>
