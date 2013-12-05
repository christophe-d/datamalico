<?php
/** 
 * @file
 * File where the datamalico_server_dbquery API is defined.
 *
 * This file is the configuration file for the datamalico library.
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2013
 */


// ######################################################
// lang/
$dco_lang = "english";
include_once realpath($_SERVER["DOCUMENT_ROOT"])."/1001_addon/library/datamalico/lang/" . $dco_lang . ".lang.php";
$GLOBALS['mil_lang_common'] = array_merge ((array) $GLOBALS['mil_lang_common'], $mil_lang_common); // For translation


// ######################################################
// Pagination : dafault values are :
$GLOBALS['pagination']['page'] = 1;
$GLOBALS['pagination']['perpage'] = 5;


?>
