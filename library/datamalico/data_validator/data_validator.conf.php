<?php
/** 
 * @file
 * CUSTOM FILE: File that you have to customize !!!
 *
 * File where you can define input data validators.
 *
 * What are data validators?
 * - When you input data into the DB (insert or update) and when you output data from the DB (select), you can apply checkers and transformers.
 * 	Both are called validators. Thus, for any value you are about to:
 * 	- 'input' (via INSERT or UPDATE) in the database. There are two kinds of input validations:
 * 		- \b DVIS (Data Validator Input on Server side): A server side validation.
 * 			You can define a function for validation rules, and this function can do two things:
 * 			- (checker) Verify the value to be inserted and if this value 
 * 				- if matches the rules: (rules you have defined) then the value can be inserted
 * 				- if does not respect rules: it prevents any INSERT, UPDATE or DELETE action on the database and 
 * 					returns an error to the client side.
 * 			- (transformer) Do an automatic correction, and transform the value to match rules you want to apply to a particular field of a particular table. 
 * 				- Eg: if you want to store, in your database, money values in cents, instead of dollars, euros, pounds... 
 * 					(because, storing an int or bigint generally uses less memory than storing a decimal),
 * 					then you can multiply by 100 any input value.
 * 				- Such a verifiaction doesn't return any error to the client page, contrary to the server side verification bullet.
 * 		- \b DVIC (Data Validator Input on Client side): A client side validation. It allows you to link a javascript chunck 
 * 			you write in order to be executed on a particular event (related to the field) in order to dynamically make changes.
 * 			- You can define these DVIC validators at 3 different places: (the 1st one overrides the 2nd, the 2nd overrides the 3rd)
 * 				- in the $frontend_access param you send to most datamalico_server_dbquery methods like datamalico_server_dbquery::select()...
 * 				- in data_validator.conf.php eg: $GLOBALS['data_validator'][{tablename}][{fieldname}]['input']['client'][{javascript_event}]
 * 				- in frontend_access.conf.php as DVIC property.
 * 			- TIP: Most of the time a field will always have the same behavior. So define this in data_validator.conf.php, which is the most logical place.
 * 	- \b DVO (Data Validator Output): 'output' and display from the database (just after a datamalico_server_dbquery::select() )
 * 		- A server side transformer: If the input data validator applies an automatic correction before the input, then you may want to apply 
 * 			an automatic correction for the ouput.
 * 	- \b DVRC (Data Validator Research Criteria): 'search on' ('research_criteria') on a field of a table of the database.
 * 		So you can validate if a research criteria meets rules you defined and add it or ommit it in a research query created 
 * 		by datamalico_server_ajax::research_build_select_query() . Like for the DVIS, DVRC can do two things:
 * 		- (checker) Verify the value to be inserted and if this value 
 * 				- if matches the rules: (rules you have defined) then the value can be search on
 * 				- if does not respect rules: it prevents any addition to the sql query.
 * 		- (transformer) Do an automatic correction, and transform the value to match rules you want to apply to a particular field of a particular table.
 *
 * Here is the structure of the $GLOBALS['data_validator'] array:
 * - $GLOBALS['data_validator']: configuration structure for data validators. A data validator helps you to validate (checkers and transformers application) or
 * 	invalidate data on input (insert, update), output (select), and also research criteria (where clause of a select).
 * 	- {table_name} (optional) {associative array}
 * 		- {field_name} (optional) {associative array}
 * 			- input: (optional) {associative array} Specify input data validator rules. You can define 2 kinds of input data validations:
 * 				- server: (optional) {string} \b DVIS: Name of the PHP global function to be executed to validate (check and/or transform) the data
 * 					before being inserted or updated into the database.
 * @code
 * // A checker example:
 * // return array (
 * // 		'valid' => false // if false, no action of delupsert on this 
 * // 				 // field or another will be done. 
 * //				 // Moreover, if you use the javascript datamalico::display_errors()
 * // 				 // to catch the result, the error can be displayed
 * // 		, 'checked_value' => $value_to_be_checked	// Can remain the same as the 
 * // 				 // param received: $value_to_be_checked
 * // 		, 'returnMessage' => "The error message you want to return to the user"
 * // );
 * $GLOBALS['data_validator']['mil_d_registered']['zipcode']['input']['server'] = "DVIS_mil_d_registered_zipcode";
 * function DVIS_mil_d_registered_zipcode ($value_to_be_checked)
 * {
 * 	$metadata['valid'] = true;
 * 	$metadata['checked_value'] = $value_to_be_checked;
 * 	$metadata['returnMessage'] = "";
 * 
 * 	if (!exists_and_not_empty($value_to_be_checked))
 * 	{
 * 		$metadata['valid'] = false;
 * 		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['zipcode_must_be_filled'];
 * 	}
 * 
 * 	return $metadata;
 * }
 *
 *
 * // A transformer example:
 * // return array (
 * // 		'valid' => true
 * // 		, 'checked_value' => $transformed_value // A valid, checked and transformed value
 * // 		, 'returnMessage' => ""	// Can remain empty if valid is true, no error message 
 * // 					// should be displayed.
 * // );
 * $GLOBALS['data_validator']['mil_d_registered']['zipcode']['input']['server'] = "DVIS_mil_d_registered_zipcode";
 * function DVIS_mil_d_registered_zipcode ($value_to_be_checked)
 * {
 * 	$metadata['valid'] = true;
 * 	$metadata['checked_value'] = $value_to_be_checked;
 * 	$metadata['returnMessage'] = "";
 * 
 * 	if ($value_to_be_checked === "75000")
 * 	{
 * 		$metadata['checked_value'] = "F-75000";
 * 	}
 * 
 * 	return $metadata;
 * }
 * @endcode
 * 				- client: (optional) {associative array} \b DVIC
 * 					- {javascript event name} (mandatory) {string} PHP string of the javascript chunk for the DVIC function, 
 * 						to be executed when the event occurs on the field.
 * @code
 *
 * // Written as a string script in the php code:
 * $GLOBALS['data_validator']['mil_c_country']['french']['input']['client']['keypress'] = '
 * 	function DVIC_mil_c_country_french_keypress (event) 
 * 	{
 * 		event.stopPropagation();
 * 		alert("keypress");
 * 		console.log ($(this).val());
 * 		return $(this); // keep the chaining capability
 * 	}
 * ';
 *
 * // OR written as an external .js file script:
 * $GLOBALS['data_validator']['mil_c_country']['french']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_mil_c_country_french_keypress.js");
 * // DVIC_mil_c_country_french_keypress.js: This is a javascript function written in a PHP string:
 * function DVIC_mil_c_country_french_keypress (event) 
 * {
 * 	event.stopPropagation();
 * 	alert("keypress");
 * 	console.log ($(this).val());
 * 	return $(this);
 * }
 * @endcode
 * Instead of writting the javascript chunck into a string, we advise you to write it into a JS file and include it via fread() or other fget()...
 * @code
 * file2string ("your_path_to/DVIC/DVIC_tablename_fieldname_keyup.js"); // You can find this custom file2string() into the mil_.lib.php
 * @endcode
 * @warning Note that you should prefer 'keypress' or 'keyup' to 'keydown', or 'focusout' (which are used by the datamalico.lib.js script). 
 * 			- output: (optional) {string} \b DVO Name of the PHP global function to apply to the field, for each row returned, in order to change it.
 * 				- Eg: like we can have a function called "DVIS_command_price" 
 * 					(for input data validator server) and multiplying the price by 100 in order to store cents, instead of dollars, 
 * 					euros or pounds, then you can write in the frontend_access.conf.php (or locally, or elswhere at	a global level), 
 * 					a function called "DVO_command_price" (for output data validator) dividing these cents to dollars, just for the 
 * 					display output.
 * 				- The function you write, must
 * 					- recieve one parameter: the value stored for this field in the database.
 * 					- return the value to be displayed.
 * @code
 * // Example of an output data validator:
 * $GLOBALS['data_validator']['mil_v_demand_status']['remaining_time']['output'] = "DVO_command_price";
 * function DVO_command_price ($stored_value)
 * {
 * 	$value_to_display = $stored_value / 100;
 * 	return $value_to_display;
 * }
 * @endcode
 * 			- research_criteria: (optional) {string} \b DVRC Name of the PHP global function to be executed to validate if the research criteria meets rules 
 * 				of verification. If it meets these rules, then the function returns TRUE, and the criteria can be added to the research.
 * @code
 * // A checker example:
 * // return array (
 * // 		'valid' => false // if false, the field is not added to the research query.
 * // 		, 'checked_value' => $value_to_be_checked	// Can remain the same as the 
 * // 				 // param received: $search_value
 * // 		, 'returnMessage' => "The error message you want to return to the user"
 * // 				 // Not yet implemented. Could be handy to to help the user to 
 * //				 // 	type good criteria.
 * // );
 * $GLOBALS['data_validator']['mil_d_registered']['valid_email']['research_criteria'] = "DVRC_mil_d_demand_country_phone";
 * function DVRC_mil_d_demand_country_phone ($search_value)
 * {
 * 	$metadata = array (
 * 		'valid' => false
 * 		, 'checked_value' => $search_value
 * 		, 'returnMessage' => ""
 * 	);
 * 
 * 	if ((int) $search_value === 0) $metadata['valid'] = false;
 * 	else $metadata['valid'] = true;	
 * 
 * 	return $metadata;
 * }
 *
 * // A transformer example:
 * // return array (
 * // 		'valid' => true	// then the criteria is added to the research query.
 * // 		, 'checked_value' => $search_value	 // A valid and checked value
 * // 		, 'returnMessage' => ""	// Can remain empty if valid is true, no error message.
 * // );
 * $GLOBALS['data_validator']['mil_d_registered']['valid_email']['research_criteria'] = "DVRC_mil_d_demand_country_phone";
 * function DVRC_mil_d_demand_country_phone ($search_value)
 * {
 * 	$metadata = array (
 * 		'valid' => false
 * 		, 'checked_value' => $search_value
 * 		, 'returnMessage' => ""
 * 	);
 * 
 * 	if ((int) $search_value === 0) $metadata['valid'] = false;
 * 	else $metadata['valid'] = true;	
 * 
 * 	return $metadata;
 * }
 * @endcode
 * 
 * 
 *
 *
 *
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 *
 *
 * @warning This data validation is a standard validation for each singular field. But for many reasons, you may want to add custom data validation. 
 * 	For example, if you want to check a group of field: (eg: you check if the password and its confirmation are the same).
 * 	For such a case, you must add a custom error. See the datamalico_server_ajax::input_data_validator_add_custom() method in datamalico_server_ajax.lib.php
 */

