<?php
//---------------------------------------------------------------------------
/** 
 * \file
 * \brief The configuration file of the mil_ general library.
 * 
 * \author Christophe Delcourte
 * 
 * \version 1.0
 * \date    2011-12-07
 */
//---------------------------------------------------------------------------



// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// 
// Technical config:
//
// ##################################################################################################################################################################
// ##################################################################################################################################################################
// ##################################################################################################################################################################


$GLOBALS['timer']['now'] = date("Y-m-d H:i:s");
$GLOBALS['timer']['rigth_now'] = microtime(true);

if ($_SERVER['DOCUMENT_ROOT'] !== NULL) $GLOBALS['env_type'] = "webserver"; // webserver : CGI (Common Gateway Interface)
else if ($_SERVER['HOME'] !== NULL) $GLOBALS['env_type'] = "commandline"; // shell : CLI (Command-line interface)


//$_SERVER["HTTP_X_FORWARDED_HOST"]	=	ssl.yourhostingcompany.fr
//$_SERVER["HTTP_X_FORWARDED_SERVER"]	=	ssl.yourhostingcompany.fr
//if (isset($_SERVER["HTTP_X_FORWARDED_SERVER"])) $GLOBALS['config_ini']['site_domain'] = $_SERVER["HTTP_X_FORWARDED_SERVER"] ."/". $_SERVER['SERVER_NAME']; // yourdomain.com
//else $GLOBALS['config_ini']['site_domain'] = $_SERVER['SERVER_NAME']; // yourdomain.com
$GLOBALS['config_ini']['site_domain'] = $_SERVER['SERVER_NAME']; // yourdomain.com
//_SERVER["REQUEST_URI"]

//phpinfo(); //echo $GLOBALS['config_ini']['site_domain']; //die();



// ######################################################
// Check environment:
// $modx->config['base_path']: /homepages/23/d400325672/htdocs/www/decorons/01_production/
if ($GLOBALS['env_type'] === "webserver")
{
	$GLOBALS['config_ini']['site_root'] = realpath($_SERVER["DOCUMENT_ROOT"]); //"/kunden/homepages/28/d147435597/htdocs/www/decorons/01_production/";

	//$GLOBALS['config_ini']['site_root'] = realpath($_SERVER["DOCUMENT_ROOT"]) . $_SERVER['SCRIPT_NAME']; //"/kunden/homepages/28/d147435597/htdocs/www/decorons/01_production/";
	//$path_parts = pathinfo($GLOBALS['config_ini']['site_root']);
	//$GLOBALS['config_ini']['site_root'] =  realpath($path_parts['dirname']);
}
else if ($GLOBALS['env_type'] === "commandline")
{
	$GLOBALS['config_ini']['site_root'] = realpath(dirname(dirname(dirname(__FILE__))));
}
else die("ERROR of env_type");


//$GLOBALS['config_ini']['protocol'] = gettype($_SERVER['HTTPS']) === "NULL" ? "http" : "https";
if (
	!empty($_SERVER['HTTPS'])
	&& $_SERVER['HTTPS'] !== 'off'
	|| $_SERVER['SERVER_PORT'] == 443
)
{
	$GLOBALS['config_ini']['protocol'] = 'https';
}
else
{
	$GLOBALS['config_ini']['protocol'] = 'http';
}

$GLOBALS['config_ini']['this_page_full_url'] = $GLOBALS['config_ini']['protocol'] . "://" . $GLOBALS['config_ini']['site_domain'] . "" . $_SERVER['REQUEST_URI'];
// trace2file ($GLOBALS['config_ini']['this_page_full_url'], "this_page_full_url", __FILE__);

// https://yourdomain.com
// $GLOBALS['config_ini']['protocol']."://".$GLOBALS['config_ini']['site_domain']."/"

// ######################################################
// Main includes:
$path = "1001_addon/library"; // for the website
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

$path = "../1001_addon/library"; // for the manager
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

$path = $GLOBALS['config_ini']['site_root']."/1001_addon/library"; // for the manager
set_include_path(get_include_path() . PATH_SEPARATOR . $path);


$path_parts = pathinfo($GLOBALS['config_ini']['site_root']);

