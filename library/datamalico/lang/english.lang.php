<?php
/** 
 * @file
 * File where the translation elements are stored for datamalico ; it uses the mil_ help library for translation.
 *
 * Here the variable $mil_lang_common will be merged with with the $GLOBALS['mil_lang_common']. See it in the mil_.conf.php file.
 */

// ######################################################
// ERRORS and warnings:

$mil_lang_common['ERROR'] = "An error has occurred . Contact the webmaster for more information.";
$mil_lang_common['webmaster_is_notified'] = "The webmaster has just been notified, and will correct the problem soon. We apologies and invite you to try again later.";

$mil_lang_common['horizontal_access_false'] = "You do not have permission to perform this action.";

$mil_lang_common['hey'] = "Hey!";
$mil_lang_common['field_must_be_filled'] = "Please fill in this field.";


// ######################################################
// General:

$mil_lang_common['please_choose'] = "-- Please choose --";
$mil_lang_common['no'] = "No";
$mil_lang_common['yes'] = "Yes";

// pagination
$mil_lang_common['NO_RESULT_DISPLAYED'] = "No result.";	// No result
$mil_lang_common['1_RESULT_DISPLAYED'] = "result";		// Page 1 of 1 - (1 result)
$mil_lang_common['PAGE_X_OF_N_1'] = "Page"; 			// Page 3 of 15 - (146 results)
$mil_lang_common['PAGE_X_OF_N_2'] = "of";			// Page 3 of 15 - (146 results)	
$mil_lang_common['X_RESULTS_DISPLAYED'] = "results";		// Page 3 of 15 - (146 results)

// search
$mil_lang_common['operator_equals'] = "=&nbsp; (Strictly equal to)";
$mil_lang_common['operator_gt_or_eq'] = ">= (Greater than or equal to)";
$mil_lang_common['operator_lt_or_eq'] = "<= (Less than or equal to)";
$mil_lang_common['operator_gt'] = ">&nbsp; (Greater than)";
$mil_lang_common['operator_lt'] = "<&nbsp; (Less than)";
$mil_lang_common['operator_between'] = "..&nbsp; (Between the values (included)";
$mil_lang_common['operator_containing'] = "*&nbsp; (Contains)";
$mil_lang_common['operator_not_contain'] = "!* (Does not contain)";
$mil_lang_common['operator_different'] = "!= (Strictly different)";
$mil_lang_common['operator_regexp'] = "regex (regular expression)";
$mil_lang_common['operator_notregexp'] = "!regex (regular expression)";
$mil_lang_common['operator_begins'] = "Starts with";
$mil_lang_common['operator_notbegins'] = "Does not begin with";
$mil_lang_common['operator_ends'] = "Ends with";
$mil_lang_common['operator_notends'] = "Does not end with";


// ######################################################
// Database actions

// select
$mil_lang_common['open_link_text'] = "See";

//update
$mil_lang_common['NO_ROW_UPDATED'] = "change is done.";
$mil_lang_common['1_ROW_UPDATED'] = "change is done.";
$mil_lang_common['X_ROWS_UPDATED'] = "changes.";
$mil_lang_common['ERROR_NO_ACCESS_TO_UPDATE'] = "You do not have sufficient rights to modify.";

//insert
$mil_lang_common['NO_INSERT_DONE'] = "No additions have been made in the database.";
$mil_lang_common['INSERT_SUCCESSFULL'] = "Insertion successfull.";
$mil_lang_common['ERROR_ON_INSERT'] = "Insertion error.";
$mil_lang_common['ERROR_ON_INSERT_NO_AUTOINC'] = "Insertion error. Because there is no autoincrement fields in the table:";
$mil_lang_common['ERROR_ON_INSERT_CANT_GET_AUTOINC'] = "Insertion error. Impossible to know the next_id for the table:";
$mil_lang_common['ERROR_ON_INSERT_NEXTID_VS_JUSTINSERTEDID'] = "Insertion error. Values ( next_id vs. just_inserted_id ) are different :";
$mil_lang_common['ERROR_NO_RIGHT_TO_INSERT'] = "You do not have sufficient insertion rights.";

//delete
$mil_lang_common['DELETION_SUCCESSFULL'] = "Delete successful.";
$mil_lang_common['ERROR_NO_RIGHT_TO_DELETE'] = "You do not have sufficient deletion rights.";


// ######################################################
// Database tables

//starwars_config_attribute 
$mil_lang_common['starwars_config_attribute.attribute'] = "Attribute";

// starwars_config_type
$mil_lang_common['starwars_config_type.type_id'] = "Type Id";
$mil_lang_common['starwars_config_type.type_name'] = "Type";

//starwars_data_character 
$mil_lang_common['starwars_data_character.char_id'] = "Character Id";
$mil_lang_common['starwars_data_character.fullname'] = "Fullname of the character";
$mil_lang_common['starwars_data_character.change_date'] = "Last modification";
$mil_lang_common['starwars_data_character.owner_ip'] = "IP of the owner";$mil_lang_common['starwars_data_character.description'] = "Brief description";
$mil_lang_common['starwars_data_character.type_id'] = "Type";

// data validator
$mil_lang_common['starwars_data_character.fullname.solo_error'] = "The Millennium Falcon belongs to Han Solo, not you! (See the DVIS (Data Validator Input on Server side) to see how this message is processed.)";
$mil_lang_common['starwars_data_character.fullname.jabba_error'] = "Nope!, Jabba has already been destroyed. (See the datamalico_server_ajax::input_data_validator_add_custom() to see how this message is processed.)";



?>