$DVIC_store = $_SERVER["DOCUMENT_ROOT"]."/1001_addon/library/datamalico/data_validator/data_validator_input_client_rules";


/**
 * Transform the value into an integer (The easyest and fastest way to store a numerical value into a DB, instead of storing decimals) (See also _100th_2_unit() ).
 * @param $unit: {int|float|string} (mandatory)
 *
 * @return $hundredth {int} Is the hundredth of the initial value given in parameter.
 */
function unit_2_100th ($unit)
{
	$positive = true;
	if ((float) $unit < 0.0) $positive = false;


	$hundredth = $unit;
	if (gettype($hundredth) === "string")
	{
		// Replace space by nothing
		$hundredth = str_replace (" ", "", $hundredth);

		// sscanf() can help
		// number_format() can help
		// money_format() can help

		// French decimal separator to dot computing decimal separator:
		$hundredth = str_replace (",", ".", $hundredth);
	}

	$hundredth = (float) $hundredth;
	$hundredth = abs($hundredth);

	// Fair rounding: 1.014 --> 1.01	1.015 --> 1.02		1.016 --> 1.02
	$hundredth = round($hundredth, 2);
	$hundredth = $hundredth * 100;


	if ($positive === false) $hundredth = -$hundredth;

	return $hundredth;
}

