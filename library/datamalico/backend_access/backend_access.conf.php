<?php
/** 
 * @file
 * CUSTOM FILE: File that you have to customize !!!
 *
 * File where the vertical security is defined (SQL UPDATE, SELECT and WHERE clause), and also where accesses to tables are defined (for SQL INSERT and DELETE).
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 *
 * Best practice syntax for INSERT and DELETE
 * @code
 * $GLOBALS['security']['backend_access']['{insert}'|'{delete}']['{table_name}'] = $accesses;
 * @endcode
 *
 * Best practice syntax for UPDATE and SELECT
 * @code
 * $GLOBALS['security']['backend_access']['{update}'|'{select}']['{table_name}'] = array (
 * 	'{field_name_1}' => $accesses_1;
 * 	, '{field_name_2}' => $accesses_2;
 * 	, ...
 * );
 * @endcode
 * 
 * Each $access value could be something like that (adapted to your own organization chart or security conventions).
 * @code
 * $accesses = array (
 * 		'everybody' => false				// For anybody accessing to the website for example
 * 		, 'website' => array (				// For website users for example
 * 			'user_roles' => array (
 * 				'Customer' => false
 * 				, 'Professional' => false
 * 				, 'INTERNAL_STAFF' => false
 * 			)
 * 			, 'user_id' => array () 		// if the role is false (or absent), then you can access true to specific users, but if this here is false but the role is true, then access is granted.
 * 		)
 * 		, 'admin' => array (			// For the administrator interface for example
 * 			'admin_role' => array (
 * 				'Administrator' => true
 * 				, 'Sales' => true
 * 			)
 * 			, 'admin_user_id' => array ( 		// if the role is false (or absent), then you can access true to specific users, but if this here is false but the role is true, then access is granted.
 * 				1 => false
 * 			)
 * 		)
 * 	)
 * );
 * @endcode
 *
 * Summary about manipulations:
 * - INSERT: 
 * 	- vertical access: at a table level, 'false' per default if absent, or must be specifically defined as 'true'.
 * 	- horizontal access (only with the datamalico_server_ajax class): is allowed anyway if vertical security is 'true'
 * 	- Note that an insert is only the folloying:
 * 		- INSERT INTO `$table_name` () VALUES ();
 * 		- then 'UPDATE' manipulations do the rest.
 * 		- When using prior, datamalico_server_dbquery::select_empty(), then see the following example to check the horizontal security.
 * - DELETE: 
 * 	- vertical access: at a table level, 'false' per default if absent, or must be specifically defined as 'true'.
 * 	- horizontal access: is allowed if vertical security is 'true', denied if 'false'
 * 		- then is allowed if datamalico_server_ajax::set_horizontal_access() is not used
 * 		- but denied if datamalico_server_ajax::set_horizontal_access() without specifying for what selector the access is allowed.
 * 			- Note that in this case, you can add or override conditions (in the WHERE clause)
 * - UPDATE: 
 * 	- vertical access: at a column level, 'false' per default if absent, or must be specifically defined as 'true'.
 * 	- horizontal access (only with the datamalico_server_ajax class): is allowed anyway if vertical security is 'true', denied if 'false'
 * 		- then is allowed if datamalico_server_ajax::set_horizontal_access() is not used
 * 		- but denied if datamalico_server_ajax::set_horizontal_access() without specifying for what selector the access is allowed.
 * 			- Note that in this case, you can add or override conditions (in the WHERE clause)
 * - SELECT: 
 * 	- vertical access: at a column level, 'false' per default if absent, or must be specifically defined as 'true'.
 * 	- horizontal access (not implemented yet)
 * 	- Note: see frontend_access to learn more about frontend access:
 * 		- 'hidden', but style accessible via script
 * 		- 'read', read only access
 * 		- 'write', write access
 *
 * Advise regarding the use of datamalico_server_dbquery::select_empty() (in datamalico_server_dbquery.lib.php) and its ['temp_insert_id']: in order to check the 
 * 	horizontal security, do something like:
 * @code
 * function my_set_horizontal_access ($one_record_selector, $custom_horizontal_access_args)
 * {
 * 	$fn_return = array ();
 *      $table_name = $one_record_selector['table_name'];
 *  
 *      // Horizontal security, precising the record selector:
 *      if ($table_name === "mil_d_user")
 * 	{
 * 		// The following TEMPINSERTID_ is added in order to be abble to 
 * 		// 	insert and make the link between a record entity and its 
 * 		// 	records of one of its join tables:
 * 		$findme = "TEMPINSERTID_"; 
 * 		
 * 		$pos = strpos($one_record_selector['conditions']['user_id'], $findme);
 * 		if ($pos === 0)
 * 			// If the condition value begins by $findme (position 0) then it is a temporary 
 * 			// 	insert id, and this must be inserted and not updated
 * 		{
 * 			$fn_return['horizontal_access'] = true;
 * 		}
 *         }
 *  
 *         return $fn_return;
 * }
 * @endcode
 *
 * @todo Check this:
 * - for any $frontend_access['action']['table_type'] = "join", then the access is granted to everybody
 * - You must also declare access to views if you there are.
 * - For multiselist, sort_index of join table and config table must be available.
 */