// first includes:
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_mysqli.class.php";
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/config/mysql-connection.php";
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_.lib.php";

// ######################################################
// Other includes:
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_email.class.php";
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_exception.class.php";
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_log.class.php";


// ######################################################
// Debug:
//echo trace2web (__FILE__.":".__LINE__);
$GLOBALS['config_ini']['trace2file']['state'] = true; // true=active, false=inactive
$GLOBALS['config_ini']['trace2file']['file'] = $GLOBALS['config_ini']['site_root'] . "/1001_addon/logs/trace2file";
trace2file ("", "", $GLOBALS['config_ini']['trace2file']['file'], true, 0600);


// ######################################################
// Region params:

$GLOBALS['config_ini']['region'] = get_region_settings ();	// Here default variables are overridded by get_region_settings(), if it can be, otherwise, default are kept.
//echo trace2web ($GLOBALS['config_ini']['region'], "GLOBALS['config_ini']['region']");

//echo trace2web (getlocale(), "ATTENTION This is the first getlocale()");

// lang/
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/lang/" . $GLOBALS['config_ini']['region']['lang'] . ".lang.php";
$GLOBALS['mil_lang_common'] = array_merge ((array) $GLOBALS['mil_lang_common'], (array) $mil_lang_common); // For translation

// lang/ of datamalico
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/datamalico/lang/" . $GLOBALS['config_ini']['region']['lang'] . ".lang.php";
$GLOBALS['mil_lang_common'] = array_merge ((array) $GLOBALS['mil_lang_common'], (array) $mil_lang_common);

// Must be after having set lang GLOBALS['config_ini']['region']['lang'], because datamalico_server_dbquery.lib.php must know about 
// 	the file: $GLOBALS['config_ini']['region']['lang'] . ".lang.php"
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/mil_/mil_page.class.php";
include_once $GLOBALS['config_ini']['site_root']."/1001_addon/library/datamalico/datamalico_server_dbquery.lib.php";

$GLOBALS['config_ini']['langs'] = 'refresh';



// ######################################################
// Security:
// Load here your own application security settings.


// ######################################################
// Analyse with sections
//$config_ini = parse_ini_file("1001_addon/assets/decorons.ini", true);
$GLOBALS['config_ini']['administration']['tech_admin_email'] = "Tech Admin <webmaster@yourdomain.com>";
$GLOBALS['config_ini']['logs']['log_level'] = "INFO" ; //FATAL=1, ERROR=2, WARN=3, INFO=4, DEBUG=5, TRACE=6
$GLOBALS['config_ini']['logs']['email_alert_level'] = "ERROR" ; // Minimum level of sending an email to the $config_ini['administration']['tech_admin_email'] 
// and, whatever the value of $config_ini['logs']['log_with_full_details'] is, details are 
// sent anyway to give informations by email to the administrator.
$GLOBALS['config_ini']['logs']['log_with_full_details'] = "FALSE" ; //TRUE OR FALSE is done to light up the size of log files, 
// and speedup processes (because, full details are so huge that it can slow down the server)


// ######################################################
// Technical values:
$lang = $GLOBALS['config_ini']['region']['lang'];
$GLOBALS['config_ini']['DB']['mil_c_country'] = get_config_table ("mil_c_country", "country_id", $lang);
$GLOBALS['config_ini']['DB']['mil_c_country.lang'] = get_config_table ("mil_c_country", "country_id", "lang");
$GLOBALS['config_ini']['DB']['mil_c_currency.currency_code'] = get_config_table ("mil_c_currency", "currency_id", "currency_code");
//$GLOBALS['config_ini']['DB']['mil_c_currency.currency_display'] = get_config_table ("mil_c_currency", "currency_code", "currency_display");
$GLOBALS['config_ini']['DB']['mil_c_country_phone'] = get_config_table ("mil_c_country", "country_id", "calling_code");


//echo trace2web($GLOBALS['config_ini']['DB']['mil_c_currency.currency_code'], "GLOBALS['config_ini']['DB']['mil_c_currency.currency_code']");
//echo trace2web($GLOBALS['config_ini']['DB']['mil_c_currency.currency_display'], "GLOBALS['config_ini']['DB']['mil_c_currency.currency_display']");