/**
 * Is the reverse function of unit_2_100th()
 * @param $unit: {int} (mandatory) A value in integer which is the hundredth of what is expected as result of the function.
 *
 * @return $hundredth {float} Is the unit with 2 deimals corresponoding to the initial value given in parameter.
 */
function _100th_2_unit ($hundredth)
{
	$unit = $hundredth / 100;

	return (float) $unit;
}


function DVIS_unit_2_100th_transformer ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = unit_2_100th($value_to_be_checked);
	$metadata['returnMessage'] = "";

	return $metadata;
}

function DVO__100th_2_unit_transformer ($stored_value)
{
	return _100th_2_unit($stored_value);
}





/**
 * -4558.68 --> "-4 558,68 EUR" or "-USD 4,558.68" depending on the locale
 */
function f_number_TO_s_local_number ($f_number)
{
	$localeconv = localeconv();
	$s_local_number = number_format($f_number, $localeconv['frac_digits'], $localeconv['decimal_point'], $localeconv['thousands_sep']);
	return $s_local_number;
}
/**
 * Returns a number (float: ok for decimal and big int in php) from a local_number (string) whatever are the thousands separator and decimal point.
 * Thus it works for any locale ad regional settings.
 *
 * @warning Their is only one constraint: the number you send as argument must be in the right format regarding the current locale.
 * 	- Eg: 
 * 		- if the locale is "en_US", then send "-59,847.12"
 * 		- if the locale is "fr_FR", then send "-59 847.12"
 * 	- How to know the locale?
 * 		- See my function getlocale () or do: setlocale(LC_ALL, 0);
 *
 * @param locale_number: {string} (mandatory) This is the string to be transformed to a number.
 * 	- Eg: "-59.847,12" with as thousands separator "," and decimal point "." (en_US, en_GB...)
 *
 * @return $number: {float} (mandatory) The number is returned.
 *
 * Example of call:
 * @code
 * setlocale(LC_ALL, "en_US");
 * $f_number = s_local_number_TO_f_number ("-59,847.12");
 * echo $f_number; // -59847.12
 *
 * setlocale(LC_ALL, "fr_FR");
 * $f_number = s_local_number_TO_f_number ("-59 847.12");
 * echo $f_number; // -59847.12
 * @endocde
 */