/**
 * @warning The core of this function must be writen according to your purposes! Adapted to your own organization chart or security conventions.
 * 	This function is used by the class datamalico_server_dbquery in datamalico_server_dbquery.lib.php
 *
 * Return if the access is given to the current user for an 'horizontal' action: SQL INSERT or DELETE action on a particular database table. 
 *
 * @param params {associative array} Params
 * 	- manipulation: {string} "insert"|"delete"
 * 	- table_name: {string} The table target.
 * 	- runas: (optional, default is $GLOBALS['security']['current_user_keys']) {string}
 * 		- Possible values are: 
 * 			- "CODER". It allows you as coder to bypass the standard security defined in $GLOBALS['security']['backend_access']['insert']
 * 				and $GLOBALS['security']['backend_access']['delete'].
 * 			- Any other value gives you only the normal right you have
 *
 * @return TRUE or FALSE
 *
 * Example of use: (Normally you don't have to use it. This is only used by the class datamalico_server_dbquery in datamalico_server_dbquery.lib.php)
 * @code
 * $can_access_table = can_access_table (
 * 		array (
 * 			'manipulation' => "insert"
 * 			, 'table_name' => $table_name
 * 			)
 * 		);
 * @endcode
 */
function can_access_table ($params)
{
	// ############################
	// Params and config
	$can_access_table = false;
	foreach ($params as $key => $value) {$$key = $value;};	// then $params['myvar'] can be accessed in the code below by $myvar

	if (exists_and_not_empty($params['runas']))
	{
		return true;
	}

	$manipulation_rights = $GLOBALS['security']['backend_access'][$manipulation];

	// ############################
	// init
	if (
		strtolower($params['manipulation']) !== "insert"
		&& strtolower($params['manipulation']) !== "delete"
	)
	{
		return false;
	}

	if (!exists_and_not_empty($table_name))
	{
		return false;
	}


	// #################################################################################
	// #################################################################################
	// #################################################################################
	// #################################################################################
	// #################################################################################
	// Core of the function
	//
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//
	// ATTENTION, in this function we advise you to change only what stands below not above:


	$admin_role = $current_user_keys['admin']['admin_role'];
	$admin_user_id = $current_user_keys['admin']['admin_user_id'];
	$user_roles = $current_user_keys['website']['user_roles'];
	$user_id = $current_user_keys['website']['user_id'];



	// #####################
	// everybody access
	//trace("manipulation_rights[$table_name]['everybody']:".$manipulation_rights[$table_name]['everybody']);
	if ($manipulation_rights[$table_name]['everybody'] === true)
	{
		$can_access_table = true;
		return $can_access_table;
	}


	// #####################
	// website user access
	if (
		gettype($current_user_keys['website']['user_roles']) === "array"
		&& gettype($manipulation_rights[$table_name]['website']['user_roles']) === "array"
	)
	{
		foreach ($current_user_keys['website']['user_roles'] as $user_role)
		{
			foreach ($manipulation_rights[$table_name]['website']['user_roles'] as $authorized_user_role => $true_or_false)
			{
				if ($user_role['role_name'] === $authorized_user_role)
				{
					//trace("user_role['role_name'] === authorized_user_role: ".$user_role['role_name']." === $authorized_user_role");
					if ($true_or_false === true)
					{
						$can_access_table = true;
						return $can_access_table;
						break 2;
					}
				}
			}
		}
	}

	//trace("manipulation_rights[$table_name]['website']['user_id'][$user_id]:".$manipulation_rights[$table_name]['website']['user_id'][$user_id]);
	if ($manipulation_rights[$table_name]['website']['user_id'][$user_id] === true)
	{
		$can_access_table = true;
		return $can_access_table;
	}


	// #####################
	// administrator application user access
	//trace("manipulation_rights[$table_name]['admin']['admin_role'][$admin_role]:".$manipulation_rights[$table_name]['admin']['admin_role'][$admin_role]);
	if ($manipulation_rights[$table_name]['admin']['admin_role'][$admin_role] === true)
	{
		$can_access_table = true;
		return $can_access_table;
	}

	//trace("manipulation_rights[$table_name]['admin']['admin_user_id'][$admin_user_id]:".$manipulation_rights[$table_name]['admin']['admin_user_id'][$admin_user_id]);
	if ($manipulation_rights[$table_name]['admin']['admin_user_id'][$admin_user_id] === true)
	{
		$can_access_table = true;
		return $can_access_table;
	}


	return false;
}

