<?php

/** 
 * \file
 * \brief This file contains the class mil_Email
 * 
 * \author tech_admin
 * \version 1.0
 * \date    2011-12
 */
//---------------------------------------------------------------------------

//---------------------------------------------------------------------------
/** 
 * \class mil_Email
 * \brief is the class relative to the mil_Email handling
 * \author tech_admin
 * \version 1.0
 * \date    2011-12
 * 
 * Has to manage the preferences of the user, such as themes...
 */
//---------------------------------------------------------------------------

include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_log.class.php";

class mil_Email
{
	var $to;
	var $subject;
	var $message;
	var $from="";
	var $cc=""; 
	var $bcc="";
	var $now;

	/*
	 * $message = '
	 <html>
	 <head>
	 <title>Calendrier des anniversaires pour Aout</title>
	 </head>
	 <body>
	 <p>Voici les anniversaires a venir au mois d\'Aout !</p>
	 <table>
	 <tr>
	 <th>Personne</th><th>Jour</th><th>Mois</th><th>Annee</th>
	 </tr>
	 <tr>
	 <td>Josiane</td><td>3</td><td>Aout</td><td>1970</td>
	 </tr>
	 <tr>
	 <td>Emma</td><td>26</td><td>Aout</td><td>1973</td>
	 </tr>
	 </table>
	 </body>
	 </html>
	 ';
	 */


	/** 
	 * \fn function mil_Email ($security, $userTheme)
	 * \brief is the constructor.
	 * \param $security
	 * \param $userThem
	 * i.e : $mymil_Email = new mil_Email($to, $subject, $message, "no-reply@toto.com");
	 */
	function __construct ($to, $subject, $message, $from="", $cc="", $bcc="")
	{
		//debugDisplayVariables ();
		$this->to = $to;
		$this->subject = $subject;
		$this->message = $message;
		$this->from = $from;
		$this->cc = $cc;
		$this->bcc = $bcc;

		// Pour envoyer un mail HTML, l'en-tete Content-type doit etre defini
		$this->headers = 'MIME-Version: 1.0' . "\r\n";
		$this->headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		// En-tetes additionnels
		//$this->headers .= "To: $this->to" . "\r\n";
		$this->headers .= "From: " . $this->from . "\r\n";
		$this->headers .= "Cc: " . $this->cc . "\r\n";
		$this->headers .= "Bcc: " . $this->bcc . "\r\n";

		$this->now = now();

		//echo debugDisplayTable($this, "this");


		##################################################
		# Verify if the email is valid
		$mil_con = mil_mysql_connection ();
		$email = $this->to;
		$sql = "
			select reg.valid_email 
			from modx_web_users web, mil_d_registered reg
			where web.id = reg.webuser_id
			and web.username = '$email'
			";
		// Exec SQL :
		$result_resource = mysql_query($sql, $mil_con);
		if (!$result_resource) new mil_Exception ("This is not possible to execute the request: $sql", "1201111240", "WARN", __FILE__ .":". __LINE__ );
		$nbRes = mysql_num_rows($result_resource);
		$row;
		if ($nbRes == 1)
		{ 
			$row = mysql_fetch_array ($result_resource, MYSQL_ASSOC);
		}
		mysql_free_result($result_resource);
		mysql_close($mil_con);


		##################################################
		# If not valid, then do not send the mail, and log it into file
		if ($row['valid_email'] !== "0") // if valid_email is '1' (we kano that the email is correct, or if is null (we still know about this email)
		{

			// ###################################################################################################
			// ###################################################################################################
			// ###################################################################################################
			// ###################################################################################################
			//trace2file("", "", __FILE__, true);
			//trace2file("mil_Email::__construct()", __LINE__, __FILE__);
			//trace2file ($this->to, "this->to", __FILE__);
			//trace2file ($this->subject, "this->subject", __FILE__);
			//trace2file ($this->headers, "this->headers", __FILE__);
			//trace2file ($this->message, "this->message", __FILE__);

			//$delivered_email = true; // in order to stop the email sending
			$delivered_email = mail ($this->to, $this->subject, $this->message, $this->headers);
			// ###################################################################################################
			// ###################################################################################################
			// ###################################################################################################
			// ###################################################################################################

			if ($delivered_email)
			{
				//$this->validate_email();
			}
			else
			{
				$logfile = fopen($GLOBALS['config_ini']['site_root']."/1001_addon/logs/invalid_emails." . date("Y-m-d") . ".log", "a");

				flock($logfile, LOCK_EX);
				fputs($logfile, $this->now . "\t" . $this->to . "\n");
				flock($logfile, LOCK_UN);

				fclose($logfile);

				//$this->invalidate_email();
			}
		}
		else
		{
			$logfile = fopen($GLOBALS['config_ini']['site_root']."/1001_addon/logs/tryToSendMail_to_invalid_email." . date("Y-m-d") . ".log", "a");

			flock($logfile, LOCK_EX);
			fputs($logfile, $this->now . "\t" . $this->to . "\t" . $this->subject . "\t" . $this->message . "\t" . $this->from . "\n");
			flock($logfile, LOCK_UN);

			fclose($logfile);
		}
	}


	function validate_email()
	{
		//trace2file("validate_email", __LINE__, __FILE__);
		$mil_con = mil_mysql_connection ();

		$lastEmailChecking_acceptedForDelivery = $this->now;
		$email = $this->to;

		$sql = "
			UPDATE `mil_d_registered`
			SET valid_email = 1
			, lastEmailChecking_acceptedForDelivery = '$lastEmailChecking_acceptedForDelivery'
			WHERE webuser_id in (
				select id
				from modx_web_users web
				where web.username = '$email'
			);
		";

		$result_resource = mysql_query($sql, $mil_con); 
		if (!$result_resource)
		{
			trace2file("", __LINE__, __FILE__);
			new mil_Exception ("In " . __FUNCTION__ . "(), This is not possible to execute the request: $sql", "1201111240", "ERROR", __FILE__ .":". __LINE__ );
		}
		mysql_close($mil_con);

		/*
		$myq = $GLOBALS['dbcon']->qexec( array (
			'sql' => $sql
			, 'script_place' => __FILE__.":".__LINE__
		));
		trace2file ($myq, "myq", __FILE__);
		 */
	}

	function invalidate_email()
	{
		//trace2file("invalidate_email", __LINE__, __FILE__);
		$mil_con = mil_mysql_connection ();

		$lastEmailChecking_acceptedForDelivery = now();
		$email = $this->to;

		$sql = "
			UPDATE `mil_d_registered`
			SET valid_email = 0
			, lastEmailChecking_acceptedForDelivery = '$lastEmailChecking_acceptedForDelivery'
			WHERE webuser_id in (
				select id
				from modx_web_users web
				where web.username = '$email'
			);
		";

		$result_resource = mysql_query($sql, $mil_con); 
		if (!$result_resource) 
		{
			trace2file("", __LINE__, __FILE__);		
			new mil_Exception ("In " . __FUNCTION__ . "(), This is not possible to execute the request: $sql", "1201111240", "ERROR", __FILE__ .":". __LINE__ );
		}
		mysql_close($mil_con);

		/*
		$myq = $GLOBALS['dbcon']->qexec( array (
			'sql' => $sql
			, 'script_place' => __FILE__.":".__LINE__
		));
		trace2file ($myq, "myq", __FILE__);
		 */
	}

}  // end of the mil_Email class

?>