function s_local_number_TO_f_number ($locale_number)
{
	$number;

	if (gettype($locale_number) === "string")
	{
		//$current_locale = getlocale();
		$localeconv = localeconv();	// get the current environment locale settings


		// decimal_point to "." computing decimal separator:
		$number = str_replace ($localeconv['decimal_point'], ".", $locale_number);

		// Through away the thousands_sep "," to "":
		$number = str_replace ($localeconv['thousands_sep'], "", $locale_number);

		// any kind of whitespace or invisible separator
		$pattern = '/[\p{L}]/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		// any kind of whitespace or invisible separator
		$pattern = '/[\p{Z}]/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		// clean expression of multi ASCII blank chars:
		$pattern = '/\s{1,}/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		$number = (float) $number;
	}

	return $number;
}

/**
 * -4558.68 --> "-4 558,68 EUR" or "-USD 4,558.68" depending on the locale
 */
function f_money_TO_s_local_money ($f_money)
{
	$s_local_money = money_format('%i', $f_money);
	return $s_local_money;
}

/**
 * -4558.68 --> "-4 558,68" or "-4,558.68" depending on the locale
 */
function f_money_TO_s_local_money_noCurrencyCode ($f_money)
{
	$s_local_money = money_format('%!i', $f_money);
	return $s_local_money;
}

/**
 * -455868 --> "-4 558,68" or "-4,558.68" depending on the locale
 */
function f_money_100th_TO_s_local_money_noCurrencyCode ($f_money_100th)
{
	$f_money = DVO__100th_2_unit_transformer ($f_money_100th);
	$s_local_money = money_format('%!i', $f_money);
	return $s_local_money;
}

/**
 * Returns a number (float: ok for decimal and big int in php) from a local_money (string) whatever are the thousands separator and decimal point.
 * Thus it works for any locale ad regional settings.
 *
 * @warning Their is only one constraint: the money you send as argument must be in the right format regarding the current locale.
 * 	- Eg: 
 * 		- if the locale is "en_US", then send "-USD 4,558.68"
 * 		- if the locale is "fr_FR", then send "-4 558,68 EUR"
 * 	- How to know the locale?
 * 		- See my function getlocale () or do: setlocale(LC_ALL, 0);
 *
 * @param locale_money: {string} (mandatory) This is the string to be transformed to a number.
 * 	- Eg: "-USD 4,558.68" with as thousands separator "," and decimal point "." (en_US, en_GB...)
 *
 * @return $number: {float} (mandatory) The number is returned.
 * 	- Eg: -4558.68
 *
 * Example of call:
 * @code
 * setlocale(LC_ALL, "en_US");
 * $f_money = s_local_money_TO_f_money ("-USD 4,558.68");
 * echo $f_money; // -4558.68
 *
 * setlocale(LC_ALL, "fr_FR");
 * $f_money = s_local_money_TO_f_money ("-4 558,68 EUR");
 * echo $f_money; // -4558.68
 * @endocde
 */
function s_local_money_TO_f_money ($locale_money)
{
	$number;

	if (gettype($locale_money) === "string")
	{
		//$current_locale = getlocale();
		$localeconv = localeconv();	// get the current environment locale settings


		// decimal_point to "." computing decimal separator:
		$number = str_replace ($localeconv['mon_decimal_point'], ".", $locale_money);

		// Through away the thousands_sep "," to "":
		$number = str_replace ($localeconv['mon_thousands_sep'], "", $locale_money);

		// any kind of whitespace or invisible separator
		$pattern = '/[\p{L}]/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		// any kind of whitespace or invisible separator
		$pattern = '/[\p{Z}]/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		// clean expression of multi ASCII blank chars:
		$pattern = '/\s{1,}/i';
		$replacement = '';
		$number = preg_replace($pattern, $replacement, $number);

		$number = (float) $number;
	}

	return $number;
}