/**
 * @warning The core of this function must be writen according to your purposes! Adapted to your own organization chart or security conventions.
 * 	This function is used by the class datamalico_server_dbquery in datamalico_server_dbquery.lib.php and the class datamalico_server_ajax in datamalico_server_ajax.lib.php
 *
 * Return if access is given to the current user (or a 'run as' user) for a SQL UPDATE or a SELECT (in select clause and/or in where clause) action on a 
 * particular database table field.
 *
 * @warning This function must be adapted to your own organization chart or security conventions.
 *
 * @param params {associative array} Params
 * 	- manipulation: {string} Possible values are:
 * 		- "select" for the select clause of a select query
 * 		- "select_where" for a where clause of a select query
 * 		- "update"
 * 	- field_name: {string} The table target.
 * 	- field_infos:
 * 		- field_direct: array needed by the get_field_structure in datamalico_server_dbquery
 * 			- table:
 * 			- orgtable: // not used
 * 	- runas: (optional, default is $GLOBALS['security']['current_user_keys']) {string}
 * 		- Possible values are: 
 * 			- "CODER". It allows you as coder to bypass the standard vertical security defined in in $GLOBALS['security']['backend_access']['update']
 * 				and $GLOBALS['security']['backend_access']['select'] (for field access for 'UPDATE' and 'SELECT').
 * 			- Any other value gives you only the normal right you have
 *
 * @return TRUE or FALSE
 *
 * Example of use: (Normally you don't have to use it. This is only used by the class datamalico_server_dbquery in datamalico_server_dbquery.lib.php and the class datamalico_server_ajax in datamalico_server_ajax.lib.php)
 * @code
 * $can_vertically_access_field = can_vertically_access_field ( array (
 * 	'manipulation' => "select"
 * 	, "field_name" => $field_name
 * 	, 'field_infos' => $field_infos
 * ));
 * @endcode
 */