// ######################################################
// Init JS Libs :
$extension = ".js"; // ".min.js"
$jquery = "jquery-1.8.0";	// $jquery = "jquery-1.8.0.min.js";
$jquery_ui = "jquery-ui-1.8.23.custom";
//$jquery_ui = "jquery-ui-1.9.2.custom";

$GLOBALS['mil_lang_common']['jquery_lib'] = $jquery.$extension;
$GLOBALS['mil_lang_common']['jquery_ui_lib'] = $jquery_ui.$extension;

$GLOBALS['config_ini']['JS']['link_to_functional_JS_libs_publicsite'] = '

	<!--For jquery =============================== -->
	<script type="text/javascript" src="1001_addon/assets/templates/common/js/'.$jquery.$extension.'"></script>

<!--For jqueryui =============================== -->
<link type="text/css" href="1001_addon/assets/templates/common/'.$jquery_ui.'/css/custom-theme-1/'.$jquery_ui.'.css" rel="stylesheet" />
<link type="text/css" href="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/themes/base/jquery.ui.all.css" rel="stylesheet" media="screen" />

<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/'.$jquery_ui.'.js"></script><!-- general -->
<!-- <script src="1001_addon/assets/templates/common/'.$jquery_ui.'/js/'.$jquery_ui.'.min.js"></script> general minified -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.core.js"></script><!-- Dialog, Button, Accordion -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.widget.js"></script><!-- Dialog, Button, Accordion -->

<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/external/jquery.bgiframe-2.1.2.js"></script><!-- Dialog -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.mouse.js"></script><!-- Dialog -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.draggable.js"></script><!-- Dialog -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.position.js"></script><!-- Dialog -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.resizable.js"></script><!-- Dialog -->
<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.dialog.js"></script><!-- Dialog -->

<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.button.js"></script><!-- Button -->

<script src="1001_addon/assets/templates/common/'.$jquery_ui.'/development-bundle/ui/jquery.ui.accordion.js"></script><!-- Accordion -->

<!--For datamalico =============================== -->
<script type="text/javascript" src="1001_addon/assets/templates/common/js/datamalico.lib.js"></script>
<link rel="stylesheet" type="text/css" href="1001_addon/assets/templates/common/css/datamalico.css" media="screen" />

<script type="text/javascript" src="1001_addon/assets/templates/common/js/jquery.paging.js"></script>
<link rel="stylesheet" type="text/css" href="1001_addon/assets/templates/common/css/pagination.css" media="screen" />

';





$GLOBALS['config_ini']['JS']['link_to_functional_JS_libs_managersite'] = '

	<!-- For mil_manager lib ============================== -->
	<link rel="stylesheet" type="text/css" href="1001_addon/assets/snippets/_manager/css/mil_manager.css" />
	<script src="1001_addon/assets/snippets/_manager/js/MANAGER_LIBRARY.js" type="text/javascript"></script>




	<!--
	<link rel="stylesheet" type="text/css" href="1001_addon/yui/build/fonts/fonts-min.css" />
	<script type="text/javascript" src="1001_addon/yui/build/tabview/tabview-min.js"></script>

	For YUI ==============================

	<link rel="stylesheet" type="text/css" href="1001_addon/assets/snippets/_manager/css/tabview.css" />
	<link rel="stylesheet" type="text/css" href="1001_addon/yui/build/container/assets/skins/sam/container.css" />
	-->

	<!-- Tab managing ============================== 
	<style type="text/css">
		/*margin and padding on body element
		can introduce errors in determining
		element position and are not recommended;
		we turn them off as a foundation for YUI
		CSS treatments. */
		body {
			/*margin:0;*/
			padding:0;
		}
	</style>
		-->
';


// The mil_.lib.js and its good language:
$GLOBALS['config_ini']['JS']['link_to_mil_lib'] = get_JS_withGoodLang_fromDynamicCache ("/1001_addon/assets/templates/common/js/mil_.lib.js");


$GLOBALS['config_ini']['head'] = $GLOBALS['config_ini']['JS']['link_to_functional_JS_libs_publicsite'] . $GLOBALS['config_ini']['JS']['link_to_mil_lib'];


?>