/**
 * Invalidate the research param (means that the param will not be part of the condition) if the value is set to 0 (that is to say not set, eg: if a research menu is set to "-- Please choose a value --")
 */
function DVRC_invalid_if_zero ($search_value)
{
	$metadata = array (
		'valid' => false
		, 'checked_value' => $search_value
		, 'returnMessage' => ""
	);

	if ((int) $search_value === 0) $metadata['valid'] = false;
	else $metadata['valid'] = true;	

	return $metadata;

	//$authorize_search_value = false;
	//if ((int) $search_value === 0) $authorize_search_value = false;
	//else $authorize_search_value = true;
	//return $authorize_search_value;
}

/**
 * Invalidate the research param (means that the param will not be part of the condition) if the value is set to the string "none"
 */
function DVRC_invalid_if_none ($search_value)
{
	$metadata = array (
		'valid' => false
		, 'checked_value' => $search_value
		, 'returnMessage' => ""
	);

	if (strtolower($search_value) === 'none') $metadata['valid'] = false;
	else $metadata['valid'] = true;	

	return $metadata;

	//$authorize_search_value = false;
	//if (strtolower($search_value) === 'none') $authorize_search_value = false;
	//else $authorize_search_value = true;
	//return $authorize_search_value;
}

/**
 * Can be necessary in order to override values (for research forms)
 */
function DVO_return_empty ($stored_value)
{
	return "";
}

/**
 * Can be necessary in order to override values (for research forms)
 */
function DVO_return_null ($stored_value)
{
	return null;
}

/**
 * Can be necessary in order to override values (for research forms)
 */
function DVIS_DVRC_date_null ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (empty ($value_to_be_checked))
	{
		$metadata['checked_value'] = "NULL";
	}

	return $metadata;
}




// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// starwars_data tables : data tables
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

// starwars_data_character
$GLOBALS['data_validator']['starwars_data_character']['fullname']['input']['server'] = "DVIS_starwars_data_character_fullname";
function DVIS_starwars_data_character_fullname ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	$findme = 'solo';
	$found = stripos($value_to_be_checked, $findme);
	if ($found !== false)
	{
		$metadata['valid'] = false;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['starwars_data_character.fullname.solo_error'];
	}

	return $metadata;
}
$GLOBALS['data_validator']['starwars_data_character']['fullname']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_starwars_data_character_fullname.js");
$GLOBALS['data_validator']['starwars_data_character']['type_id']['research_criteria'] = "DVRC_invalid_if_zero";


// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Registered entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################


// mil_d_registered
$GLOBALS['data_validator']['mil_d_registered']['preload_username']['input']['server'] = "DVIS_mil_d_registered_preload_username";
function DVIS_mil_d_registered_preload_username ($value_to_be_checked)
{
	check_email ($value_to_be_checked);

	return $metadata;
}
$GLOBALS['data_validator']['mil_d_registered']['zipcode']['input']['server'] = "DVIS_mil_d_registered_zipcode";
function DVIS_mil_d_registered_zipcode ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['zipcode_must_be_filled'];
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_registered']['country_id']['input']['server'] = "DVIS_mil_d_registered_country_id";
function DVIS_mil_d_registered_country_id ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['country_id_must_be_filled'];
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_registered']['url']['input']['server'] = "DVIS_mil_d_registered_url";
function DVIS_mil_d_registered_url ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	$string = strtolower($value_to_be_checked);
	$pattern = '/^http:\/\//';
	$replacement = '';
	$metadata['checked_value'] = preg_replace($pattern, $replacement, $string);

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_registered']['valid_email']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['enabled']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['gender']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['country_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['country_phone']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['country_mobile']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_registered']['open_contact']['research_criteria'] = "DVRC_invalid_if_zero";

$GLOBALS['data_validator']['mil_d_registered']['reg_id']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_strict.js");
$GLOBALS['data_validator']['mil_d_registered']['firstname']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_registered_firstname_keyup.js");
$GLOBALS['data_validator']['mil_d_registered']['lastname']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_registered_lastname_keyup.js");

$GLOBALS['data_validator']['mil_d_registered']['country_id']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_registered_country_id_change.js");
$GLOBALS['data_validator']['mil_d_registered']['country_phone']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_registered_country_phone_change.js");
$GLOBALS['data_validator']['mil_d_registered']['country_mobile']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_registered_country_mobile_change.js");