function can_vertically_access_field ($params)
{
	// ############################
	// Params and config
	$can_vertically_access_field = false;
	foreach ($params as $key => $value) {$$key = $value;};	// then $params['myvar'] can be accessed in the code below by $myvar

	if (exists_and_not_empty($params['runas']))
	{
		return true;
	}

	$manipulation_rights = $GLOBALS['security']['backend_access'][$manipulation];

	// ############################
	// init
	if (
		strtolower($params['manipulation']) !== "select"
		&& strtolower($params['manipulation']) !== "select_where"
		&& strtolower($params['manipulation']) !== "update"
	)
	{
		return $can_vertically_access_field;
	}

	if (!exists_and_not_empty($field_name))
	{
		return $can_vertically_access_field;
	}

	if (gettype($field_infos) !== "array")
	{
		return $can_vertically_access_field;
	}

	//echo trace2web($field_infos, "field_infos");

	// For multiselist, open right to select the special field "selected_in_multiselist_in_db". This field is for multiselist, so that the client page can receive which element of the multiselist is checked or not. 
	if (
		$manipulation === "select"
		&& $field_name === "selected_in_multiselist_in_db"
	)
	{
		$can_vertically_access_field = true;
		return $can_vertically_access_field;
	}




	// #################################################################################
	// #################################################################################
	// #################################################################################
	// #################################################################################
	// #################################################################################
	// Core of the function
	//
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//
	// ATTENTION, in this function we advise you to change only what stands below not above:

	$current_user_keys = $GLOBALS['security']['current_user_keys'];
	$admin_role = $current_user_keys['admin']['admin_role'];
	$admin_user_id = $current_user_keys['admin']['admin_user_id'];
	$user_roles = $current_user_keys['website']['user_roles'];
	$user_id = $current_user_keys['website']['user_id'];
	//trace("field_name:$field_name");

	//$orig_table_name = $field_infos[$name]['orgtable'
	//$orig_field_name = $field_infos[$name]['orgname'];

	$table_name = $field_infos['field_direct']['table'];
	//$table_name = $field_infos['field_direct']['orgtable'];

	// #####################
	// everybody access
	//trace("manipulation_rights[$table_name][$field_name]['everybody']:".$manipulation_rights[$table_name][$field_name]['everybody']);
	if ($manipulation_rights[$table_name][$field_name]['everybody'] === true)
	{
		$can_vertically_access_field = true;
		return $can_vertically_access_field;
	}


	// #####################
	// website user access
	if (
		gettype($current_user_keys['website']['user_roles']) === "array"
		&& gettype($manipulation_rights[$table_name][$field_name]['website']['user_roles']) === "array"
	)
	{
		foreach ($current_user_keys['website']['user_roles'] as $user_role)
		{
			foreach ($manipulation_rights[$table_name][$field_name]['website']['user_roles'] as $authorized_user_role => $true_or_false)
			{
				if ($user_role['role_name'] === $authorized_user_role)
				{
					//trace("user_role['role_name'] === authorized_user_role: ".$user_role['role_name']." === $authorized_user_role");
					if ($true_or_false === true)
					{
						$can_vertically_access_field = true;
						return $can_vertically_access_field;
						break 2;
					}
				}
			}
		}
	}

	//trace("manipulation_rights[$table_name][$field_name]['website']['user_id'][$user_id]:".$manipulation_rights[$table_name][$field_name]['website']['user_id'][$user_id]);
	if ($manipulation_rights[$table_name][$field_name]['website']['user_id'][$user_id] === true)
	{
		$can_vertically_access_field = true;
		return $can_vertically_access_field;
	}


	// #####################
	// admin application user access
	//trace("manipulation_rights[$table_name][$field_name]['admin']['admin_role'][$admin_role]:".$manipulation_rights[$table_name][$field_name]['admin']['admin_role'][$admin_role]);
	if ($manipulation_rights[$table_name][$field_name]['admin']['admin_role'][$admin_role] === true)
	{
		$can_vertically_access_field = true;
		return $can_vertically_access_field;
	}

	//trace("manipulation_rights[$table_name][$field_name]['admin']['admin_user_id'][$admin_user_id]:".$manipulation_rights[$table_name][$field_name]['admin']['admin_user_id'][$admin_user_id]);
	if ($manipulation_rights[$table_name][$field_name]['admin']['admin_user_id'][$admin_user_id] === true)
	{
		$can_vertically_access_field = true;
		return $can_vertically_access_field;
	}

	return false;
}


// #################################################################################
// #################################################################################
// #################################################################################
// #################################################################################
// #################################################################################
// Configuration of the backend accesses:
//
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//
// Customize it below:




// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_config tables : config tables

// starwars_config_attribute
$GLOBALS['security']['backend_access']['insert']['starwars_config_attribute'] = array ('admin' => array ('admin_role' => array ('Administrator' => true)));
$GLOBALS['security']['backend_access']['delete']['starwars_config_attribute'] = array ('admin' => array ('admin_role' => array ('Administrator' => true)));
$GLOBALS['security']['backend_access']['select']['starwars_config_attribute'] = array (
	'attr_id' => array ('everybody' => true)	// it is important to allow everybody to access such id in order to save their choice in checklists...
	, 'attribute' => array ('everybody' => true)
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'sort_index' => array ('everybody' => true)
); 
$GLOBALS['security']['backend_access']['select_where']['starwars_config_attribute'] = array (
	//'attr_id' => array ('everybody' => true)
	'attribute' => array ('everybody' => true)
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))	// for example, it is better to prevent anybody to make researches on this criteria.
	, 'sort_index' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))	// for example, it is better to prevent anybody to make researches on this criteria.
);
$GLOBALS['security']['backend_access']['update']['starwars_config_attribute'] = array (
	//'attr_id' => array ()		// no need to autorise the update of an identificator.
	'attribute' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'sort_index' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
);

