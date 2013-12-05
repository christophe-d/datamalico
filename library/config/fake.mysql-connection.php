<?php
//---------------------------------------------------------------------------

/** 
 * \file
 * \brief This file contains the class Mysql
 * 
 * \author tech_admin
 * \version 1.0
 * \date    2011-12
 *
 * @todo This could be nice to drop all occurences of "global $mysqli_con;" and replace it by $GLOBALS['dbcon'], in order to drop this connexion, but this is really not urgent!!!
 *
 * @todo Remove old functions (but still used functions):
 * 	- mil_mysql_connection(), used in library/mil_/mil_email.class.php
 */
//---------------------------------------------------------------------------


include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_log.class.php";
include_once $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/mil_/mil_mysqli.class.php";


// #####################################################################
// #####################################################################
// #####################################################################
// #####################################################################
//
// mysqli extension
// 	--> new extension
//
// #####################################################################
// #####################################################################


$GLOBALS['DB'] = array (
	'DB_server_name' => "db1234567890.db.yourhostingcompany.com" //"localhost"
	, 'DB_name' => "db1234567890"
	, 'DB_user' => "dbo1234567890"
	, 'DB_pass' => "**************"
	, 'port' => "3306"
	, 'socket' => "" //"/tmp/mysql5.sock"
	, 'description' => ""
);
$GLOBALS['dbcon'] = new mil_mysqli ();
$GLOBALS['mysqli_con'] = new mil_mysqli ();	// delete it in the future, after having drop all "global $mysqli_con;" occurences.

/*
//mil_dbcon_init();
function mil_dbcon_init()
{
	$GLOBALS['dbcon'] = new mil_mysqli ();	// In any case, the GLOBAL var for the connection is destructed even with die(), exit(), or header().

	//echo trace2web ($GLOBALS['dbcon'], "GLOBALS['dbcon'] - " . __FILE__.":".__LINE__);
}

function mil_mysqli_connection()
{
	//return $GLOBALS['dbcon'];


	$DB_name = $GLOBALS['DB']['DB_name'];
	$DB_server_name = "db1234567890.db.yourhostingcompany.com"; //"localhost";
	$port = "3306";
	$DB_user_name = $GLOBALS['DB']['DB_user'];
	$DB_pass = $GLOBALS['DB']['DB_pass'];
	$socket = ""; //"/tmp/mysql5.sock";
	$description = "";

	$mil_mysqli_connection = new mysqli($DB_server_name, $DB_user_name, $DB_pass, $DB_name, $port/*, $socket * /);
	if ($mil_mysqli_connection->connect_errno) 
	{
		new mil_Exception ("This is not possible to connect to the DataBaseServer - " . $mil_mysqli_connection->connect_error, "1112101505", "ERROR", __FILE__ .":". __LINE__ );
		return;
	}

	return $mil_mysqli_connection;
}
*/


// #####################################################################
// #####################################################################
// #####################################################################
// #####################################################################
//
// mysql extension
// 	--> old extension
//
// #####################################################################
// #####################################################################

function mil_mysql_connection ()
{
	$DB_name = $GLOBALS['DB']['DB_name'];	
	$DB_server_name = $GLOBALS['DB']['DB_server_name']; //"localhost:/tmp/mysql5.sock";
	$DB_user_name = $GLOBALS['DB']['DB_user'];	
	$DB_pass = $GLOBALS['DB']['DB_pass'];
	$description = "";



	$mil_mysql_connection = mysql_connect($DB_server_name, $DB_user_name, $DB_pass);
	if (!$mil_mysql_connection) new mil_Exception ("This is not possible to connect to the DataBaseServer - " . mysql_error(), "1112101505", "WARN", __FILE__ .":". __LINE__ );
	// DB selection :
	if (!mysql_select_db($DB_name, $mil_mysql_connection)) new mil_Exception ("This is not possible to select the DataBase", "1112101508", "WARN", __FILE__ .":". __LINE__ );
	/*
	$sql_init = "
		SET SESSION character_set_server = 'utf8';
		SET SESSION collation_server = 'utf8_general_ci';
	";

	// Exec SQL :
	$result_resource = mysql_query($sql_init, $mil_mysql_connection); 
	if (!$result_resource) new mil_Exception ("This is not possible to execute the request: $sql", "1201111240", "WARN", __FILE__ .":". __LINE__ );
	 */

	return $mil_mysql_connection;
}


?>