$GLOBALS['data_validator']['mil_d_registered']['companynum']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_registered_companynum_keyup.js");
$GLOBALS['data_validator']['mil_d_registered']['phone']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_registered_phone_keyup.js");
$GLOBALS['data_validator']['mil_d_registered']['mobile']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_registered_mobile_keyup.js");



// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Demand entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

// mil_d_demand
$GLOBALS['data_validator']['mil_d_demand']['role_target']['input']['server'] = "DVIS_mil_d_demand_role_target";
function DVIS_mil_d_demand_role_target ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (
		!exists_and_not_empty($value_to_be_checked) 
		|| (int) $value_to_be_checked === 0 
		|| $value_to_be_checked === null
		|| strtolower( (string) $value_to_be_checked ) === "null"
	)
	{
		$metadata['valid'] = false;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['role_target_must_be_filled'];
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand']['zipcode']['input']['server'] = "DVIS_mil_d_demand_zipcode";
function DVIS_mil_d_demand_zipcode ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['zipcode_must_be_filled'];
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand']['country_id']['input']['server'] = "DVIS_mil_d_demand_country_id";
function DVIS_mil_d_demand_country_id ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['country_id_must_be_filled'];
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand']['publishedDate']['input']['server'] = "DVIS_DVRC_date_null";

$GLOBALS['data_validator']['mil_d_demand']['role_target']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_demand_role_target_change.js");

$GLOBALS['data_validator']['mil_d_demand']['country_id']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_demand_country_id_change.js");
$GLOBALS['data_validator']['mil_d_demand']['country_phone']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_demand_country_phone_change.js");
$GLOBALS['data_validator']['mil_d_demand']['country_mobile']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_demand_country_mobile_change.js");

$GLOBALS['data_validator']['mil_d_demand']['phone']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_demand_phone_keyup.js");
$GLOBALS['data_validator']['mil_d_demand']['mobile']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_demand_mobile_keyup.js");

$GLOBALS['data_validator']['mil_d_demand']['area_archi']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_strict.js"); 
$GLOBALS['data_validator']['mil_d_demand']['area_garden']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_strict.js");

$GLOBALS['data_validator']['mil_d_demand']['demand_id']['research_criteria'] = "DVRC_mil_d_demand_demand_id";
function DVRC_mil_d_demand_demand_id ($search_value)
{
	$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
	include_once $doc_root."/1001_addon/assets/snippets/demand/demand.lib.php";

	$clear_demand_identifiers = jammed_to_clear_demand_id ($search_value);

	$metadata = array (
		'valid' => true
		, 'checked_value' => $clear_demand_identifiers['demand_id']
		, 'returnMessage' => ""
	);

	//echo trace2web ($clear_demand_identifiers, "clear_demand_identifiers");
	//echo trace2web ($metadata, "metadata");

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand']['owner_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['country_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['country_phone']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['country_mobile']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['clientWantsToPublish']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['role_target']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['ET_cost_per_area_archi']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['ET_cost_per_area_garden']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_demand']['other']['research_criteria'] = "DVRC_invalid_if_zero";



$GLOBALS['data_validator']['mil_v_demand_status']['main_status']['research_criteria'] = "DVRC_invalid_if_none";
$GLOBALS['data_validator']['mil_v_demand_status']['publication_status_real_time']['research_criteria'] = "DVRC_invalid_if_none";
$GLOBALS['data_validator']['mil_v_demand_status']['concurrence_status']['research_criteria'] = "DVRC_invalid_if_none";
$GLOBALS['data_validator']['mil_v_demand_status']['signing_status']['research_criteria'] = "DVRC_invalid_if_none";
$GLOBALS['data_validator']['mil_v_demand_status']['clientWantsToPublish']['research_criteria'] = "DVRC_invalid_if_zero";



$GLOBALS['data_validator']['mil_v_demand_status']['remaining_time']['output'] = "DVO_mil_v_demand_status_remaining_time";
function DVO_mil_v_demand_status_remaining_time ($stored_value)
{
	$value_to_display;
	if ($GLOBALS['config_ini']['region']['lang'] === "french")
	{
		$value_to_display = str_replace("d", "j", $stored_value);
	}

	return $value_to_display;
}




// mil_d_demand_2_service
$GLOBALS['data_validator']['mil_d_demand_2_service']['service_quantity_100th']['input']['server'] = "DVIS_mil_d_demand_2_service_service_quantity_100th";
function DVIS_mil_d_demand_2_service_service_quantity_100th ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['quantity_must_be_filled'];
	}
	else
	{
		$metadata['valid'] = true;
		$metadata['checked_value'] = unit_2_100th($value_to_be_checked);
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand_2_service']['service_quality_id']['input']['server'] = "DVIS_mil_d_demand_2_service_service_quality_id";
function DVIS_mil_d_demand_2_service_service_quality_id ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['quality_must_be_filled'];
	}

	return $metadata;
}

