<?php
/** 
 * @file
 * File where the translation elements are stored for the mil_ help library.
 *
 * Here the variable $mil_lang_common will be merged with with the $GLOBALS['mil_lang_common']. See it in the mil_.conf.php file.
 */

// ######################################################
// For general Javascript:
$mil_lang_common['YourBrowser'] = "Your browser is:";
$mil_lang_common['WatchOutBrowserVersion'] = "This version is already old and can cause navigation problems. You should do an update and or use another up-to-date browser.";
$mil_lang_common['pleaseWait'] = "Please wait...";
$mil_lang_common['mil_problem_notification_box_title'] = "Notify a problem";
$mil_lang_common['mil_abuse_notification_box_title'] = "Report abuse";


// ######################################################
// ERRORS
$mil_lang_common['ERROR'] = "An error has occurred. Contact the webmaster for more information.";
$mil_lang_common['webmaster_is_notified'] = "The webmaster has to be informed, and will correct the problem. Please accept our apologies and try again later.";
$mil_lang_common['bad_form_input'] = "You have not completed the form correctly. Fields with errors you are in red.";
$mil_lang_common['hey'] = "Hey!";
$mil_lang_common['BAD_EMAIL_ADDRESS'] = "Please verify your email address. We have not been able to find you from the email address you provided.";


// For AJAX errors :
$mil_lang_common['ajax_status_not200'] = "We apologize, an ajax error has occurred. The webmaster has to be informed, and will correct the problem. Please accept our apologies and try again later.";
$mil_lang_common['ajax_response_undefined'] = "An error has occurred and the server responds with indefinite manner. The webmaster has been informed, and will correct the problem. Please accept our apologies and try again later.";
$mil_lang_common['ajax_Failure_error'] = "An error has occurred and the server does not respond. The webmaster has been informed, and will correct the problem. Please accept our apologies and try again later.";


// Security
$mil_lang_common['no_access'] = "You do not have sufficient privileges to access this page. Please first check your account then we make your request by email.";
$mil_lang_common['please_connect'] = "You need to login.";
$mil_lang_common['please_connect_link_label'] = "Login now";
$mil_lang_common['access_not_defined'] = "Our apologies. An error has occurred. The administrator of the system is to be advised.";

// ajax
$mil_lang_common['MALFORMED_FORM_ARGUMENTS'] = "ERROR : MALFORMED_FORM_ARGUMENTS";


// Country
$mil_lang_common['country_listing_please_choose'] = "-- Select a country --";
$mil_lang_common['calling_country_listing_please_choose'] = "-- Select a country phone code --";

// Gender
$mil_lang_common['mil_c_gender_listing_please_choose'] = "-- Sex --";



// ######################################################
// Init Functional :
$GLOBALS['config_ini']['functional']['invoice']['our_designation'][1] = "Your company name";
$GLOBALS['config_ini']['functional']['invoice']['our_designation'][2] = "Your company name";

?>