// starwars_config_type
$GLOBALS['security']['backend_access']['insert']['starwars_config_type'] = array ('admin' => array ('admin_role' => array ('Administrator' => true)));
$GLOBALS['security']['backend_access']['delete']['starwars_config_type'] = array ('admin' => array ('admin_role' => array ('Administrator' => true)));
$GLOBALS['security']['backend_access']['select']['starwars_config_type'] = array (
	'type_id' => array ('everybody' => true)	// it is important to allow everybody to access such id in order to save their choice in checklists...
	, 'type_name' => array ('everybody' => true)
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'sort_index' => array ('everybody' => true)
);
$GLOBALS['security']['backend_access']['select_where']['starwars_config_type'] = array (
	//'type_id' => array ('everybody' => true)
	'type_name' => array ('everybody' => true)
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))	// for example, it is better to prevent anybody to make researches on this criteria.
	, 'sort_index' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))	// for example, it is better to prevent anybody to make researches on this criteria.
);
$GLOBALS['security']['backend_access']['update']['starwars_config_type'] = array (
	//'type_id' => array ()		// no need to autorise the update of an identificator.
	'type_name' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'enabled' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'sort_index' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
);


// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_config tables : config tables

// starwars_data_character
$GLOBALS['security']['backend_access']['insert']['starwars_data_character'] = array ('everybody' => true); 
$GLOBALS['security']['backend_access']['delete']['starwars_data_character'] = array ('everybody' => true);
$GLOBALS['security']['backend_access']['select']['starwars_data_character'] = array (
	'char_id' => array ('everybody' => true)
	, 'fullname' => array ('everybody' => true)
	, 'change_date' => array ('everybody' => true)
	, 'owner_ip' => array ('everybody' => true)
	, 'description' => array ('everybody' => true)
	, 'type_id' => array ('everybody' => true)
);
$GLOBALS['security']['backend_access']['select_where']['starwars_data_character'] = array (
	'char_id' => array ('admin' => array ('admin_role' => array ('Administrator' => true)))
	, 'fullname' => array ('everybody' => true)
	, 'change_date' => array ('everybody' => true)
	, 'owner_ip' => array ('everybody' => true)
	, 'description' => array ('everybody' => true)
	, 'type_id' => array ('everybody' => true)
);
$GLOBALS['security']['backend_access']['update']['starwars_data_character'] = array (
	//'char_id' => array ()		// no need to autorise the update of an identificator.
	'fullname' => array ('everybody' => true)
	, 'change_date' => array ()
	, 'owner_ip' => array ('everybody' => true)
	, 'description' => array ('everybody' => true)
);

// starwars_data_character2attribute
$GLOBALS['security']['backend_access']['insert']['starwars_data_character2attribute'] = array ('everybody' => true); 
$GLOBALS['security']['backend_access']['delete']['starwars_data_character2attribute'] = array ('everybody' => true);
$GLOBALS['security']['backend_access']['select']['starwars_data_character2attribute'] = array (
	'char2attr_id' => array ('everybody' => true)
	, 'char_id' => array ('everybody' => true)
	, 'attr_id' => array ('everybody' => true)
);
$GLOBALS['security']['backend_access']['select_where']['starwars_data_character2attribute'] = array (
	'char2attr_id' => array ('everybody' => true)
	, 'char_id' => array ('everybody' => true)
	, 'attr_id' => array ('everybody' => true)
);
$GLOBALS['security']['backend_access']['update']['starwars_data_character2attribute'] = array (
	//'char2attr_id' => array ('everybody' => true) 	// no need to autorise the update of an identificator.
	'char_id' => array ('everybody' => true) 
	, 'attr_id' => array ('everybody' => true)
);

?>