//$GLOBALS['data_validator']['mil_d_demand_2_service']['demand_id']['input']['client']['change'] = file2string ("$DVIC_store/DVIC_mil_d_demand_2_service_demand_id_change.js");

$GLOBALS['data_validator']['mil_d_demand_2_service']['service_quantity_100th']['output'] = "DVO_mil_d_demand_2_service_service_quantity_100th";
function DVO_mil_d_demand_2_service_service_quantity_100th ($stored_value)
{
	$value_to_display = _100th_2_unit ($stored_value);
	if ((float) $value_to_display === 0.0) $value_to_display = null;
	return $value_to_display;
}

$GLOBALS['data_validator']['mil_d_demand_2_service']['service_quantity_100th']['research_criteria'] = "DVRC_mil_d_demand_2_service_service_quantity_100th_research_criteria";
function DVRC_mil_d_demand_2_service_service_quantity_100th_research_criteria ($search_value)
{
	$metadata = array (
		'valid' => false
		, 'checked_value' => $search_value
		, 'returnMessage' => ""
	);

	if (!exists_and_not_empty($search_value)) $metadata['valid'] = false;
	else
	{
		$metadata['valid'] = true;
		$metadata['checked_value'] = $search_value * 100;
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand_2_service']['service_quality_id']['research_criteria'] = "DVRC_mil_d_demand_2_service_service_quality_id_research_criteria";
function DVRC_mil_d_demand_2_service_service_quality_id_research_criteria ($search_value)
{
	$metadata = array (
		'valid' => false
		, 'checked_value' => $search_value
		, 'returnMessage' => ""
	);

	if ((int) $search_value === 0) $metadata['valid'] = false;
	else $metadata['valid'] = true; 

	return $metadata;
}




//mil_d_demand_2_service_type
$GLOBALS['data_validator']['mil_d_demand_2_service_type']['myCommForContact_ET_cents']['input']['server'] = "DVIS_mil_d_demand_2_service_type_myCommForContact_ET_cents";
function DVIS_mil_d_demand_2_service_type_myCommForContact_ET_cents ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['myCommForContact_ET_cents_must_be_filled'];
	}
	else
	{
		$metadata['valid'] = true;
		$metadata['checked_value'] = unit_2_100th($value_to_be_checked);
	}

	return $metadata;
}
$GLOBALS['data_validator']['mil_d_demand_2_service_type']['myCommForSigning_ET_cents']['input']['server'] = "DVIS_mil_d_demand_2_service_type_myCommForSigning_ET_cents";
function DVIS_mil_d_demand_2_service_type_myCommForSigning_ET_cents ($value_to_be_checked)
{
	$metadata['valid'] = true;
	$metadata['checked_value'] = $value_to_be_checked;
	$metadata['returnMessage'] = "";

	// checker-blocker
	if (!exists_and_not_empty($value_to_be_checked))
	{
		$metadata['valid'] = false;
		$metadata['checked_value'] = $value_to_be_checked;
		$metadata['returnMessage'] = $GLOBALS['mil_lang_common']['myCommForSigning_ET_cents_must_be_filled'];
	}

	// transformer
	else
	{
		$metadata['valid'] = true;
		$metadata['checked_value'] = unit_2_100th($value_to_be_checked);
	}

	return $metadata;
}

$GLOBALS['data_validator']['mil_d_demand_2_service_type']['ET_supposedServicePrice']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_demand_2_service_type']['myCommForContact_ET_cents']['output'] = "DVO_mil_d_demand_2_service_type_myCommForContact_ET_cents";
function DVO_mil_d_demand_2_service_type_myCommForContact_ET_cents ($stored_value)
{
	if ((int) $stored_value === 0) return null;

	$value_to_display = _100th_2_unit ($stored_value);
	return $value_to_display;
}

$GLOBALS['data_validator']['mil_d_demand_2_service_type']['myCommForSigning_ET_cents']['output'] = "DVO_mil_d_demand_2_service_type_myCommForSigning_ET_cents";
function DVO_mil_d_demand_2_service_type_myCommForSigning_ET_cents ($stored_value)
{
	if ((int) $stored_value === 0) return null;

	$value_to_display = _100th_2_unit ($stored_value);
	return $value_to_display;
}




// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Offer entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

$GLOBALS['data_validator']['mil_d_offer_2_service_type']['ET_servicePrice']['output'] = "DVO_mil_d_offer_2_service_type_ET_servicePrice";
function DVO_mil_d_offer_2_service_type_ET_servicePrice ($stored_value)
{
	$value_to_display;

	if ($stored_value === NULL)
		$value_to_display = $GLOBALS['mil_lang_common']['mil_d_offer_2_service_type.ET_servicePrice.notset_yet'];

	return $value_to_display;
}

$GLOBALS['data_validator']['mil_d_offer_2_service_type']['service_type_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_offer_2_service_type']['consultantWantsToPublish']['research_criteria'] = "DVRC_invalid_if_zero";


// Example of an output data validator:
$GLOBALS['data_validator']['mil_v_offer_status']['offer_status_real_time']['output'] = "DVO_mil_v_offer_status_offer_status_real_time";
function DVO_mil_v_offer_status_offer_status_real_time ($stored_value)
{
	$value_to_display;
	if ($stored_value === "is_not_published") $value_to_display = $GLOBALS['mil_lang_common']['mil_v_offer_status.is_not_published'];
	else if ($stored_value === "is_currently_published") $value_to_display = $GLOBALS['mil_lang_common']['mil_v_offer_status.is_currently_published'];
	else if ($stored_value === "is_signed") $value_to_display = $GLOBALS['mil_lang_common']['mil_v_offer_status.is_signed'];

	return $value_to_display;
}

// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Basket and Invoice entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################


// mil_d_basket
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_VAT_cents']['input']['server'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ET']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_VAT_cents']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_VAT_cents']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket']['owner_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_basket']['basket_status_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket']['TOTAL_VAT_cents']['research_criteria'] = "DVIS_unit_2_100th_transformer";


// mil_d_basket_invoice
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['input']['server'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['research_criteria'] = "DVIS_unit_2_100th_transformer";


// mil_d_basket_invoice
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['input']['server'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice']['TOTAL_VAT_cents']['research_criteria'] = "DVIS_unit_2_100th_transformer";



// mil_d_basket_item
$GLOBALS['data_validator']['mil_d_basket_item']['item_nb_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['VAT_rate_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['item_validity']['input']['server'] = "DVIS_DVRC_date_null";

$GLOBALS['data_validator']['mil_d_basket_item']['item_nb_100th']['input']['client']['keyup'] = file2string ("$DVIC_store/DVIC_mil_d_basket_item_item_nb_100th.js");
$GLOBALS['data_validator']['mil_d_basket_item']['VAT_rate_100th']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ET']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket_item']['item_nb_100th']['output'] = "DVO__100th_2_unit_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['VAT_rate_100th']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket_item']['item_nb_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['VAT_rate_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_item']['item_unit_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket_item']['item_type_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_basket_item']['currency_id']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_basket_item']['owner_can_change_nb']['research_criteria'] = "DVRC_invalid_if_zero";
$GLOBALS['data_validator']['mil_d_basket_item']['item_validity']['research_criteria'] = "DVIS_DVRC_date_null";


// mil_d_basket_invoice_item
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_nb_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['VAT_rate_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_nb_100th']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['VAT_rate_100th']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ET']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_nb_100th']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['VAT_rate_100th']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_nb_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['VAT_rate_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_invoice_item']['item_unit_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";


// mil_d_basket_abandoned_item
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_nb_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['VAT_rate_100th']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ET']['input']['server'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ATI']['input']['server'] = "DVIS_unit_2_100th_transformer";

$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_nb_100th']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['VAT_rate_100th']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ET']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ATI']['input']['client']['keypress'] = file2string ("$DVIC_store/DVIC_generic_only_digits_plus.js");

$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_nb_100th']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['VAT_rate_100th']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ET']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ATI']['output'] = "f_money_100th_TO_s_local_money_noCurrencyCode";

$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_nb_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['VAT_rate_100th']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ET']['research_criteria'] = "DVIS_unit_2_100th_transformer";
$GLOBALS['data_validator']['mil_d_basket_abandoned_item']['item_unit_price_cents_ATI']['research_criteria'] = "DVIS_unit_2_100th_transformer";



?>
