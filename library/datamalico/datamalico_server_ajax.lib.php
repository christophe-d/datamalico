<?php
/** 
 * @file
 * File where the datamalico ajax library is defined.
 *
 * You must make the difference between, this (here) library, and the datamalico API library, which makes the interface with the database itself.
 * The present file manages ajax handling.
 * 
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 *
 * For more information about this file, see the class itself datamalico_server_ajax.
 */

/**
 * @par What is the class datamalico_server_ajax?
 * 	- With datamalico_server_ajax a client HTML webpage can send instructions to a target server page (in PHP) in order to execute SQL actions:
 * 		- INSERT
 * 		- UPDATE
 * 		- DELETE
 * 		- Research using SELECT... WHERE...
 * 	- datamalico_server_ajax manages ajax interactions between a client webpage (HTML, Javascript, jQuery) and a responding server page (PHP).
 * 		This manages:
 * 		- reception of the data sent by the client page (See __construct() )
 * 		- actions to be done on the server:
 * 			- security management: set_horizontal_access() (and its param my_set_horizontal_access (See FAKE_my_set_horizontal_access_FAKE().)
 * 				- For more information about security, have a look on backend_access.conf.php and search on the word 'security'.
 * 			- data validation: input_data_validator(), input_data_validator_add_custom()
 * 			- user notification, if necessary, on the client page managed by are_there_invalid_data() (See also datamalico_client::display_errors() 
 * 				in the datamalico.lib.js)
 * 			- effective SQL INSERT, UPDATE, DELETE via delupsert()
 * 		- research using research_get_form_structure(), research_multiselist_get_form_structure(), research_build_select_query().
 *
 * @par How to send data (and pilot SQL INSERT, UPDATE, DELETE) from the client page to the server page?
 * 	- Input format: You can send data from a HTML form. Here is how you can specify data to INSERT, UPDATE or DELETE:
 * @code
 * <div id="html_ctnr_lastname">
 * 	<input type="hidden" name="delupsert[html_ctnr_lastname][t]" value="registered"><!-- [t] for table name (FROM clause) -->
 * 	<input type="text" name="delupsert[html_ctnr_lastname][f][firstname]" value="James"><!-- [f] for fields to be updated or inserted -->
 * 	<input type="text" name="delupsert[html_ctnr_lastname][f][lastname]" value="Bond"><!-- [f] for fields to be updated or inserted -->
 * 	<input type="hidden" name="delupsert[html_ctnr_lastname][c][reg_id]" value="1"><!-- [c] for conditions (WHERE clause) -->
 * 	<input type="hidden" name="delupsert[html_ctnr_lastname][c][other_cond]" value="foo"><!-- [c] for conditions (WHERE clause) -->
 * </div>
 * @endcode
 * You can also send the same parameters from in ajax data you send to the server (then your javascript code will be):
 * @code
 * data: {
 * 	delupsert: {
 * 		firstname: {
 * 			t: "registered"		// table name
 * 			, f: {			// list of fields
 * 				firstname: "James"
 * 				, lastname: "Bond"
 * 			}
 * 			, c: {			// list of conditions
 * 				reg_id: "1"
 * 				, other_cond: "foo"
 * 			}
 * 		}
 * 	}
 * }
 * @endcode
 *
 *
 * Then on the server side, you just have to do the following: (see also the page TEMPLATES_CODE/mil_page/server.delupsert.ajax.php)
 * @code
 *
 * // Datamalico ajax object creation:
 * $dco_ajax = new datamalico_server_ajax ( array (
 *         'page_params' => array  ($_GET, $_POST) // gets all params that the page can receive.
 * ));
 *
 *
 * // ############################
 * // Front-end validation: Checks via ajax if the validity of data is ok, or returns an error message to the front-end interface:
 * $dco_ajax->input_data_validator ();     //echo trace2web($dco_ajax->input['delupsert_list'], "input_data_validator");
 * $dco_ajax->input_data_validator_add_custom (get_custom_data_validation($dco_ajax, $this_mil_page));
 *
 *
 * // ############################
 * // Back-end validation: Checks if, in the precise context, the user can insert or update a data into a field, depending on your own custom function:
 * $dco_ajax->set_horizontal_access ( array (
 * 	'custom_horizontal_access_function' => "my_set_horizontal_access"
 *      , 'custom_horizontal_access_args' => array (
 *      	'this_mil_page' => $this_mil_page
 * 	)
 * ));             //echo trace2web($dco_ajax->input['delupsert_list'], "set_horizontal_access");
 *
 *
 * // ############################
 * // Gives a feedback if data are valid for on both sides front-end and back-end.
 * if ($dco_ajax->are_there_invalid_data () === false) $isok=true;	// ok
 *
 * // Real insertion or update or deletion:
 * $dco_ajax->delupsert();
 * $this_mil_page->output = $dco_ajax->output;
 * @endcode
 *
 * @todo This class is to be continued.
 */
class datamalico_server_ajax
{
	/**
	 * input: {associative array} This is actually data that the server page recieved trough _GET and _POST --> ajax input
	 * 	- select: To prepare a SELECT statement.
	 * 		- pagination: (optional) {associative array}
	 * 		- select: (optional, if empty the built query will be with *) {numerical array} Fields which must be set in the SELECT clause of the select query.
	 * 		- from: (mandatory) {numerical array} Tables which must be set in the FROM clause of the select query. If this parameter is 
	 * 			not specifyed or well formed, then the request fails.
	 * 			- About join tables: This is not mandatory to write join tables (in the GLOBALS['relationship'][{table_1}]['many_to_many'][{table_2}]
	 * 			- About table priority: You must remind that there could be priorities in writting tables in this array. Eg: Suppose that we would 
	 * 				have to write join tables (even if this is not the case), you must know that in the FROM clause of a SQL query, 
	 * 				you cannot mention the table_2 before mentionning the join table.
	 * 		- where: (optional) {associative array} Criteria which must be set in the WHERE clause of the select query.
	 * 	- delupsert_list: {associative array} This is the list of data to be 'delupserted' (that is to say, deleted, inserted or updated).
	 * 		This delupsert_list is going to change depending on methods executions.
	 *
	 * Here is what the $this->input looks like:
	 * @code
	 * $this->input => array(
	 * 	'select' => array (
	 * 		'pagination' => array (
	 *			['page' => "{value}"]
	 *			[, 'perpage' => "{value}"]
	 * 		)
	 * 		, 'select' => array (
	 * 			"registered.id"
	 * 			, "registered.firstname"
	 * 			, "registered.lastname"
	 * 		)
	 * 		, 'from' => array (
	 * 			"registered"
	 * 			, "demand"
	 * 		)
	 * 		, 'where' => array (
	 * 			'demand.credate' => array (
	 * 				'op' => ">"		// greater than
	 * 				, 'val' => "20120924"
	 * 			)
	 * 			, 'demand.comments' => array (
	 * 				'op' =>			// no operator; then will use like operator or = if double quotes are written
	 * 				, 'val' => "bathroom"
	 * 			)
	 * 		)
	 * 	)
	 * 	, 'delupsert_list' => array (
	 * 		'delete' => array ()
	 * 		, 'insert' => array ()
	 * 		, 'update' => array (
	 * 			'{html_container_id}' => array (
	 * 				'data_itself' => array (
	 * 					'table_name' => '{table_name}'
	 * 					, 'fields' => array (
	 * 						'field_1' => "{value}"
	 * 						[, 'field_2' => "{value}"]
	 * 					)
	 * 					, 'conditions' => array (
	 * 						'cond_1' => "{value}"
	 * 						[, 'cond_2' => "{value}"]
	 * 					)
	 * 				)
	 * 				, 'metadata' => array (
	 * 					'ctnr' => '{html_container_id}'
	 * 					, 'horizontal_access' => true|false	// see datamalico_server_ajax::set_horizontal_access()
	 * 					, 'valid' => true|false			// see datamalico_server_ajax::input_data_validator() or datamalico_server_ajax::set_horizontal_access()
	 * 					, 'checked_value' => "{value returned by the input_data_validator, can be valid or invalid}"
	 * 					, 'returnMessage' => "Any message"	// see datamalico_server_ajax::input_data_validator() or datamalico_server_ajax::set_horizontal_access()
	 * 				)
	 * 			)
	 * 		)
	 * 		, 'custom_data_validation' => array()	// see datamalico_server_ajax::input_data_validator_add_custom()
	 * 	)
	 * );
	 * @endcode
	 */
	public $input = array(
		'select' => array (
			'pagination' => array ()
		)
		, 'delupsert_list' => array ()
	);

	/**
	 * This is report on the datamalico_server_ajax object. Most of the time, this parameter is the json result returned to the client page.
	 *
	 * The structure pattern if datamalico_server_ajax::are_there_invalid_data() is TRUE:
	 * - $this->output
	 * 	- metadata
	 * 		- returnCode:
	 * 			- 'THERE_ARE_INVALID_DATA' means that there is at least one either horizontal_access=FALSE or valid=FALSE
	 *	- custom_data_validation
	 *		- {html_ctnr_1}
	 *			- metadata
	 *				- valid: {boolean}
	 *	- TEMPINSERTID_insertion: {associative array}
	 *	- select
	 *	- delete
	 *	- update
	 *		- {html_ctnr_1}
	 *			- data_itself: {associative array}
	 *				- table_name: {string}
	 *				- fields: {associative array}
	 *					- field_name_1: {mixed} Value to be inserted
	 *					- field_name_n: {mixed} Value to be inserted
	 *					- ...
	 *				- conditions: {associative array}
	 *					- condition_field_name_1: {mixed} Value of the condition
	 *					- condition_ield_name_n: {mixed} Value of the condition
	 *					- ...
	 *			- metadata: {associative array}
	 *				- ctnr: {string} Is the HTML id of the element where the value takes place.
	 *				- horizontal_access: {boolean} Is it horizontally accessible?
	 *				- valid: {boolean} Is it valid according to the data_validator?
	 *				- checked_value: {mixed} Is the value returned by the data_validator.
	 *				- returnMessage: {string} In case of horizontal_access=FALSE or valid=FALSE, the message to be displayed 
	 *					to the end-user.
	 *		- {html_ctnr_n}
	 *		- ...
	 *	- insert
	 *		- {html_ctnr_1}
	 *			- data_itself: {associative array}
	 *				- table_name: {string}
	 *				- fields: {associative array}
	 *					- field_name_1: {mixed} Value to be inserted
	 *					- field_name_n: {mixed} Value to be inserted
	 *					- ...
	 *			- metadata: {associative array}
	 *				- ctnr: {string} Is the HTML id of the element where the value takes place.
	 *				- horizontal_access: {boolean} Is it horizontally accessible?
	 *				- valid: {boolean} Is it valid according to the data_validator?
	 *				- checked_value: {mixed} Is the value returned by the data_validator.
	 *				- returnMessage: {string} In case of horizontal_access=FALSE or valid=FALSE, the message to be displayed 
	 *					to the end-user.
	 *		- {html_ctnr_n}
	 *		- ...
	 *
	 * The structure pattern if datamalico_server_ajax::are_there_invalid_data() is FALSE:
	 * - $this->output
	 * 	- metadata
	 * 		- returnCode:
	 * 			- 'API_HAS_BEEN_CALLED' means that the datamalico_server_dbquery::upsert(), or datamalico_server_dbquery::delete()
	 * 				has been called and that there were no prior THERE_ARE_INVALID_DATA.
	 *	- custom_data_validation
	 *		- {html_ctnr_1}
	 *			- metadata
	 *				- valid: {boolean}
	 *	- TEMPINSERTID_insertion: {associative array} (optional) Is the result of the insertion resulting relying on a form generated by 
	 *		datamalico_server_dbquery::select_empty() (having a temp_insert_id as criteria) (See its temp_insert_id parameter).
	 *		This array is different of the below $this->output['insert'] array, insert is the result of insert action not generated with 
	 *		datamalico_server_dbquery::select_empty(), having no criteria at all.
	 *		- [0]: {int} (mandatory)
	 *			- insert_api: {associative array} (mandatory) See insert_api()
	 *	- select
	 *	- delete: {associative array} See the datamalico_server_dbquery::delete()
	 *		- {html_ctnr_1}
	 *			- delete_api: {associative array} (mandatory) See dco_delete_api()
	 *	- update: {associative array} See the datamalico_server_dbquery::upsert()
	 *		- {html_ctnr_1}
	 *			- update_api: {associative array} (mandatory) See update_api()
	 *		- {html_ctnr_n}
	 *		- ...
	 *	- insert: {associative array} See the datamalico_server_dbquery::upsert(). This array is the result of insert action not generated with 
	 *		having no criteria at all, whereas data inserted via a form generated by datamalico_server_dbquery::select_empty() having a temp_insert_id as criteria.
	 *		- {html_ctnr_1}
	 *			- insert_api: {associative array} (mandatory) See insert_api()
	 *		- {html_ctnr_n}
	 *		- ...
	 *
	 *
	 * Here is what the $this->output looks like:
	 * @code
	 * $this->output => Array
	 *         (
	 *             [metadata] => Array
	 *                 (
	 *                 	[returnCode] => THERE_ARE_INVALID_DATA
	 *                 )
	 * 
	 *             [error_list] => Array
	 *                 (
	 *                 )
	 * 
	 *             [TEMPINSERTID_insertion] => Array
	 *                 (
	 *                 )
	 * 
	 *             [select] => Array
	 *                 (
	 *                 )
	 * 
	 *             [delete] => Array
	 *                 (
	 *                 )
	 * 
	 *             [update] => Array
	 *                 (
	 *                 )
	 * 
	 *             [insert] => Array
	 *                 (
	 *                 )
	 * 
	 *         )
	 * @endcode
	 * 
	 * Another real example:
	 * @code
	 * $this->output: Array
	 * (
	 *     [metadata] => Array
	 *         (
	 *             [returnCode] => API_HAS_BEEN_CALLED
	 *         )
	 * 
	 *     [TEMPINSERTID_insertion] => Array
	 *         (
	 *         )
	 * 
	 *     [insert] => Array
	 *         (
	 *             [14_demand_id_20130104173605332] => Array
	 *                 (
	 *                     [insert_api] => Array	// Remind that an insert is a 'blank' insert 
	 *                     				// 	A blank insert is: INSERT INTO `$table_name` () VALUES ()
	 *                     				// This blank insert is followed by one or several updates.
	 *                     				// Thus, there are [metadata] followed by [update_api]
	 *                         (
	 *                             [metadata] => Array
	 *                                 (
	 *                                     [returnCode] => INSERT_SUCCESSFULL
	 *                                     [returnMessage] => 
	 *                                     [insert_id] => 254
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand_2_service
	 *                                             [fields] => Array
	 *                                                 (
	 *                                                     [demand_id] => 174
	 *                                                     [service_id] => 12
	 *                                                 )
	 * 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 593
	 *                                             [time] => 2013-01-04CET17:50:44
	 *                                             [runas] => 
	 *                                         )
	 * 
	 *                                     [sql_query] => 
	 * 			INSERT INTO `data_demand_2_service` () VALUES ();
	 * 		
	 *                                     [affected_rows] => 1
	 *                                 )
	 * 
	 *                             [update_api] => Array
	 *                                 (
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand_2_service
	 *                                             [fields] => Array
	 *                                                 (
	 *                                                     [demand_id] => 174
	 *                                                     [service_id] => 12
	 *                                                 )
	 * 
	 *                                             [conditions] => Array
	 *                                                 (
	 *                                                     [demand_service_id] => 254
	 *                                                 )
	 * 
	 *                                             [runas] => 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 593
	 *                                         )
	 * 
	 *                                     [0] => Array
	 *                                         (
	 *                                             [metadata] => Array
	 *                                                 (
	 *                                                     [returnCode] => 1_ROW_UPDATED
	 *                                                     [returnMessage] => 1 changement effectué.
	 *                                                     [sql_query] => 
	 * 			UPDATE `data_demand_2_service`
	 * 			SET `demand_id` = 174
	 * 			WHERE (
	 * `demand_service_id` = '254'
	 * )
	 * 			;
	 * 		
	 *                                                     [affected_rows] => 1
	 *                                                     [value_just_inserted] => 174
	 *                                                 )
	 * 
	 *                                             [params] => Array
	 *                                                 (
	 *                                                     [table_name] => data_demand_2_service
	 *                                                     [field_name] => demand_id
	 *                                                     [field_new_value] => 174
	 *                                                     [full_string_AND_condition] => (
	 * `demand_service_id` = '254'
	 * )
	 *                                                     [runas] => 
	 *                                                     [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                                     [calling_LINE] => 593
	 *                                                 )
	 * 
	 *                                         )
	 * 
	 *                                     [1] => Array
	 *                                         (
	 *                                             [metadata] => Array
	 *                                                 (
	 *                                                     [returnCode] => 1_ROW_UPDATED
	 *                                                     [returnMessage] => 1 changement effectué.
	 *                                                     [sql_query] => 
	 * 			UPDATE `data_demand_2_service`
	 * 			SET `service_id` = 12
	 * 			WHERE (
	 * `demand_service_id` = '254'
	 * )
	 * 			;
	 * 		
	 *                                                     [affected_rows] => 1
	 *                                                     [value_just_inserted] => 12
	 *                                                 )
	 * 
	 *                                             [params] => Array
	 *                                                 (
	 *                                                     [table_name] => data_demand_2_service
	 *                                                     [field_name] => service_id
	 *                                                     [field_new_value] => 12
	 *                                                     [full_string_AND_condition] => (
	 * `demand_service_id` = '254'
	 * )
	 *                                                     [runas] => 
	 *                                                     [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                                     [calling_LINE] => 593
	 *                                                 )
	 * 
	 *                                         )
	 * 
	 *                                 )
	 * 
	 *                         )
	 * 
	 *                 )
	 * 
	 *         )
	 * 
	 *     [update] => Array
	 *         (
	 *             [zipcode] => Array
	 *                 (
	 *                     [update_api] => Array
	 *                         (
	 *                             [params] => Array
	 *                                 (
	 *                                     [table_name] => data_demand
	 *                                     [fields] => Array
	 *                                         (
	 *                                             [zipcode] => 57070
	 *                                         )
	 * 
	 *                                     [conditions] => Array
	 *                                         (
	 *                                             [demand_id] => 174
	 *                                             [owner_id] => 151
	 *                                         )
	 * 
	 *                                     [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                     [calling_LINE] => 613
	 *                                     [time] => 2013-01-04CET17:50:44
	 *                                     [runas] => 
	 *                                 )
	 * 
	 *                             [0] => Array
	 *                                 (
	 *                                     [metadata] => Array
	 *                                         (
	 *                                             [returnCode] => NO_ROW_UPDATED
	 *                                             [returnMessage] => 0 changement effectué.
	 *                                             [sql_query] => 
	 * 			UPDATE `data_demand`
	 * 			SET `zipcode` = '57070'
	 * 			WHERE (
	 * `demand_id` = '174'
	 * AND `owner_id` = '151'
	 * )
	 * 			;
	 * 		
	 *                                             [affected_rows] => 0
	 *                                             [value_just_inserted] => 57070
	 *                                         )
	 * 
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand
	 *                                             [field_name] => zipcode
	 *                                             [field_new_value] => 57070
	 *                                             [full_string_AND_condition] => (
	 * `demand_id` = '174'
	 * AND `owner_id` = '151'
	 * )
	 *                                             [runas] => 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 613
	 *                                         )
	 * 
	 *                                 )
	 * 
	 *                         )
	 * 
	 *                 )
	 * 
	 *             [area_garden] => Array
	 *                 (
	 *                     [update_api] => Array
	 *                         (
	 *                             [params] => Array
	 *                                 (
	 *                                     [table_name] => data_demand
	 *                                     [fields] => Array
	 *                                         (
	 *                                             [area_garden] => 10
	 *                                         )
	 * 
	 *                                     [conditions] => Array
	 *                                         (
	 *                                             [demand_id] => 174
	 *                                             [owner_id] => 151
	 *                                         )
	 * 
	 *                                     [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                     [calling_LINE] => 613
	 *                                     [time] => 2013-01-04CET17:50:44
	 *                                     [runas] => 
	 *                                 )
	 * 
	 *                             [0] => Array
	 *                                 (
	 *                                     [metadata] => Array
	 *                                         (
	 *                                             [returnCode] => 1_ROW_UPDATED
	 *                                             [returnMessage] => 1 changement effectué.
	 *                                             [sql_query] => 
	 * 			UPDATE `data_demand`
	 * 			SET `area_garden` = 10
	 * 			WHERE (
	 * `demand_id` = '174'
	 * AND `owner_id` = '151'
	 * )
	 * 			;
	 * 		
	 *                                             [affected_rows] => 1
	 *                                             [value_just_inserted] => 10
	 *                                         )
	 * 
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand
	 *                                             [field_name] => area_garden
	 *                                             [field_new_value] => 10
	 *                                             [full_string_AND_condition] => (
	 * `demand_id` = '174'
	 * AND `owner_id` = '151'
	 * )
	 *                                             [runas] => 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 613
	 *                                         )
	 * 
	 *                                 )
	 * 
	 *                         )
	 * 
	 *                 )
	 * 
	 *             [10_demand_id_20130104173605232] => Array
	 *                 (
	 *                     [update_api] => Array
	 *                         (
	 *                             [params] => Array
	 *                                 (
	 *                                     [table_name] => data_demand_2_service
	 *                                     [fields] => Array
	 *                                         (
	 *                                             [demand_id] => 174
	 *                                             [service_id] => 9
	 *                                         )
	 * 
	 *                                     [conditions] => Array
	 *                                         (
	 *                                             [demand_id] => 174
	 *                                             [service_id] => 9
	 *                                         )
	 * 
	 *                                     [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                     [calling_LINE] => 613
	 *                                     [time] => 2013-01-04CET17:50:44
	 *                                     [runas] => 
	 *                                 )
	 * 
	 *                             [0] => Array
	 *                                 (
	 *                                     [metadata] => Array
	 *                                         (
	 *                                             [returnCode] => NO_ROW_UPDATED
	 *                                             [returnMessage] => 0 changement effectué.
	 *                                             [sql_query] => 
	 * 			UPDATE `data_demand_2_service`
	 * 			SET `demand_id` = 174
	 * 			WHERE (
	 * `demand_id` = '174'
	 * AND `service_id` = '9'
	 * )
	 * 			;
	 * 		
	 *                                             [affected_rows] => 0
	 *                                             [value_just_inserted] => 174
	 *                                         )
	 * 
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand_2_service
	 *                                             [field_name] => demand_id
	 *                                             [field_new_value] => 174
	 *                                             [full_string_AND_condition] => (
	 * `demand_id` = '174'
	 * AND `service_id` = '9'
	 * )
	 *                                             [runas] => 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 613
	 *                                         )
	 * 
	 *                                 )
	 * 
	 *                             [1] => Array
	 *                                 (
	 *                                     [metadata] => Array
	 *                                         (
	 *                                             [returnCode] => NO_ROW_UPDATED
	 *                                             [returnMessage] => 0 changement effectué.
	 *                                             [sql_query] => 
	 * 			UPDATE `data_demand_2_service`
	 * 			SET `service_id` = 9
	 * 			WHERE (
	 * `demand_id` = '174'
	 * AND `service_id` = '9'
	 * )
	 * 			;
	 * 		
	 *                                             [affected_rows] => 0
	 *                                             [value_just_inserted] => 9
	 *                                         )
	 * 
	 *                                     [params] => Array
	 *                                         (
	 *                                             [table_name] => data_demand_2_service
	 *                                             [field_name] => service_id
	 *                                             [field_new_value] => 9
	 *                                             [full_string_AND_condition] => (
	 * `demand_id` = '174'
	 * AND `service_id` = '9'
	 * )
	 *                                             [runas] => 
	 *                                             [calling_FILE] => /homepages/23/d400325672/htdocs/www/decorons/02_dev/1001_addon/library/datamalico/datamalico_server_ajax.lib.php
	 *                                             [calling_LINE] => 613
	 *                                         )
	 * 
	 *                                 )
	 * 
	 *                         )
	 * 
	 *                 )
	 * 
	 *         )
	 * 
	 * )
	 * @endcode
	 */
	public $output = array(
		'metadata' => array ()
		, 'error_list' => array ()
		, 'TEMPINSERTID_insertion' => array ()
		, 'select' => array ()
		, 'delete' => array ()
		, 'update' => array ()
		, 'insert' => array ()
	);

	public $timing = array ();

	/**
	 * - page_params: {associative array} Parameters the page has received via GET or POST. See the get_data_from_GET_POST() method to see how this property is built.
	 *
	 * See also the __constructor()
	 *
	 * Example of the property:
	 * @code
	 * page_params :Array
	 * (
	 *     [firstname] => Array
	 *         (
	 *             [t] => data_registered
	 *             [f] => Array
	 *                 (
	 *                     [firstname] => Christophe
	 *                 )
	 * 
	 *             [cdn] => Array
	 *                 (
	 *                     [reg_id] => TEMPINSERTID_201208141806500054
	 *                 )
	 *
	 *             [ctnr] => firstname_sub
	 *         )
	 * )
	 * @endcode
	 */
	public $page_params = array ();

	/**
	 * Create an object 'datamalico_server_ajax'.
	 *
	 * call the function like this:
	 * @code
	 * get_data_from_GET_POST(_GET, _POST); // _GET is first, because _GET must win upon _POST.
	 * @endcode
	 *
	 * @param $params: (optional) {associative array} Params for the object configuration:
	 * 	- page_params: (optional) {numerical array with only 2 elements} See also page_params. This object takes data through _GET and _POST.
	 * 		When, sending params, you can overwrite values of _POST using _GET or overwrite values of _GET using _POST. If you want to 
	 * 		overwrite _POST using _GET, then set: 'page_params' => array  ($_GET, $_POST)
	 *
	 * Example of call:
	 * @code
	 * $dco_ajax = new datamalico_server_ajax (array (
	 * 	'page_params' => array  ($_GET, $_POST)
	 * ));
	 * @endcode
	 */
	function __construct ($params)
	{
		//trace2file ("", "", __FILE__, true);
		//echo trace2web ("datamalico_server_ajax::__construct()");
		$this->timing = array (
			'begin' => ''	// look for debug_chronometer () in mil_.lib.php
			, 'laps' => ''
			, 'end' =>  ''
		);

		$this->get_config__constructor ($params);	//echo trace2web($this, "after get_config__constructor()");

		// $this->page_params['delupsert'] becomes $this->input['delupsert_list']
		if (exists_and_not_empty($this->page_params['delupsert']))
		{
			$this->input['delupsert_list'] = $this->page_params['delupsert'];
			unset ($this->page_params['delupsert']); // free memory
			$this->formdata_2_logical_data_list ();		//echo trace2web($this->input['delupsert_list'], "formdata_2_logical_data_list ()");
			$this->split_as_delete_insert_update ();	//echo trace2web($this->input['delupsert_list'], "split_as_delete_insert_update ()");
		}

		// $this->page_params['select'] becomes $this->input['select']
		if (exists_and_not_empty($this->page_params['select']))
		{
			$this->input['select'] = $this->page_params['select'];
			unset ($this->page_params['select']); // free memory
		}
		//echo trace2web($this, "At the end of the " . __CLASS__ . " constructor");		
	}

	private function get_config__constructor ($params)
	{
		if (exists_and_not_empty($params['page_params']))
		{
			$_GET_or_POST_priority_1 = $params['page_params'][0];
			$_GET_or_POST_priority_2 = $params['page_params'][1];
			//$this->page_params['delupsert'] = $this->get_data_from_GET_POST ($_GET_or_POST_priority_1, $_GET_or_POST_priority_2);
			$this->page_params = $this->get_data_from_GET_POST ($_GET_or_POST_priority_1, $_GET_or_POST_priority_2);
			//echo trace2web($this->page_params['delupsert'], "this->page_params['delupsert']");
		}
	}

	function __destruct ()
	{
		//trace ("Destruction of an object " . __CLASS__);
	}

	/*private function get_data_from_GET_POST ($_GET_or_POST_priority_1, $_GET_or_POST_priority_2)
	{
		return get_data_from_GET_POST ($_GET_or_POST_priority_1, $_GET_or_POST_priority_2);
	}*/

	/**
	 * Returns the fusion of params _POST and _GET. When, sending params, you can overwrite values of _POST using _GET or overwrite values of _GET using _POST.
	 * If you want to overwrite _POST using _GET, then call the function like this:
	 * @code
	 * $this->page_params = $this->get_data_from_GET_POST(_GET, _POST); // _GET is first, because _GET must win upon _POST.
	 * @endcode
	 *
	 * @param $_GET_or_POST_priority_1: {associative array} You set this to $_GET or $_POST. If this $_GET_or_POST_priority_1['delupsert'] is set, then the function returns 
	 * 	$_GET_or_POST_priority_2['delupsert'].
	 * @param $_GET_or_POST_priority_2: {associative array} You set this to $_GET or $_POST. If $_GET_or_POST_priority_1['delupsert'] is not set, then $_GET_or_POST_priority_2['delupsert'] 
	 * 	is returned.
	 *
	 * @return $page_params {associative array} Merge of $_GET_or_POST_priority_1 overwritting $_GET_or_POST_priority_2. Morevoer, this result is mixed_stripslashes()
	 */
	private function get_data_from_GET_POST ($_GET_or_POST_priority_1, $_GET_or_POST_priority_2)
	{
		//$page_params = array_merge((array) $_GET_or_POST_priority_2['delupsert'], (array) $_GET_or_POST_priority_1['delupsert']);
		$page_params = array_merge((array) $_GET_or_POST_priority_2, (array) $_GET_or_POST_priority_1);
		$page_params = mixed_stripslashes ($page_params);
		return $page_params;
	}

	private function formdata_2_logical_data_list ()
	{
		$this->input['delupsert_list'] = formdata_2_logical_data_list ($this->input['delupsert_list']);
	}

	private function split_as_delete_insert_update ()
	{
		$this->input['delupsert_list'] = split_as_delete_insert_update ($this->input['delupsert_list']);
	}

	private function add_custom_data_validation_error ($params)
	{
		$html_container = $params['html_container'];

		if (!exists_and_not_empty($params['metadata']['valid'])) $params['metadata']['valid'] = false;
		$this->input['delupsert_list']['custom_data_validation'][$html_container]['metadata'] = $params['metadata'];
	}

	/**
	 * This method executes a DVIS (Data Validator Input on Server side) on the values you are about to INSERT or UPDATE. (See data_validator.conf.php for details)
	 *
	 * It relies on the $GLOBALS['data_validator']['{table_name}']['{field_name}']['input']['server'] you have defined in data_validator.conf.php
	 *
	 * @return No return value, but in case of invalidation, the datamalico_server_ajax object gets following properties: (given by DVIS - Data Validator, Input Server - see data_validator.conf.php)
	 * @code
	 * $dco_ajax->input['delupsert_list']['insert'|'update'][{fieldname}]['metadata']['valid'] = false;
	 * $dco_ajax->input['delupsert_list']['insert'|'update'][{fieldname}]['metadata']['returnMessage'] = "Any invalidation message";
	 * @endcode
	 */
	public function input_data_validator ()
	{
		$this->input['delupsert_list'] = input_data_validator ($this->input['delupsert_list']);
	}

	/**
	 * You can add custom errors as DVIS (Data Validator Input on Server side), but it doesn't rely on $GLOBALS['data_validator']['{table_name}']['{field_name}']['input']['server']
	 * 	that you have defined in data_validator.conf.php, but on a custom code that you write in your ajax server page.
	 *
	 * The advantage of this custom data validator, 
	 * 	is that you can create errors in checking not only one feild defined as $GLOBALS['data_validator']['{table_name}']['{field_name}']['input']['server'] but in checking 
	 * 	many variables.
	 *
	 * Such an error is like an error of the input_data_validator() error, and will prevent any datamalico_server_ajax::delupsert() execution.
	 *
	 * On the client side page (getting the response of the server side page), the error message will be displayed in the 'html_container' associated, provided the javascript callback of the 
	 * 	ajax request invoke the display_errors() on the client datamalico javascript.
	 *
	 * @param array_of_errors: {numerical array} An array containing errors you want to return to the client page.
	 * 	- An element of this numerical array is:
	 * 		- {associative array} One error.
	 * 			- html_container: {string} Id of the html container where to display the error.
	 * 			- metadata: {associative array} Metadata (same array as the one returned for any standard server data validation. See data_validator.conf.php)
	 * 				- valid: (optional, default is false) {boolean} Must be false so that the server side script understands that there is an error and stops
	 * 					before the UPDATE or DELETE execution.
	 * 				- returnMessage: {string} The message you want to display to the user on the client side page.
	 *
	 * @return No return value, but in case of invalidation, the datamalico_server_ajax object gets following properties: (given by DVIS - Data Validator, Input Server - see data_validator.conf.php)
	 * @code
	 * $dco_ajax->input['custom_data_validation'][{paramname}]['metadata']['valid'] = false;
	 * $dco_ajax->input['custom_data_validation'][{paramname}]['metadata']['returnMessage'] = "Any invalidation message";
	 * @endcode
	 *
	 * @note In your custom function, how to access data sent by the client page to the server page?
	 * @code
	 * $your_datamalico_server_ajax_object->input['delupsert_list']['delete'|'insert'|'update'][{html_container}]['data_itself']['fields'][{fieldname}] // datamalico standard way
	 * //ex:
	 * $firstname = $dco_ajax->input['delupsert_list']['update']['firstname_ctnr']['data_itself']['fields']['firstname'];
	 * @endcode
	 * See also the datamalico_server_ajax::$input property.
	 *
	 * @note Or if you use the mil_ library and the mil_page class, you can also use:
	 * @code
	 * $this_mil_page->page_params['delupsert'][{html_container}]['f'][{field_name}];
	 * $this_mil_page->page_params['any_other_param_name_you_sent'];
	 * @endcode
	 * 
	 *
	 * @warning The html_container must be a HTML container containing only the input field, or a single error message.
	 * 	Otherwise, if the html_container is a container containing some other input field, or other error messages, these other messages
	 * 	can be removed during the Javascript datamalico::display_errors() process.
	 * 
	 * Example of use:
	 * @code
	 * $dco_ajax->input_data_validator ();
	 * $array_of_errors[0] = array (
	 * 	'html_container' => "one_phone_at_least"
	 * 	, 'metadata' => array (
	 * 		'valid' => false
	 * 		, 'returnMessage' => "Please specify at least your phone or mobile number."
	 * 	)
	 * );
	 * $array_of_errors[1] = array (
	 * 	'html_container' => "pass_and_confirm"
	 * 	, 'metadata' => array (
	 * 		'valid' => false
	 * 		, 'returnMessage' => "Your password and its confirmation are different. Please correct it."
	 * 	)
	 * );
	 * $dco_ajax->input_data_validator_add_custom (array_of_errors);
	 * $dco_ajax->delupsert();
	 * @endcode
	 *
	 * @warning
	 * If you have an HTML custom field dedicated to an error, and different of the HTML container of a data (using the datamalico::display()
	 * 	or datamalico::display_datagrid() methods ), 
	 * 	then this HTML custom field, must be refreshed after a previous datamalico::display_error() call. So, even without error, you must
	 * 	return information for this field, but with a valid = true
	 * @code
	 * $dco_ajax->input_data_validator ();
	 * $array_of_errors[0] = array (
	 * 	'html_container' => "one_phone_at_least"
	 * 	, 'metadata' => array (
	 * 		'valid' => true
	 * 	)
	 * );
	 * $array_of_errors[1] = array (
	 * 	'html_container' => "pass_and_confirm"
	 * 	, 'metadata' => array (
	 * 		'valid' => true
	 * 	)
	 * );
	 * $dco_ajax->input_data_validator_add_custom (array_of_errors);
	 * $dco_ajax->delupsert();
	 * @endcode
	 *
	 * Another complex example:
	 * @code
	 * $dco_ajax->input_data_validator ();	//echo trace2web($dco_ajax->input['delupsert_list'], "input_data_validator");
	 * $dco_ajax->input_data_validator_add_custom (get_custom_data_validation($dco_ajax, $this_mil_page));
	 * 
	 * function get_custom_data_validation (&$dco_ajax, $this_mil_page)
	 * {
	 * 	$array_of_errors = array ();
	 * 
	 * 	// services
	 * 	$array_of_errors[] = check_services ($dco_ajax, $this_mil_page);
	 * 
	 * 	//zipcode check_zipcode_according_to_country
	 * 	$array_of_errors[] = check_zipcode_according_to_country ($dco_ajax, $this_mil_page);
	 * 
	 * 	//captcha: captcha is a field containing a captcha. Veriword is the MODx class generating an image with a captcha in it. (captcha is also called formcode in modx)
	 * 	$array_of_errors[] = check_captcha($this_mil_page->page_params['user']['captcha']);
	 * 
	 * 	return $array_of_errors;
	 * }
	 * 
	 * function check_captcha ($captcha)
	 * {
	 * 	$one_error = array (
	 * 		'html_container' => "captcha"
	 * 		, 'metadata' => array (
	 * 			'valid' => true
	 * 		)
	 * 	);
	 * 
	 * 	if (exists_and_not_empty($captcha))
	 * 	{
	 * 		if ($captcha !== $_SESSION["veriword"])
	 * 		{
	 * 			$one_error['metadata']['valid'] = false;
	 * 			$one_error['metadata']['returnMessage'] = $GLOBALS['lang_common']['captcha_bad'];
	 * 			return $one_error;
	 * 		}
	 * 		else
	 * 		{
	 * 			$one_error['metadata']['valid'] = true; //GOOD_CAPTCHA
	 * 		}
	 * 	}
	 * 	else
	 * 	{
	 * 		$one_error['metadata']['valid'] = false;
	 * 		$one_error['metadata']['returnMessage'] = $GLOBALS['lang_common']['captcha_absent'];
	 * 		return $one_error;
	 * 	}
	 * 
	 * 	return $one_error;
	 * }
	 * @endcode			
	 */
	public function input_data_validator_add_custom ($array_of_errors)
	{
		foreach ($array_of_errors as $html_container => $params)
		{
			$this->add_custom_data_validation_error ($params);
		}
	}

	/**
	 * This method sets if (true|false) and how (adds a WHERE clause) a record can be touched.
	 *
	 * This function allows you to manage horizontal access on actions which impact rows: 'DELETE' and 'UPDATE'.
	 * This function impacts $this->input['delupsert_list']['delete'] and $this->input['delupsert_list']['update'].
	 *
	 * @param $params (mandatory) {associative array} Parameters you send to this method. If the parameter is absent, the die() instruction is immediatelly executed.
	 * 	- custom_horizontal_access_function: (mandatory) {string} This is the name of the local custom function that manages your own horizontal security on
	 * 		'DELETE' and 'UPDATE' manipulations.
	 * 		If the parameter is absent, the die() instruction is immediatelly executed.
	 * 	- custom_horizontal_access_args: (optional) {associative array} Array containing parameters you want to send to your custom function.
	 *
	 * @warning See also the FAKE_my_set_horizontal_access_FAKE() method to learn more about how to weite your own custom horizontal security function.
	 */
	public function set_horizontal_access ($params)
	{
		if (!exists_and_not_empty ($params))
		{
			new mil_Exception (
				__FUNCTION__ . " : Big lake of security (Intercepted) in the datamalico_server_ajax::set_horizontal_access() : No parameter \$params given."
				, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
			die();	// immediate stopping of the script, because, if there is no parameters for this function, this could be a big lake of security.
		}
		if (!exists_and_not_empty ($params['custom_horizontal_access_function']))
		{
			new mil_Exception (
				__FUNCTION__ . " : Big lake of security (Intercepted) in the datamalico_server_ajax::set_horizontal_access() : No parameter \$params['custom_horizontal_access_function'] given."
				, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
			die();	// immediate stopping of the script, because, if there is no parameters for this function, this could be a big lake of security.
		}

		$custom_horizontal_access_function = $params['custom_horizontal_access_function'];
		$custom_horizontal_access_args = $params['custom_horizontal_access_args'];
		if (!exists_and_not_empty ($custom_horizontal_access_args)) $custom_horizontal_access_args = array ();


		//$this->input['delupsert_list'] = set_horizontal_access ($this->input['delupsert_list'], $custom_horizontal_access_function);

		//$delete = array ();
		//$insert = array ();
		//$update = array ();

		//echo trace2web($this->input['delupsert_list'], "this->input['delupsert_list']");

		foreach ($this->input['delupsert_list'] as $manipulation => $data_list)
		{
			if ($manipulation !== "insert")
			{
				//echo trace2web ($custom_horizontal_access_args, "custom_horizontal_access_args");
				if (exists_and_not_empty ($this->input['delupsert_list'][$manipulation]))
					// eg, if no update is required, no need to get_CHA_per_manipulation
				{
					//trace($manipulation);
					//$$manipulation = get_CHA_per_manipulation ($data_list, $custom_horizontal_access_function);
					//function get_CHA_per_manipulation ($data_list, $custom_horizontal_access_function)	

					//echo trace2web($this->input['delupsert_list'][$manipulation], "this->input['delupsert_list'][$manipulation]");
					$record_selectors = get_record_selectors ($this->input['delupsert_list'][$manipulation]);
					//echo trace2web($record_selectors, "record_selectors after get_record_selectors()");

					// ######################################################
					// check if this user has an horizontal access for this table and condition
					foreach ($record_selectors as $i => $one_record_selector)
					{
						if (exists_and_not_empty ($record_selectors[$i]['conditions']))
						{
							// If is a delete in a join table, then, can be false



							// ########### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! ################
							// ########### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! ################
							// ###########          HERE IS THE CALL TO my_set_horizontal_access ()          ################
							// ########### SEE THE DOC OF FAKE_my_set_horizontal_access_FAKE () IN THIS CLASS################
							// ########### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! ################
							// ########### !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! ################
							$record_selectors[$i]['custom_horizontal_access'] = $custom_horizontal_access_function ($record_selectors[$i], $custom_horizontal_access_args);



							if (!exists_and_not_empty($record_selectors[$i]['custom_horizontal_access']['horizontal_access']))
							{
								$record_selectors[$i]['custom_horizontal_access']['horizontal_access'] = false;
							}
						}


						// if no conditions, then an insert is required, so there is no horizontal_access to check,
						// 	then it is true (and only the vertical_access will be checked by the datamalico_server_dbquery class).
						else
						{
							$record_selectors[$i]['custom_horizontal_access'] = true;
						}
					}
					//echo trace2web($record_selectors, "record_selectors");
					//echo trace2web($this->input['delupsert_list'], "delupsert_list");

					// ######################################################
					// Write the horizontal_access into the $this->input['delupsert_list'][$manipulation]
					foreach ($record_selectors as $i => $one_record_selector)
					{
						foreach ($record_selectors[$i]['ctnr_ids'] as $j => $ctnr_id)
						{
							if ($record_selectors[$i]['custom_horizontal_access']['horizontal_access'] === true)
							{
								$this->input['delupsert_list'][$manipulation][$ctnr_id]['metadata']['horizontal_access'] = $record_selectors[$i]['custom_horizontal_access']['horizontal_access'];
								if (exists_and_not_empty ($record_selectors[$i]['custom_horizontal_access']['conditions']))
								{
									foreach ($record_selectors[$i]['custom_horizontal_access']['conditions'] as $cond_name => $cond_value)
									{
										//trace("$ctnr_id, $cond_name");
										//trace($this->input['delupsert_list'][$manipulation][$ctnr_id]['data_itself']['conditions'][$cond_name]);
										//trace($record_selectors[$i]['custom_horizontal_access']['conditions'][$cond_name]);

										$this->input['delupsert_list'][$manipulation][$ctnr_id]['data_itself']['conditions'][$cond_name] = $record_selectors[$i]['custom_horizontal_access']['conditions'][$cond_name];
									}
								}
							}
							else
							{
								$this->input['delupsert_list'][$manipulation][$ctnr_id]['metadata']['horizontal_access'] = $record_selectors[$i]['custom_horizontal_access']['horizontal_access'];
								$this->input['delupsert_list'][$manipulation][$ctnr_id]['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['horizontal_access_false'];
							}
						}
					}
				}
			}
		}

		//echo trace2web($this->input['delupsert_list'], "delupsert_list");
	}


	/**
	 * Return true if there are invalid data. This method check in the $this->input['delupsert_list'] property if there is at least one:
	 * 	- $this->input['delupsert_list']['input'|'update'|'custom_data_validation'|][{html_container}]['metadata']['valid'] === false;
	 * 	- or $this->input['delupsert_list']['input'|'update'|'custom_data_validation'|][{html_container}]['metadata']['horizontal_access'] === false;
	 *
	 * See also the display_errors() method of the datamalico_client client in the datamalico.lib.js.
	 *
	 * @return {boolean} If there is at least one ['metadata']['valid'] === false or one ['metadata']['horizontal_access'] === false, 
	 * 	then it returns true, or false otherwise.
	 */
	public function are_there_invalid_data ()
	{
		return are_there_invalid_data ($this->input['delupsert_list']);
	}

	/**
	 * This method is one of the most important method of this class. This really ease DELETE, UPDATE and INSERT manipulations.
	 * Please, read the class description to undertand better what is a delupsert.
	 *
	 * This method really performs a delupsert.
	 * This method relies on the property datamalico_server_ajax::$input['delupsert_list']
	 *
	 * @params $params
	 * 	- runas: (optional) See your own functions:
	 * 		- can_access_table() in backend_access.conf.php
	 * 		- can_vertically_access_field() in backend_access.conf.php
	 *
	 * @return No return value, but the datamalico_server_ajax::output property is filled with the result of the the delupsert.
	 *
	 * @todo Complete theses explanations:
	 * 	- For updates, the condition must be a fixed value (id) or the last field (of the array config['fields'])
	 * 		- In other words, if you change first, a value, that is taken later as condition, then the condition is obsolete.
	 * 		- eg: Pour data_registered_2_profession il est impossible de changer en mìme temps reg_id et profession_id car le 
	 * 		premier update va se faire, mais pas le deuxiÿme. Parce que les conditions renvoyées par la page de saisie sont : 
	 * 		reg_id = 'old_val_1' AND profession_id = 'old_val_2' mais aprÿs le premier update, l'une de ces deux valeures a changé, 
	 * 		et donc le deuxiÿmÿe update fait avec cette condition ne fonctionnera pas.
	 */
	public function delupsert ($params = array ())
	{
		//echo trace2web ("datamalico_server_ajax::delupsert()");

		//$this->input_data_validator ();	//echo trace2web($this->input['delupsert_list'], "input_data_validator");

		// ######################################################
		// Let function_return the format to be understood by the js and displaying errors
		$are_there_invalid_data = $this->are_there_invalid_data (); //$are_there_invalid_data = are_there_invalid_data ($delupsert_list);


		$ajaxReturn;
		if ($are_there_invalid_data === true)
		{
			$ajaxReturn['metadata']['returnCode'] = "THERE_ARE_INVALID_DATA";
			$ajaxReturn = array_merge (
				$ajaxReturn
				, $this->input['delupsert_list']
			);
		}
		else
		{
			// Check if there are TEMPINSERTID_
			$TEMPINSERTID_insertion = array ();
			//echo trace2web ($this->input['delupsert_list'], "get_rid_of_TEMPINSERTID ( this->input['delupsert_list'], array() )");
			$this->input['delupsert_list'] = get_rid_of_TEMPINSERTID ($this->input['delupsert_list'], $TEMPINSERTID_insertion);
			//echo trace2web($delupsert_list, "get_rid_of_TEMPINSERTID ()");

			$ajaxReturn['metadata']['returnCode'] = "API_HAS_BEEN_CALLED";
			$ajaxReturn['TEMPINSERTID_insertion'] = $TEMPINSERTID_insertion;

			trace2file ("", "", __FILE__, true);
			trace2file ($this->input['delupsert_list'], "this->input['delupsert_list']", __FILE__);

			// ######################################################
			// delupsert
			foreach ($this->input['delupsert_list'] as $manipulation => $data_list)
			{
				//echo trace2web($data_list, "data_list");
				if ($manipulation === "delete")
				{
					//echo trace2web ("manipulation === delete");
					//$this->input['delupsert_list']['delete'] = reformat_delete_list ($this->input['delupsert_list']['delete']);	// could be handy, but does not optimize
					foreach ($this->input['delupsert_list'][$manipulation] as $field => $meta_and_data)
					{
						//$ajaxReturn[$manipulation][$field] = dco_delete_api ($config);
						$dco = new datamalico_server_dbquery ();
						$dco->delete(array (
							'table_name' => $meta_and_data['data_itself']['table_name']	// mandatory
							, 'conditions' => $meta_and_data['data_itself']['conditions']
							, 'runas' => $params['runas']
							, 'calling_FILE' => __FILE__
							, 'calling_LINE' => __LINE__
						));
						$ajaxReturn[$manipulation][$field] = $dco->output;
					}
				}
				else if ($manipulation === "insert")
				{
					//echo trace2web ("manipulation === insert");
					//if (exists_and_not_empty ($this->input['delupsert_list']['insert']))
					//{
					// Give function_return the good format to insert data as efficiency as possible
					//$this->input['delupsert_list']['insert'] = reformat_insert_list ($this->input['delupsert_list']['insert']);	// no need because otherwise, only the last insert is taken into account

					foreach ($this->input['delupsert_list']['insert'] as $field => $meta_and_data)
					{
						//$ajaxReturn[$manipulation][$field] = dco_upsert_api ($config);
						$dco = new datamalico_server_dbquery ();
						$dco->upsert(array (
							'table_name' => $meta_and_data['data_itself']['table_name']	// mandatory
							, 'fields' => $meta_and_data['data_itself']['fields']	// $fields['credate'] = "2012-06-01 10:00:03"; $fields['creby'] = 5
							, 'runas' => $params['runas']
							, 'calling_FILE' => __FILE__
							, 'calling_LINE' => __LINE__
						));
						$ajaxReturn[$manipulation][$field] = $dco->output;
					}
					//}
				}
				else if ($manipulation === "update")
				{
					//echo trace2web ("manipulation === update");
					//$this->input['delupsert_list']['update'] = reformat_update_list ($this->input['delupsert_list']['update']);	// could be handy, but does not optimize
					foreach ($this->input['delupsert_list'][$manipulation] as $field => $meta_and_data)
					{
						//$ajaxReturn[$manipulation][$field] = dco_upsert_api ($config);
						$dco = new datamalico_server_dbquery ();
						$dco->upsert(array (
							'table_name' => $meta_and_data['data_itself']['table_name']	// mandatory
							, 'fields' => $meta_and_data['data_itself']['fields']	// $fields['credate'] = "2012-06-01 10:00:03"; $fields['creby'] = 5
							, 'conditions' => $meta_and_data['data_itself']['conditions']
							, 'runas' => $params['runas']
							, 'calling_FILE' => __FILE__
							, 'calling_LINE' => __LINE__
						));
						$ajaxReturn[$manipulation][$field] = $dco->output;
					}
				}
			}
		}

		//echo trace2web($this->input['delupsert_list'], "this->input['delupsert_list']");
		$this->output = $ajaxReturn; //return $ajaxReturn;

		trace2file ($this->output, "this->output", __FILE__);
	}


	/**
	 * Return the structure to be used by the client javascript page in order to easily build a research form.
	 * @code
	 * // Javascript:
	 * $('#myselector').datamalico(ajaxReturn).display_datagrid(/ * params * /); //(See datamalico.lib.js)
	 * @endcode
	 *
	 * Access rights are taken into consideration so that the current user relies on 
	 * 	$GLOBALS['security']['backend_access']['select_where'][{table_name}] in your customized backend_access.conf.php.
	 *
	 * @param params: (mandatory) {associative array} Params of the method
	 * 	- SELECT_fields_to_search_on: (optional) {numerical array of strings} Restrictive list of fields you want to present to make the research. 
	 * 		By default, if you don't specify this param, all fields TRUE with
	 * @code
	 * $GLOBALS['security']['backend_access']['select_where'][{from_table_name}][{field_name}] === TRUE
	 * @endcode 
	 * 		are included to the research fields.
	 * 		But, you can reduce the scope of this variable by setting a list of fields "{table_name}.{field_name}"	via this parameter.
	 * 		Each element of the list, only accept this format:
	 * @code
	 * "{table_name}.{field_name}"
	 * @endcode
	 * 	- FROM_tables: (mandatory) {numerical array of strings} List of tables that the research must search on (and thus, present 
	 * 		research fields).
	 * 		Regarding join tables (see relationships.conf.php to learn more about it), don't mention it, this will be done automatically to make 
	 * 		the link between the entity table and the config table. But you must know that no field of the join table will be displayed as research fields.
	 * 		If you want to display research field of the join table, then create another ajax request in your javascript code, this ajax request must
	 * 		call another ajax server page, in order to specifically return the form structure of a join table.
	 * 		- If ever you specify, a table, but, you don't see any field of this table as result, verify that in the backend_access.conf.php, the 
	 * 			var $GLOBALS['security']['backend_access']['select_where'][{tablename}] has fields you want to search on.
	 * 	- frontend_access: (optional) {associative array} You can override frontend_access settings. Note that for each field, the frontend_access['accesses']['rights']
	 * 		must be "write" in order to input a research value. See datamalico_server_dbquery::select() and its param $params['frontend_access'] and the file
	 * 		frontend_access.conf.php for more information.
	 *
	 * @return Nothing is returned, but the object property this->output is filled with the datamalico_server_dbquery::select_empty() return value. 
	 * 	You must then return this output as the server reply to the requesting ajax client javascript page.
	 *
	 * 
	 *
	 * Example of use
	 * @code
	 * // Javascript code: invokation of the server page replying to the javascript ajax request:
	 * // 	This invokation doesn't send anything, but only the server replies.
	 * mil_ajax ({
	 * 	data: {}
	 * 	, url: "[+this_relative_file_path+]/server.research_get_form.ajax.php"
	 * 	, success: on_success
	 * });
	 * 
	 *
	 * // PHP code: reply of the server:
	 * $dco_ajax = new datamalico_server_ajax ( array (
	 * 	'page_params' => array  ($_GET, $_POST)
	 * 	));
	 * $dco_ajax->research_get_form_structure( array (
	 * 	'SELECT_fields_to_search_on' => array (
	 * 		"data_demand.country_id"
	 * 		, "data_demand.zipcode"
	 * 		, "config_service.name"
	 * 		, "config_service_quality.name"
	 * 		)
	 * 	, 'FROM_tables' => array (
	 * 		"data_demand"
	 * 		, "config_service"
	 * 		, "config_service_quality"
	 * 	)
	 * ));
	 * echo $dco_ajax->output;
	 * @endcode
	 */
	public function research_get_form_structure ($params)
	{
		//echo trace2web ($params, "research_get_form_structure() params");

		$SELECT_members = array ();
		$FROM_tables = array ();

		if (!exists_and_not_empty($params['frontend_access']))
		{
			$params['frontend_access'] = array();
		}

		foreach ($params['FROM_tables'] as $key => $table_name)
		{
			//echo trace2web ($table_name, "table_name");
			// ######
			// check if the current user has the right to search (where clause of the select query) at least through one field of the table.
			if (exists_and_not_empty ($GLOBALS['security']['backend_access']['select_where'][$table_name]))
			{
				foreach ($GLOBALS['security']['backend_access']['select_where'][$table_name] as $field_name => $rights)
				{
					$can_vertically_access_field = can_vertically_access_field ( array (
						'manipulation' => "select_where"
						, "field_name" => $field_name
						, 'field_infos' => array (
							'field_direct' => array (
								'table' => $table_name
							)
						)
					));

					//echo trace2web ("	$table_name.$field_name ==> $can_vertically_access_field");

					if ($can_vertically_access_field === true)
					{
						// prevent duplicates of table names in the $FROM_tables list
						$last_index = count($FROM_tables) - 1;
						if ($FROM_tables[$last_index] !== $table_name)
						{
							// #######
							// Addition of the table in the from list (if not already added)
							array_push($FROM_tables, $table_name);
						}

						// #######
						// Addition of the field in the select list (if not already added)
						if (exists_and_not_empty ($params['SELECT_fields_to_search_on']))
						{
							foreach ($params['SELECT_fields_to_search_on'] as $index => $authorized_full_field_name)
							{
								//echo trace2web($table_name . "." . $field_name . " === " . $authorized_full_field_name);

								if ($table_name . "." . $field_name === $authorized_full_field_name)
								{
									array_push($SELECT_members, $table_name . "." . $field_name);
								}
								else if ($table_name . ".*" === $authorized_full_field_name)
								{
									array_push($SELECT_members, $table_name . "." . $field_name);
								}
							}
						}
						else
						{
							array_push($SELECT_members, $table_name . "." . $field_name);
						}
					}
				}
			}
		}

		//echo trace2web ($SELECT_members, "SELECT_members");
		//echo trace2web ($FROM_tables, "FROM_tables");

		if (exists_and_not_empty ($FROM_tables))
		{
			//$select_query = $this->research_build_select_query();
			$select_query = $this->research_build_select_query ( array (
				'SELECT_members' => $SELECT_members
				, 'FROM_tables' => $FROM_tables
			));
			//echo trace2web($select_query);

			$config = array (
				'sql' => $select_query
				, 'frontend_access' => $params['frontend_access']
				, 'calling_FILE' => __FILE__
				, 'calling_LINE' => __LINE__
			);
			$dco = new datamalico_server_dbquery ();
			$dco->select_empty($config);

			$this->output = $dco->output;

			//echo trace2web ($dco, "dco");
		}

		//echo trace2web ($this->output, "research_get_form_structure() ending");
	}

	/**
	 * This function is very similar to datamalico_server_dbquery::select_multiselist(), but has as purpose to return the structure in order to create a
	 * 	resarch form on a multi-selection-list.
	 *
	 * @param $params: {associative array} Same params as datamalico_server_dbquery::select_multiselist(), but you must ommit the following:
	 * 	- entity_id
	 * 	- temp_insert_id
	 * 	- action
	 *
	 * @return Nothing is returned, but the object property this->output is filled with the datamalico_server_dbquery::select_multiselist() return value.
	 * 	This output is accessible fields of the join table.
	 * 	You must then return this output as the server reply to the requesting ajax client javascript page.
	 */
	function research_multiselist_get_form_structure ($params)
	{
		$config = array (
			'entity_table' => $params['entity_table']
			//, 'entity_id' => $entity_id
			, 'temp_insert_id' => array (
				'field' => ""
				, 'value' => ""
			)
			, 'list_table' => $params['list_table']
			, 'frontend_access' => $params['frontend_access']
			, 'action' => array (
				'save_mode' => "global_save"
			)
			, 'calling_FILE' => $params['calling_FILE']
			, 'calling_LINE' => $params['calling_LINE']
		);

		$dco = new datamalico_server_dbquery ();
		$dco->select_multiselist ($config);

		$this->output = $dco->output;
	}

	/**
	 * @warning Create a query relying on the datamalico_server_ajax::input['select']['where'] params, and the $params of the method.
	 *
	 * Through the ajax params datamalico_server_ajax::input['select']['where'] a user can give criteria to build a query that you set in the 
	 * 	PHP file, calling this method.
	 *
	 * @param $params: (optional) {associative array} Params for this method.
	 * 	- SELECT_members: (optional, default is *, otherwise the select clause will only have the elements you write here) {numerical array} 
	 * 		Specify the select clause members to add to the select query. There is no special format to respect,
	 * 		you can write, anything that a select clause of a select query can accept.
	 * 	- FROM_tables: (mandatory) {numerical array of strings} Sepcify the list of tables to include in the FROM clause of the SELECT query.
	 * 		(This is not necessary to specify join tables, making the link between a entity table, and a config table (See relationship.conf.php to learn more))
	 * 		@b Security: owing to these 'FROM_tables' you ensure security of your SQL query.
	 * 	- WHERE_authorized: (optional, if absent, any condition authorized (in the scope of the $GLOBALS['security']['backend_access']['select_where']) is authorized)
	 * 		{numerical array of strings} You can reduce the scope of $GLOBALS['security']['backend_access']['select_where'][{table_name}][{field_name}] 
	 * 		by setting a list of authorized {table_name}.{field_name} in the where clause.
	 * 	- WHERE_additions (optional) {numerical array} Additional criteria you can add into the WHERE clause in order to control security. This 
	 * 		is actually the horizontal security you can define for your query.
	 * 	- GROUP_BY: (optional) {string} The group by string. Eg. tablename.fieldname, tablename.fieldname2 DESC
	 *	- ORDER_BY: (optional) {string} The order by string. Eg. tablename.fieldname, tablename.fieldname2 DESC
	 *
	 * Here is the structure the datamalico_server_ajax::input['select']['where'] must have:
	 * - select: (mandatory to send criteria) {associative array}
	 * 	- where: (mandatory to send criteria) {associative array}
	 * 		- {html_container_id}: (mandatory to send criteria) {associative array}
	 * 			- t: (mandatory, if absent, the criteria is not added to the select query) Table name of the WHERE condition.
	 * 			- o: (optional) {string} Operator to be used in the resulting SQL query. This can be a value among this list:
	 * 				- like: LIKE
	 * 				- notlike: NOT LIKE
	 * 				- eq: = (equals)
	 * 				- noteq: <> (not equals)
	 * 				- lt: < (less than)
	 * 				- gt: > (greater than)
	 * 				- lt_or_eq: <= (less than or equals)
	 * 				- gt_or_eq: >= (greater than or equals)
	 * 				- betw: As there is is no between operator in SQL, this operator will result a combination of a first condition <= and 
	 * 					a second >=
	 * 					the first criteria field name is the name of the column in the DB table, whereas the the second criteria field name
	 * 					is appended with _MAX 
	 * 			- c: (mandatory, if absent, the criteria is not added to the select query) {string} Criteria value.
	 * 				- {db_fieldname}: {string} (optional) Is set with the value you want to research.
	 * 				- !!! You must know that, even if a string is sent to the server page, 
	 * 					- it is automatically adapted to sql query.
	 * 					- and regarding operators, with a:
	 * 						- string field (MySQL varchar, text, mediumtext, longtext) The default operator is 'LIKE'
	 * 							- With the operator 'LIKE': 
	 * 								- If there are several words, each word becomes a condition (A word is everything separataed by
	 * 									any punctuation /[\p{P}]/ or any blank char /[\p{Z}]/)
	 * 								- The wildcard operator % is prepend and append to each word
	 * 							- With other operators, the behavior is the same
	 * 						- number field (MySQL tinyint, smallint, mediumint, int, bigint, decimal) The default operator is = (equals)
	 * 						- date field (MySQL datetime, timestamp) The default operator is 'LIKE'
	 * 							- The wildcard operator % is prepend and append to the time expression
	 * 			- g: {associative array} (optional) Are grouping specifications of the WHERE clause. It must be define as a frontend_access value.
	 * 				(See $GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']['research_operators']['cond_group'] in frontend_access.conf.php)
	 * 				- name: {string} (optional, default is "default") Is the name of the group of conditions.
	 * 				- parent: {string} (optional, default is "none") Is the name of the parent group.
	 * 				- join_op: {string} (optional, default is "AND") Is the join operator linking conditions into the group. Possible values are:
	 * 					- "AND"
	 * 					- "OR"
	 *				- cond_group: {associative array} (optional) Are options about the field and its grouping:
	 *					(see also the frontend_access structure in frontend_access.conf.php and get_one_intelligent_condition in the datamalico_server_ajax.lib.php)
	 * 					- name: {string} (optional, default is 'default', the main group into a WHERE clause) eg:
	 * 						@code	
	 *						SELECT ...
	 *						WHERE
	 *						# Condition Group: default
	 *						(
	 *						tablename.fieldname = 'value'
	 *						)
	 * 					- parent: {string} (optional, default is 'none') This is the name of the parent group (because groups can be cascading)
	 * 					- join_op: {string} (optional, default is 'AND') This is the join operator into the group itself
	 * 					- oper_opt: {associative array} Are operator options:
	 * 						- exact_word: {bool} (optional, default is false) If true, it searches the exact word: 'word' and not a part of word: '%word%'
	 * 						- exact_expr: {bool} (optional, default is false) If true, it searches the exact expression: 'hello world' and not several expression: 'hello' + 'world'
	 * 						- all_words: {bool} (optional, default is true) If true, it searches if ALL expressions are present in a field, instead of only one expression.
	 * 				
	 * 				
	 *	 
	 *
	 * You must know that each condition is authorized if:
	 * 	- the research value is not empty
	 * 	- AND the research criteria is valid (See in data_validator.conf.php $GLOBALS['data_validator'][{tablename}][{fieldname}]['research_criteria'])
	 * 	- AND the the field can be vertically accessed on a 'select_where' manipulation (See in backend_access.conf.php 
	 * 		$GLOBALS['security']['backend_access']['select_where'][{tablename}][{fieldname}])
	 * 	- AND if you specify WHERE_authorized as argument, the field is one of those you authorize. (Per default, if you don't specify anything
	 * 		a field is authorized if above tests are TRUE.
	 *
	 * You must also know that a string field (with no research_operators) is searched with a LIKE operator and surrounded by %, in order to find the pattern anywhere.
	 * And searching several words, will search %word1% AND %word2% AND %word3% ...
	 *
	 * @warning Note for coders: this method is also used by datamalico_server_ajax::research_get_form_structure() in order to build the HTML form (followed by
	 * 	datamalico_server_dbquery::select_empty() )
	 *
	 *
	 *
	 * Example of sending params through an ajax request via HTML:
	 * @code
	 * // Params under the HTML format:
	 * <input type="hidden" name="select[where][ET_supposedServicePrice_ctnr][t]" value="data_demand_2_service_type">
	 * <select name="select[where][ET_supposedServicePrice_ctnr][o][ET_supposedServicePrice]" class="research_simple_operators">
	 * 	<option value="eq" selected="selected">=&nbsp; (Equals)</option>
	 * 	<option value="gt_or_eq" selected="selected">&gt;= (Greater than or equals)</option>
	 * 	<option value="lt_or_eq">&lt;= (Less than or equals)</option>
	 * 	<option value="betw">..&nbsp; (Between, included values)</option>
	 * </select>
	 * <input type="text" name="select[where][ET_supposedServicePrice_ctnr][c][ET_supposedServicePrice]" value="1000">
	 * <!--
	 * 	<input type="text" name="select[where][ET_supposedServicePrice_ctnr][c][ET_supposedServicePrice_MAX]" value="9999">
	 * 	This line would be only if the operator is "betw"
	 * -->
	 * @endcode
	 *
	 * Example of sending params through an ajax request via javascript:
	 * @code
	 * // Params under the javascript format sent as data throught the jQuery.ajax() function:
	 * select: {
	 * 	where: {
	 * 		ET_supposedServicePrice_ctnr: {
	 * 			t: "data_demand_2_service_type"
	 * 			, o: {
	 * 				ET_supposedServicePrice: "gt_or_eq"
	 * 			}
	 * 			, c: {
	 * 				ET_supposedServicePrice: "1000"
	 * 				//, ET_supposedServicePrice_MAX: "9999" // This line would be only if the operator is "betw"
	 * 			}
	 * 		}
	 * 	}
	 * }
	 * @endcode
	 *
	 * Example of use:
	 * @code
	 * $dco_ajax = new datamalico_server_ajax ( array (
	 * 	'page_params' => array  ($_GET, $_POST)
	 * )); 
	 * 
	 * // datamalico_server_ajax::input['select']['where'] will be taken into account in the where clause (if authorized):
	 * $sql = $dco_ajax->research_build_select_query ( array (
	 * 	'SELECT_members' => array (
	 * 		"data_demand.demand_id"
	 * 		, "data_demand_2_service_type.demand_service_type_id"
	 * 		, "data_demand_2_service_type.service_type_id"
	 * 		, "data_demand.zipcode"
	 * 		, "data_demand.country_id"
	 * 		, "data_demand.role_target"
	 * 		, "mil_v_demand_status.remaining_time"
	 * 		, "mil_v_demand_status.publishedEndDate"
	 * 		, "data_demand_2_service_type.ET_supposedServicePrice"
	 * 		, "data_demand_2_service_type.myCommForContact_ET_cents"       
	 * 	)
	 * 	, 'FROM_tables' => array (
	 * 		"data_demand"
	 * 		, "mil_v_demand_status"
	 * 		, "data_demand_2_service"
	 * 		, "data_demand_2_service_type"
	 * 	)
	 * 	, 'WHERE_authorized' => array ()
	 * 	, 'WHERE_additions' => array (
	 * 		"(data_demand.publication_status_delayed = 'will_be_published' OR data_demand.publication_status_delayed = 'is_currently_published')"
	 * 		, "mil_v_demand_status.publication_status_real_time = 'is_currently_published'"
	 * 		, "mil_v_demand_status.enabled = true"  	// the demand creator's account is enabled
	 * 		, "mil_v_demand_status.valid_email = true"      // The email is valid
	 * 	)
	 * 	, 'GROUP_BY' => "data_demand_2_service_type.demand_service_type_id"
	 * ));
	 * //echo trace2web ($sql);
	 * 
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->select( array (
	 * 	'sql' => $sql
	 * 	, 'frontend_access' => array (
	 * 		'demand_id' => array (
	 * 			'accesses' => array ('rights' => "read")
	 * 		)
	 * 		, 'pagination' => $this_mil_page->page_params['pagination']
	 * 		, 'calling_FILE' => __FILE__
	 * 		, 'calling_LINE' => __LINE__
	 * 	)
	 * ));
	 * @endcode
	 *
	 *
	 * @todo For select['where'][{html_container_id}]['c'] Improve the behavior with other operators for string values.
	 */
	public function research_build_select_query ($params)
	{
		//echo trace2web ($params, "research_build_select_query() params");

		// #######################################################
		// Config and params
		$select_clause = "";
		$from_clause = "";
		$where_clause = "";

		// #####################
		// SELECT clause of the select query
		$select_clause;
		//$select_clause_members_count = count ($params['SELECT_members']);
		if (!exists_and_not_empty($params['SELECT_members']))
		{
			$select_clause = "SELECT *\n";
		}
		else
		{
			$select_clause = "SELECT\n" . get_SELECT_clause ($params['SELECT_members']) . "\n";
		}
		//echo trace2web ($select_clause);


		// #####################
		// FROM clause of the select query
		//if (!exists_and_not_empty($params['FROM_tables'])) $params['FROM_tables'] = array();
		$from_clause_content = get_FROM_clause ($params['FROM_tables']);
		if (empty($from_clause_content))
		{
			$select_query = "";
			return $select_query;
		}
		$from_clause = "FROM\n" . $from_clause_content;
		//echo trace2web ($from_clause);


		// #####################
		// WHERE clause of the select query
		//$this->input['select']['where'];
		if (
			exists_and_not_empty ($this->input['select']['where'])
			|| exists_and_not_empty ($params['WHERE_additions'])
		)
		{
			//if (!exists_and_not_empty($params['WHERE_authorized'])) $params['WHERE_authorized'] = array();
			//echo trace2web($this->input['select']['where'], "this->input['select']['where']");

			$build_where_string = get_WHERE_clause($this->input['select']['where'], $params['WHERE_authorized']);
			//echo trace2web($build_where_string, "build_where_string");
			$WHERE_additions_string = get_AND_condition ($params['WHERE_additions']);

			if (
				$build_where_string !== ""
				&& $WHERE_additions_string !== "")
			{
				$where_clause = $build_where_string . "\nAND\n" . $WHERE_additions_string;
			}
			else
			{
				if ($build_where_string !== "") $where_clause = $build_where_string;
				if ($WHERE_additions_string !== "") $where_clause = $WHERE_additions_string;
			}

			if ($where_clause !== "") $where_clause = "WHERE $where_clause\n";
		}

		if (exists_and_not_empty ($params['GROUP_BY']))
		{
			$params['GROUP_BY'] = "GROUP BY " . $params['GROUP_BY'] . "\n";
		}

		if (exists_and_not_empty ($params['ORDER_BY']))
		{
			$params['ORDER_BY'] = "ORDER BY " . $params['ORDER_BY'] . "\n";
		}

		$select_query = $select_clause . $from_clause . $where_clause . $params['GROUP_BY'] . $params['ORDER_BY'];

		return $select_query;
	}
}



/**
 * Transform the raw received data (see get_data_from_GET_POST() ) to a logical_data_list, transforming:
 * - [tn] to [table_name]
 * - [fn] and [fv] to [fields]
 * - [cdn] and [cdv] to [conditions]
 *
 * It also makes the difference between data_itself and metadata of the field.
 *
 * @param $delupsert_list {associative array} out from get_data_from_GET_POST()
 *
 * @return $logical_data_list {associative array}
 *
 * Example of returned array:
 * @code
 * formdata_2_logical_data_list () :Array
 * (
 *     [firstname] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered
 *                     [fields] => Array
 *                         (
 *                             [firstname] => Christophe
 *                         )
 * 
 *                     [conditions] => Array
 *                         (
 *                             [reg_id] => TEMPINSERTID_201208141806500054
 *                         )
 * 
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => firstname_sub
 *                 )
 * 
 *         )
 * 
 *     [lastname] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered
 *                     [fields] => Array
 *                         (
 *                             [firstname] => Delcourte
 *                         )
 * 
 *                     [conditions] => Array
 *                         (
 *                             [reg_id] => TEMPINSERTID_201208141806500054
 *                         )
 * 
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => lastname_sub
 *                 )
 * 
 *         )
 * 
 *     [1_reg_id_20120814180650470] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered_2_profession
 *                     [fields] => Array
 *                         (
 *                             [reg_id] => TEMPINSERTID_201208141806500054
 *                             [profession_id] => 1
 *                         )
 * 
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => 1_reg_id_20120814180650470_sub
 *                 )
 * 
 *         )
 * 
 *     [2_reg_id_20120814180650474] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered_2_profession
 *                     [fields] => Array
 *                         (
 *                             [reg_id] => TEMPINSERTID_201208141806500054
 *                             [profession_id] => 13
 *                         )
 * 
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => 2_reg_id_20120814180650474_sub
 *                 )
 * 
 *         )
 * 
 *     [3_reg_id_20120814180650476] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered_2_profession
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => 3_reg_id_20120814180650476_sub
 *                 )
 * 
 *         )
 * 
 *     [4_reg_id_20120814180650478] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered_2_profession
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => 4_reg_id_20120814180650478_sub
 *                 )
 * 
 *         )
 * 
 *     [5_reg_id_20120814180650481] => Array
 *         (
 *             [data_itself] => Array
 *                 (
 *                     [table_name] => data_registered_2_profession
 *                 )
 * 
 *             [metadata] => Array
 *                 (
 *                     [ctnr] => 5_reg_id_20120814180650481_sub
 *                 )
 * 
 *         )
 * 
 * )
 * @endcode
 */
function formdata_2_logical_data_list ($delupsert_list)
{
	$fn_return;
	foreach ($delupsert_list as $ctnr_id => $data_itself)
	{
		if (exists_and_not_empty ($data_itself['t'])) $fn_return[$ctnr_id]['data_itself']['table_name'] = $data_itself['t'];
		if (exists_and_not_empty ($data_itself['f'])) $fn_return[$ctnr_id]['data_itself']['fields'] = $data_itself['f'];
		if (exists_and_not_empty ($data_itself['c'])) $fn_return[$ctnr_id]['data_itself']['conditions'] = $data_itself['c'];
		$fn_return[$ctnr_id]['metadata']['ctnr'] = $ctnr_id;
	}

	return $fn_return;
}
function formdata_2_logical_data_list_stopped ($delupsert_list)
{
	$fn_return;
	foreach ($delupsert_list as $ctnr_id => $data_itself)
	{
		if (exists_and_not_empty ($data_itself['tn']))
		{
			$fn_return[$ctnr_id]['data_itself']['table_name'] = $data_itself['tn'];
			$fn_return[$ctnr_id]['data_itself']['fields'] = array ();
			$fn_return[$ctnr_id]['data_itself']['conditions'] = array ();
			$fn_return[$ctnr_id]['metadata']['ctnr'] = $data_itself['ctnr'];

			// #############
			// format fields
			if (exists_and_not_empty ($data_itself['fn']) && exists_and_not_empty ($data_itself['fv']))
			{
				$field_names = $data_itself['fn'];
				$field_new_values = $data_itself['fv'];

			/*$fn_return[$ctnr_id]['data_itself']['fields'] = array ($field_names => $field_new_values);
			if ($fn_return[$ctnr_id]['data_itself']['fields'] === false)
			{
				unset ($fn_return[$ctnr_id]['data_itself']['fields']);
			}*/

			/*foreach ($field_new_values as $j => $val)
			{
				if (!exists_and_not_empty ($field_new_values[$j]))
				{
					unset ($field_names[$j]);
					unset ($field_new_values[$j]);
				}
			}*/

				if (count ($field_names) !== 0 && count ($field_new_values) !== 0)
				{
					$fn_return[$ctnr_id]['data_itself']['fields'] = array_combine($field_names, $field_new_values);
				}
			}

			// #############
			// format conditions
			if (exists_and_not_empty ($data_itself['cdn']) && exists_and_not_empty ($data_itself['cdv']))
			{
				$condition_names = $data_itself['cdn'];
				$condition_values = $data_itself['cdv'];

			/*foreach ($condition_values as $j => $val)
			{
				if (!exists_and_not_empty ($condition_values[$j]))
				{
					unset ($condition_names[$j]);
					unset ($condition_values[$j]);
				}
			}*/

				if (count ($condition_names) !== 0 && count ($condition_values) !== 0)
				{
					$fn_return[$ctnr_id]['data_itself']['conditions'] = array_combine($condition_names, $condition_values);
				}
			}

			// because array_combine returns FALSE if the number of elements for each array isn't equal
			// then either fields instruction or conditions are considered as wrong.
			// we unset both fields and conditions, because, if we unset only one (fields or conditions) we switch from delete to insert meaning.
			// then we cancel this whole action, by unsetting both.
			//trace("$ctnr_id : fields : " . (bool) $fn_return[$ctnr_id]['data_itself']['fields']);
			//trace("$ctnr_id : conditions : " . (bool) $fn_return[$ctnr_id]['data_itself']['conditions']);
			if (
				$fn_return[$ctnr_id]['data_itself']['fields'] === false
				|| $fn_return[$ctnr_id]['data_itself']['conditions'] === false
			)
			{
				//trace("unset both");
				unset ($fn_return[$ctnr_id]['data_itself']['fields']);
				unset ($fn_return[$ctnr_id]['data_itself']['conditions']);
				$fn_return[$ctnr_id]['metadata']['returnCode'] = "MALFORMED_FORM_ARGUMENTS";
				$fn_return[$ctnr_id]['metadata']['returnMessage'] = $GLOBALS['mil_lang']['MALFORMED_FORM_ARGUMENTS'];
			}

			if (empty ($fn_return[$ctnr_id]['data_itself']['fields']))
			{
				unset ($fn_return[$ctnr_id]['data_itself']['fields']);
			}

			if (empty ($fn_return[$ctnr_id]['data_itself']['conditions']))
			{
				unset ($fn_return[$ctnr_id]['data_itself']['conditions']);
			}
		}
	}

	return $fn_return;
}

/**
 * Returns an array with 3 kinds of data: data to be deleted, inserted and updated.
 *
 * @param $logical_data_list {associative array} Is the result of the formdata_2_logical_data_list()
 *
 * Example of the returned array:
 * @code
 * split_as_delete_insert_update () :Array
 * (
 *     [delete] => Array
 *         (
 *         )
 * 
 *     [insert] => Array
 *         (
 *             [firstname] => Array
 *                 (
 *                     [data_itself] => Array
 *                         (
 *                             [table_name] => data_registered
 *                             [fields] => Array
 *                                 (
 *                                     [firstname] => Christophe
 *                                 )
 * 
 *                             [conditions] => Array
 *                                 (
 *                                     [reg_id] => TEMPINSERTID_20120815103424189
 *                                 )
 * 
 *                         )
 * 
 *                     [metadata] => Array
 *                         (
 *                             [ctnr] => firstname_sub
 *                         )
 * 
 *                 )
 * 
 *             [lastname] => Array
 *                 (
 *                     [data_itself] => Array
 *                         (
 *                             [table_name] => data_registered
 *                             [fields] => Array
 *                                 (
 *                                     [firstname] => Delcourte
 *                                 )
 * 
 *                             [conditions] => Array
 *                                 (
 *                                     [reg_id] => TEMPINSERTID_20120815103424189
 *                                 )
 * 
 *                         )
 * 
 *                     [metadata] => Array
 *                         (
 *                             [ctnr] => lastname_sub
 *                         )
 * 
 *                 )
 * 
 *             [1_reg_id_20120815103424630] => Array
 *                 (
 *                     [data_itself] => Array
 *                         (
 *                             [table_name] => data_registered_2_profession
 *                             [fields] => Array
 *                                 (
 *                                     [reg_id] => TEMPINSERTID_20120815103424189
 *                                     [profession_id] => 1
 *                                 )
 * 
 *                         )
 * 
 *                     [metadata] => Array
 *                         (
 *                             [ctnr] => 1_reg_id_20120815103424630_sub
 *                         )
 * 
 *                 )
 * 
 *             [2_reg_id_20120815103424641] => Array
 *                 (
 *                     [data_itself] => Array
 *                         (
 *                             [table_name] => data_registered_2_profession
 *                             [fields] => Array
 *                                 (
 *                                     [reg_id] => TEMPINSERTID_20120815103424189
 *                                     [profession_id] => 13
 *                                 )
 * 
 *                         )
 * 
 *                     [metadata] => Array
 *                         (
 *                             [ctnr] => 2_reg_id_20120815103424641_sub
 *                         )
 * 
 *                 )
 * 
 *         )
 * 
 *     [update] => Array
 *         (
 *         )
 * 
 * )
 * @endcode
 */
function split_as_delete_insert_update ($logical_data_list)
{
	$delete = array ();
	$insert = array ();
	$update = array ();

	foreach ($logical_data_list as $ctnr_id => $meta_and_data)
	{
		if (exists_and_not_empty ($meta_and_data['data_itself']['table_name'])) // in every case, the table name is absolutely necessarary
		{
			// if a delete --> presence of : {condition} - absence of : {field}
			if (
				exists_and_not_empty ($meta_and_data['data_itself']['conditions'])
				&& !exists_and_not_empty ($meta_and_data['data_itself']['fields'])
			)
			{
				$delete[$ctnr_id] = $meta_and_data;
			}

			// if an insert --> presence of : {field} - absence of : {condition}
			else if (
				exists_and_not_empty ($meta_and_data['data_itself']['fields'])
				&& !exists_and_not_empty ($meta_and_data['data_itself']['conditions'])
			)
			{
				$insert[$ctnr_id] = $meta_and_data;
			}

			// if an update --> presence of : {field, condition} - absence of : {}
			else if (
				exists_and_not_empty ($meta_and_data['data_itself']['fields'])
				&& exists_and_not_empty ($meta_and_data['data_itself']['conditions'])
			)
			{
				$update[$ctnr_id] = $meta_and_data;
			}

			// Should never happen
		/*else
		{
			new mil_Exception (
				__FUNCTION__ . " : Should never happen, neither a delete, nor an insert, nor an update"
				, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
		}*/
		}
	}

	$split_as_delete_insert_update = array (
		'delete' => $delete	// deletions must be in 1st place, because in the case of multiple selection listings, first, delete and then insert and update
		, 'insert' => $insert	// insertions must be in 2nd position
		, 'update' => $update	// update in 3rd position
	);

	return $split_as_delete_insert_update;
}


/**
 * This function is  a fake function, this is an illustration of the function you can add (as argument -you only send its name as a string- ) when you call the method: 
 * 	set_horizontal_access()
 *
 * @param $one_record_selector (mandatory) {associative array} Automatically sent by the datamalico_server_ajax class and its method set_horizontal_access()
 * 	See datamalico_server_ajax.lib.php
 *	- What is a record selector? This is a group of elements which allows to target your SQL SELECT, UPDATE or DELETE on restricted records containing a particular criteria.
 *		In order to isolate one or several specific rows, you need to specify:
 * 		- the table name (this is the 'FROM' sql clause)
 * 		- and the criteria (the 'WHERE' sql clause).
 * 		- This is all what you need. Datamalico attachs to this record selector, all the ids of HTML containers that share this same selector.
 * 		Here is what a record selector looks like:
 * 		@code
 * $one_record_selector =
 * 	[table_name] => registered
 * 	[conditions] => Array
 * 	(
 * 	    [reg_id] => 2
 * 	)
 * 
 * 	[ctnr_ids] => Array
 * 	(
 * 	    [0] => lastname
 * 	    [2] => firstname
 * 	)
 * 		@endcode
 * @param $custom_horizontal_access_args (mandatory) {associative array} Array containing parameters you want to send to your custom function. This argument 
 * 	recieves what you send from set_horizontal_access () as custom_horizontal_access_args param. Note that if ever in set_horizontal_access () you don't
 * 	send this param, then an empty array will be recieved by your custom function.
 *
 * @return $fn_return: {associative array}
 * 	- conditions: (optional) {associative array} Additional conditions (for the sql 'WHERE' clause, concatenate with the AND reserved word) 
 * 	to reduce the scope of the 'DELETE' or 'UPDATE' queries.
 * 		- cond_1: value of the condition
 * 		- [cond_2: value of the condition]
 * 		- [...]
 * 	- horizontal_access: (optional) {boolean} Per default when you call the set_horizontal_access() method, the horizontal_access is false, unless you
 * 		set, here, this value to true.
 *
 * Calling this method will impact actions:
 * - 'DELETE' and 
 * - 'UPDATE' queries, 
 * - wheras 'INSERT' won't be impacted, because queries cannot be checked before and fetched through a select. See backend_access.conf.php for 'INSERT' authorizations.
 *
 * As soon as you call the method set_horizontal_access(), when you use the method datamalico_server_ajax::delupsert(), every 'DELETE' and 'UPDATE' 
 * 	background action that you don't specifically authorize, will be unauthorized.
 * 	Please note, that the INSERT action is only a firt insertion, followed by several UPDATE.
 * 
 * - How to specifically authorize a 'DELETE' or 'UPDATE'? 
 * 	- By returning:
 * @code
 * $fn_return['horizontal_access'] = true;
 * @endcode
 * 	- But you can also specify criteria in the 'WHERE' clause on 'DELETE' and 'UPDATE' queries, so that you can isolate only records you want 
 * 		the user to 'DELETE' or 'UPDATE', by returning:
 * @code
 * $fn_return['conditions']['user_id'] = $user_id; // so that the current user can update or delete only its own record.
 * @endcode
 * @warning Note that these conditions will be added to the already existing conditions in the record seletor, and will override them, if one particular condition
 * 	already existed. 
 *
 * How to write your custom horizontal access?
 * - If the $one_record_selector is relative to a specific table you decide, then you can add additional conditions, that is to say, criteria in the 'WHERE'
 * 	sql clause for further 'DELETE' or 'UPDATE'.
 * 	- In this case, 
 * 		- you must set the 'horizontal_access' to true, 
 * 		- and add conditions, ex:
 * @code
 * return array (
 * 	'horizontal_access' => true
 * 	, 'conditions' => array (
 * 		'user_id' => $custom_horizontal_access_args['this_mil_page']->current_user_keys['current_user_id'] // This will add a condition in the WHERE clause of the SQL: AND user_id = 38;
 * 	)
 * );
 * @endcode
 *
 * Here is a complete example of how to write and call your custom function:
 * @code
 * // ############################
 * // How to call your custom function:
 * $dco_ajax->set_horizontal_access (
 * 	array (
 * 		'custom_horizontal_access_function' => "my_set_horizontal_access"
 * 		, 'custom_horizontal_access_args' => array (
 * 			'this_mil_page' => $this_mil_page
 * 		)
 * 	)
 * );
 * 
 * // ############################
 * // Define your custom function:
 * function my_set_horizontal_access ($one_record_selector, $custom_horizontal_access_args)
 * {
 * 	$fn_return = array ();		// by default, the ['horizontal_access'] will be false;
 * 	$table_name = $one_record_selector['table_name'];
 * 
 * 	// Horizontal security, precising the record selector:
 * 	if (
 * 		$table_name === "data_registered"
 * 		|| $table_name === "data_registered_2_role"
 * 	)
 * 	{
 * 		$fn_return['conditions'] = array (
 * 			'reg_id' => $custom_horizontal_access_args['this_mil_page']->current_user_keys['website']['reg_id']
 * 		);
 * 		$fn_return['horizontal_access'] = true;
 * 	}
 * 
 * 	return $fn_return;
 * }
 * @endcode
 */
function FAKE_my_set_horizontal_access_FAKE ($one_record_selector, $custom_horizontal_access_args)
{
	//echo trace2web($one_record_selector, "one_record_selector");
	//return true;

	$fn_return = array (
		'horizontal_access' => false
	);

	//$GLOBALS['security']['current_user_keys'];
	$horizontal_access = false;
	$table_name = $one_record_selector['table_name'];

	// SECURITY horizontal :

	if (
		$table_name === "data_registered"
		|| $table_name === "data_registered_2_role"
	)
	{
		$fn_return['conditions'] = array (
			'reg_id' => $custom_horizontal_access_args['this_mil_page']->current_user_keys['website']['reg_id']
		);
		$fn_return['horizontal_access'] = true;
	}

	return $fn_return;
}



/**
 * This function should be private.
 *
 * In order to avoid multiple db queries to set_horizontal_access or make several atomic delete or update, we check before if in the set of data,
 * 	there are duplicates (eg for the same table, and the same conditions)
 *
 * What is a record selector?
 * - This is params you need to select a record, that is to say:
 * 	- a table name
 * 	- and one or more conditions (what you could write into a where clause).
 */	
function get_record_selectors ($data_list)
{
	//echo trace2web($data_list, "data_list");

	// ####################################
	// create an array of serialized conditions in order to ease recognition of duplicates
	foreach ($data_list as $ctnr_id => $data_itself)
	{
		$one_record_selector['table_name'] = $data_list[$ctnr_id]['data_itself']['table_name'];
		$one_record_selector['conditions'] = $data_list[$ctnr_id]['data_itself']['conditions'];

		$temp_serial = serialize($one_record_selector);

		$record_selectors['serial'][] = $temp_serial;
		$record_selectors['ctnr_ids'][] = $ctnr_id;
	}

	//echo trace2web($record_selectors, "record_selectors");

	// ####################################
	// sort this array
	array_multisort(
		$record_selectors['serial'], SORT_ASC, SORT_STRING
		, $record_selectors['ctnr_ids'], SORT_ASC, SORT_NUMERIC
	);

	// ####################################
	// Removes duplicates of serials
	$temp1_record_selectors = $record_selectors;
	$temp2_record_selectors = $record_selectors;
	$record_selectors = array();
	$i = 0;
	while (list($key, $val) = each($temp1_record_selectors['serial'])) // better than a foreach because, unset doesn't impact the reading of a foreach. (The array is frozen before a foreach)
	{
		//trace("loop 1");
		$record_selectors['serial'][$i] = $temp1_record_selectors['serial'][$key];
		$record_selectors['ctnr_ids'][$i] = (array)$temp1_record_selectors['ctnr_ids'][$key];

		while (list($key2, $val2) = each($temp2_record_selectors['serial']))
		{
			//trace("loop 2");
			//trace("i => $i");
			//trace("key, val => $key, $val");
			//trace("key2, val2 => $key2, $val2");


			if ($record_selectors['serial'][$i] === $temp2_record_selectors['serial'][$key2])
			{
				$record_selectors['ctnr_ids'][$i] = array_merge (
					(array)$record_selectors['ctnr_ids'][$i]
					, (array)$temp2_record_selectors['ctnr_ids'][$key2]
				);
				unset($temp2_record_selectors['serial'][$key2]);
				unset($temp2_record_selectors['ctnr_ids'][$key2]);
				unset($temp1_record_selectors['serial'][$key2]);
				unset($temp1_record_selectors['ctnr_ids'][$key2]);
			}

			//echo trace2web($record_selectors, "record_selectors");
			//echo trace2web($temp1_record_selectors, "temp1_record_selectors");
			//echo trace2web($temp2_record_selectors, "temp2_record_selectors");
		}
		reset($temp2_record_selectors['serial']);
		unset($temp1_record_selectors['serial'][$key]);
		unset($temp1_record_selectors['ctnr_ids'][$key]);
		$i++;
	}
	unset ($temp1_record_selectors);
	unset ($temp2_record_selectors);


	// ####################################
	// Removes duplicates of ctnr_ids
	foreach ($record_selectors['ctnr_ids'] as $i => $val)
	{
		$record_selectors['ctnr_ids'][$i] = array_unique ($record_selectors['ctnr_ids'][$i]);
	}
	//echo trace2web($record_selectors, "record_selectors");


	// ####################################
	// reformat the array in a good maner
	$temp_record_selectors = $record_selectors;
	$record_selectors = array();
	$i = 0;
	foreach ($temp_record_selectors['serial'] as $key => $val)
	{
		$one_record_selector = unserialize ($temp_record_selectors['serial'][$key]);

		$record_selectors[$i]['table_name'] = $one_record_selector['table_name'];
		$record_selectors[$i]['conditions'] = $one_record_selector['conditions'];
		$record_selectors[$i]['ctnr_ids'] = $temp_record_selectors['ctnr_ids'][$key];
		$i++;
	}
	unset ($temp_record_selectors);
	//echo trace2web($record_selectors, "record_selectors reformated the array in a good maner :");

	return $record_selectors;
}

function input_data_validator ($delupsert_list)
{
	$delete = array ();
	$insert = array ();
	$update = array ();

	foreach ($delupsert_list as $manipulation => $data_list)
	{
		if (
			$manipulation === "insert"
			|| $manipulation === "update"
		)
		// of-course, there is no data_calidator for delete queries
		{
			$$manipulation = input_data_validator_per_manipulation ($data_list);
		}
	}

	$delupsert_list = array (
		'delete' => $delupsert_list['delete']
		, 'insert' => $insert
		, 'update' => $update
	);

	return $delupsert_list;
}

/**
 * This function should be private. 
 */
function input_data_validator_per_manipulation ($data_list)
{
	$temp_data_list = $data_list;
	$data_list = array ();
	foreach ($temp_data_list as $ctrn_id => $meta_and_data)
	{
		if ($temp_data_list[$ctrn_id]['metadata']['horizontal_access'] === true)
		{
			$data_list[$ctrn_id] = get_data_field_validated ($meta_and_data);
		}
		else
		{
			$data_list[$ctrn_id] = $temp_data_list[$ctrn_id];
		}
	}

	return $data_list;
}

/**
 * This function should be private. 
 */
function get_data_field_validated ($meta_and_data)
{
	$table_name = $meta_and_data['data_itself']['table_name'];

	foreach ($meta_and_data['data_itself']['fields'] as $field_name => $value_to_be_checked) // there is only one element in the array, but an array is necessary for the api.
	{
		if (exists_and_not_empty ($GLOBALS['data_validator'][$table_name][$field_name]['input']['server']))
		{
			$checking_metadata = $GLOBALS['data_validator'][$table_name][$field_name]['input']['server'] ($value_to_be_checked); // missing data, or bad format...
			$meta_and_data['metadata'] = array_merge (
				$meta_and_data['metadata']
				, $checking_metadata
			);
			$meta_and_data['data_itself']['fields'][$field_name] = $checking_metadata['checked_value'];
		}
		else
		{
			$meta_and_data['metadata']['valid'] = true;
			$meta_and_data['metadata']['checked_value'] = $value_to_be_checked;
			$meta_and_data['metadata']['returnMessage'] = "";
		}
	}

	return $meta_and_data;
}


/**
 * After having used the datamalico_server_dbquery::select_empty() method (and using its temp_insert_id parameter) to get an empty row 
 * to save a new record, you have a TEMPINSERTID as criteria in order to make the datamalico_server_dbquery::upsert() ; this function 
 * get_rid_of_TEMPINSERTID () makes the first inser_api() and replace the TEMPINSERTID with the just inserted id.
 *
 * @param $delupsert_list {associative array} (mandatory) is the delupsert_list.
 * @param &$TEMPINSERTID_insertion is a reference on the result of the insertion.
 *
 * @return $delupsert_list {associative array} (mandatory) is the delupsert_list in which all TEMPINSERTID has been replaced by the just inserted id.
 *
 * @warning this function may use recursivity and call itself in case of several TEMPINSERTID)
 */
function get_rid_of_TEMPINSERTID ($delupsert_list, &$TEMPINSERTID_insertion)
{
	//echo trace2web (__FUNCTION__ . ":" . __LINE__);

	$table_name;
	$fields;
	$old_value;
	$new_value;

	//trace2file ("", "", __FILE__, true);
	//trace2file ($delupsert_list, "delupsert_list", __FILE__);

	$has_reached_the_end_of_array = true;
	foreach ($delupsert_list['update'] as $field => $meta_and_data)
	{
		foreach ($meta_and_data['data_itself']['conditions'] as $cdn => $cdv)
		{
			$findme = "TEMPINSERTID_"; // This TEMPINSERTID_ is added in order to be abble to insert  and make the link between a record entity and its records of one of its join tables
			$pos = strpos($cdv, $findme);
			if ($pos === 0) // If the condition value begins by $findme (position 0) then it is a temporary insert id, and this must be inserted and not updated
			{
				$table_name = $delupsert_list['update'][$field]['data_itself']['table_name'];
				$fields = $delupsert_list['update'][$field]['data_itself']['fields'];
				$old_value = $cdv;

				$insert_api_config = array (
					'table_name' => $table_name
					, 'fields' => $fields
					, 'calling_FILE' => __FILE__
					, 'calling_LINE' => __LINE__
				);
				$insert_res = insert_api ($insert_api_config);
				$TEMPINSERTID_insertion[] = $insert_res;
				$new_value = (string) $insert_res['insert_api']['metadata']['insert_id'];
				//echo trace2web($insert_res, "insert_res");

				$old_value_length = strlen($old_value);
				$new_value_length = strlen($new_value);

				$delupsert_list_serial = serialize($delupsert_list);
				$delupsert_list_serial = str_replace("s:$old_value_length:\"$old_value\"", "s:$new_value_length:\"$new_value\"", $delupsert_list_serial);
				$delupsert_list = unserialize ($delupsert_list_serial);

				$has_reached_the_end_of_array = false;
				break 2;
			}
		}
	}

	if ($has_reached_the_end_of_array === false)
	{
		//trace(__FUNCTION__ . ":" . __LINE__);
		return get_rid_of_TEMPINSERTID ($delupsert_list, $TEMPINSERTID_insertion);
	}
	else
	{
		//trace(__FUNCTION__ . ":" . __LINE__);
		//echo trace2web ($delupsert_list, "delupsert_list OUTPUT");
		return $delupsert_list;
	}
}

function are_there_invalid_data ($delupsert_list)
{
	$are_there_invalid_data = false;
	foreach ($delupsert_list as $manipulation => $data_list)
	{
		foreach ($delupsert_list[$manipulation] as $ctrn_id => $meta_and_data)
		{
			if (
				$meta_and_data['metadata']['horizontal_access'] === false
				|| $meta_and_data['metadata']['valid'] === false
			)
			{
				$are_there_invalid_data = true;
				break;
			}
		}
	}

	return $are_there_invalid_data;
}

function reformat_delete_list ($delete)
{
	$temp_delete = $delete;
	$delete = array ();

	//echo trace2web($temp_delete, "temp_delete");
	$record_selectors = get_record_selectors ($temp_delete);
	//echo trace2web($record_selectors, "record_selectors");

	foreach ($record_selectors as $i => $one_record_selector)
	{
		$table = $record_selectors[$i]['table_name'];
		$conditions = $record_selectors[$i]['conditions'];

		$delete[$i] = array (
			'table_name' => $table
			, 'conditions' => $conditions
		);
	}

	//echo trace2web($delete, "delete");
	return $delete;
}

function reformat_insert_list ($insert)
{
	$temp_insert = $insert;
	$insert = array ();
	//echo trace2web($temp_insert, "temp_insert");

	foreach ($temp_insert as $ctrn_id => $meta_and_data)
	{
		$table_name = $meta_and_data['data_itself']['table_name'];

		$insert[$table_name]['table_name'] = $table_name;
		if (!exists_and_not_empty ($insert[$table_name]['fields']))
		{
			$insert[$table_name]['fields'] = array();
		}

		foreach ($meta_and_data['data_itself']['fields'] as $j => $field_to_be_upserted)
		{
			$insert[$table_name]['fields'] = array_merge (
				$insert[$table_name]['fields']
				, $meta_and_data['data_itself']['fields']
			);
		}
	}

	//echo trace2web($insert, "insert");
	return $insert;
}

function reformat_update_list ($update)
{
	$temp_update = $update;
	$update = array ();

	//echo trace2web($temp_update, "temp_update");
	$record_selectors = get_record_selectors ($temp_update);
	//echo trace2web($record_selectors, "record_selectors");

	foreach ($record_selectors as $i => $one_record_selector)
	{
		$table = $record_selectors[$i]['table_name'];
		$fields = array ();

		foreach ($record_selectors[$i]['ctnr_ids'] as $j => $ctnr_id)
		{
			$fields = array_merge ($fields, $temp_update[$ctnr_id]['data_itself']['fields']);
		}
		$conditions = $record_selectors[$i]['conditions'];

		$update[$i] = array (
			'table_name' => $table
			, 'fields' => $fields
			, 'conditions' => $conditions
		);
	}

	//echo trace2web($update, "update");
	return $update;
}

/**
 * Prepares an sql string according to parameters
 */
function dco_select_api ($select_api_config)
{
	$sql;

	// #########################
	// Prepares the select clause
	$sql = "SELECT\n";
	$nb_field = count ($select_api_config['fields']);
	$i=1;
	foreach ($select_api_config['fields'] as $field_name)
	{
		$sql .= "$field_name\n";

		if ($i < $nb_field)
		{
			$sql .= ", ";
		}
		$i++;
	}

	// #########################
	// Prepares the from clause
	$sql .= "FROM\n";
	$nb_tables = count ($select_api_config['table_names']);
	$i=1;
	foreach ($select_api_config['table_names'] as $table_name)
	{
		$sql .= "$table_name\n";

		if ($i > 1)
		{
			$sql .= " ON ";
			// get relationship --> $GLOBALS['relationship']['data_registered'];
			if (exists_and_not_empty ($GLOBALS['relationship'][$table_name]['many_to_many']))
			{
				// get_autoincrement_field ($table_name)
			}
		}
		if ($i < $nb_tables)
		{
			$sql .= "INNER JOIN ";
		}

		$i++;
	}

	// #########################
	// Prepares the where clause

	trace("dco_select_api: $sql");
}


/**
 * Function get_SELECT_clause. If you send an array with select clause components, you'll get a string.
 * @param fields_array {numerical array} 
 * @return a string with the correct number of commas between field names.
 */
function get_SELECT_clause ($fields_array)
{
	//echo trace2web($fields_array, "fields_array");
	$field_string;
	$nb_field = count ($fields_array);
	$i=1;
	foreach ($fields_array as $field_name)
	{
		$field_string .= "$field_name\n";
		if ($i < $nb_field)
		{
			$field_string .= ", ";
		}

		$i++;
	}

	//echo trace2web($field_string, "field_string");
	return $field_string;
}

/**
 * Get the FROM clause according to a $tables numerical array and relying on the relationship.conf.php file.
 *
 * @param $tables (mandatory) {numerical array} Array of table names.
 *
 * @return Return a string.
 */
function get_FROM_clause ($tables)
{
	//echo trace2web ($tables, "get_FROM_clause() tables");

	$from_clause = "";
	$table_list_1 = $tables;
	$table_list_2 = $tables;

	$avoid_table_redundancy = array();
	// loop through each table
	//foreach ($tables as $i => $table_1)
	while (list($i, $table_1) = each($table_list_1)) // there is here a while instead of a foreach, because, there will be array_push during the loop
	{
		//echo trace2web ($table_1, "table_1: table_list_1[$i]");
		if ($i === 0)
		{
			$from_clause .= "$table_1\n";
			$avoid_table_redundancy[$table_1] = true;
			//echo trace2web ("NO MORE $table_1");
		}


		// loop through each associated table
		foreach ($table_list_2 as $j => $table_2)
			//while (list($j, $table_2) = each($tables))
		{
			//echo trace2web ($table_2, "	table_2: table_list_2[$j]");
			$join_has_been_found = false;
			$join_part_1;
			$join_part_2;

			// avoid redundancy on the table itself
			//if ($table_1 !== $table_2)
			if (!isset($avoid_table_redundancy[$table_2]))
			{
				//echo trace2web ("	Can add $table_2");
				//echo trace2web ("	Can link $table_1 with $table_2");
				// many_to_one:
				//if ($join_has_been_found === false)
				//{

				if (exists_and_not_empty ($GLOBALS['relationship'][$table_1]['many_to_one']))
				{
					//echo trace2web ("many_to_one");
					foreach ($GLOBALS['relationship'][$table_1]['many_to_one'] as $key_relation_to_table_2 => $val_relation_to_table_2)
					{
						//echo trace2web ($val_relation_to_table_2, "val_relation_to_table_2");
						if ($key_relation_to_table_2 === $table_2)
						{
							$join_part_1 = $val_relation_to_table_2;

							if (exists_and_not_empty ($GLOBALS['relationship'][$table_2]['one_to_many'][$table_1]))
							{
								$join_part_2 = $GLOBALS['relationship'][$table_2]['one_to_many'][$table_1];
								$from_clause_locale = "INNER JOIN $table_2 ON $join_part_2 = $join_part_1\n";
								$from_clause .= $from_clause_locale;
								$avoid_table_redundancy[$table_2] = true;
								//echo trace2web ("	NO MORE $table_2");
								//echo trace2web ("	" . $from_clause_locale);

								$join_has_been_found = true;
								//echo trace2web ("	unset: " . $tables[$j]);
								//unset ($tables[$j]);
							}
							else
							{
								new mil_Exception (
									__FUNCTION__ . " :  As there is a GLOBALS['relationship'][$table_1]['many_to_one'][$table_2], then there must be the other part of the relation GLOBALS['relationship'][$table_2]['one_to_many'][$table_1] in relationship.conf.php, but there is not. Please correct it or the join can not done."
									, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
							}
						}
					}
				}
				//}

				// one_to_many:
				if ($join_has_been_found === false)
				{
					if ($GLOBALS['relationship'][$table_1]['one_to_many'])
					{
						//echo trace2web ("one_to_many");
						foreach ($GLOBALS['relationship'][$table_1]['one_to_many'] as $key_relation_to_table_2 => $val_relation_to_table_2)
						{
							//echo trace2web ($val_relation_to_table_2, "val_relation_to_table_2");
							if ($key_relation_to_table_2 === $table_2)
							{
								$join_part_1 = $val_relation_to_table_2;

								if (exists_and_not_empty ($GLOBALS['relationship'][$table_2]['many_to_one'][$table_1]))
								{
									$join_part_2 = $GLOBALS['relationship'][$table_2]['many_to_one'][$table_1];
									$from_clause_locale = "INNER JOIN $table_2 ON $join_part_2 = $join_part_1\n";
									$from_clause .= $from_clause_locale;
									$avoid_table_redundancy[$table_2] = true;
									//echo trace2web ("	NO MORE $table_2");
									//echo trace2web ("	" . $from_clause_locale);

									$join_has_been_found = true;
									//echo trace2web ("	unset: " . $tables[$j]);
									//unset ($tables[$j]);
								}
								else
								{
									new mil_Exception (
										__FUNCTION__ . " :  As there is a GLOBALS['relationship'][$table_1]['one_to_many'][$table_2], then there must be the other part of the relation GLOBALS['relationship'][$table_2]['many_to_one'][$table_1] in relationship.conf.php, but there is not. Please correct it or the join can not done."
										, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
								}
							}
						}
					}
				}

				// many_to_many:
				if ($join_has_been_found === false)
				{
					if ($GLOBALS['relationship'][$table_1]['many_to_many'])
					{
						//echo trace2web ("many_to_many");
						foreach ($GLOBALS['relationship'][$table_1]['many_to_many'] as $key_relation_to_table_2 => $val_relation_to_table_2)
						{
							//echo trace2web ($val_relation_to_table_2, "val_relation_to_table_2");
							if ($key_relation_to_table_2 === $table_2)
							{
								$join_table = $GLOBALS['relationship'][$table_1]['many_to_many'][$table_2]['join_table'];

								if (!isset($avoid_table_redundancy[$join_table]))
								{

									// First: link from the table_1 to the join table
									$join_part_1 = $GLOBALS['relationship'][$table_1]['one_to_many'][$join_table];
									$join_part_2 = $GLOBALS['relationship'][$join_table]['many_to_one'][$table_1];
									$from_clause_locale = "INNER JOIN $join_table ON $join_part_2 = $join_part_1\n";
									$from_clause .= $from_clause_locale;
									$avoid_table_redundancy[$join_table] = true;
									//echo trace2web ("	NO MORE $table_2");
									//echo trace2web ("	" . $from_clause_locale);

									// Second: link from the join table to the table_2
									array_push ($table_list_1, $join_table);
									array_push ($table_list_2, $join_table);
								/*
								$join_part_1 = $GLOBALS['relationship'][$join_table]['many_to_one'][$table_2];
								$join_part_2 = $GLOBALS['relationship'][$table_2]['one_to_many'][$join_table];
								$from_clause_locale = "INNER JOIN $table_2 ON $join_part_2 = $join_part_1\n";
								$from_clause .= $from_clause_locale;
								$avoid_table_redundancy[$join_table] = true;
								//echo trace2web ("	NO MORE $table_2");
								//echo trace2web ("	" . $from_clause_locale);
								 */


									$join_has_been_found = true;
									//echo trace2web ("	unset: " . $tables[$j]);
									//unset ($tables[$j]);
								}
							}
						}
					}
				}
			}
		}
	}

	//echo trace2web($from_clause);
	return $from_clause;
}

/**
 * Get a full string with AND conditions of a WHERE clause of a SQL query, relying on an associative array of conditions.
 * Each condition is escaped, and each condition is put into simple quotes, so that there must be no problem, even integers and decimals can be included into quotes.
 * 
 * @warning Remark: This function is very similar to datamalico_server_dbquery::get_full_string_AND_condition() but is newer.
 *
 * @param $conditions (optional) {associative array} Conditions
 * 	
 * @return {string} The full string condition.
 *
 * Example of $this_mil_page->page_params['select']['where']
 * @code
 * this_mil_page->page_params: Array
 * (
 *     [select] => Array
 *         (
 *             [where] => Array
 *                 (
 *                     [zipcode] => Array
 *                         (
 *                             [t] => data_registered
 *                             [c] => Array
 *                                 (
 *                                     [zipcode] => 57*** 54***
 *                                 )
 *                             [g] => Array
 *                                 (
 *                                     [name] => default
 *                                     [parent] => none
 *                                     [join_op] => AND
 *                                 )
 *                         )
 *                     [country_id] => Array
 *                         (
 *                             [t] => data_registered
 *                             [c] => Array
 *                                 (
 *                                     [country_id] => 76
 *                                 )
 *                             [g] => Array
 *                                 (
 *                                     [name] => default
 *                                     [parent] => none
 *                                     [join_op] => AND
 *                                 )
 *                         )
 *                     [1_reg_id_20130401184553676] => Array
 *                         (
 *                             [t] => data_registered_2_profession
 *                             [c] => Array
 *                                 (
 *                                     [reg_id] => 
 *                                     [profession_id] => 1
 *                                 )
 *                             [g] => Array
 *                                 (
 *                                     [name] => professions
 *                                     [parent] => default
 *                                     [join_op] => OR
 *                                 )
 *                         )
 *                     [3_reg_id_20130401184553693] => Array
 *                         (
 *                             [t] => data_registered_2_profession
 *                             [c] => Array
 *                                 (
 *                                     [reg_id] => 
 *                                     [profession_id] => 2
 *                                 )
 *                             [g] => Array
 *                                 (
 *                                     [name] => professions
 *                                     [parent] => default
 *                                     [join_op] => OR
 *                                 )
 *                         )
 *                 )
 *         )
 * )
 *
 *
 * // And here is the SQL string result:
 * SELECT ...
 * FROM ...
 * WHERE 
 * # Condition Group: default
 * (
 * 	data_registered.zipcode REGEXP '57...|54...'
 * 	AND data_registered.country_id = '76'
 * 	AND 
 * 	# Condition Group: professions
 * 	(
 * 		data_registered_2_profession.profession_id = '1'
 * 		OR data_registered_2_profession.profession_id = '2'
 * 	)
 * )
 * @endcode
 */
function get_WHERE_clause ($conditions, $authorized_conditions)
{
	//echo trace2web ("get_WHERE_clause()");
	//echo trace2web ($conditions, "conditions");

	$full_string_condition = "";

	if (exists_and_not_empty ($conditions))
	{
		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		// if array or string
		if (gettype ($conditions) === "array")
		{

			// make groups:

/*
			$conditions['1_reg_id_20130401184553676']['g'] = array (
				'name' => "professions"
				, 'parent' => "default"
				, 'join_op' => "OR"
			);
			$conditions['3_reg_id_20130401184553693']['g'] = array (
				'name' => "professions"
				, 'parent' => "default"
				, 'join_op' => "OR"
);*/
			$cond_groups = array();
			foreach ($conditions as $html_container => $condition)
			{
				// if there is no condition, then the criteria is of no use, and can be deleted:
				if (!exists_and_not_empty ($condition['c'])) unset ($conditions[$html_container]);
				else
				{
					$conditions[$html_container]['g'] = replace_leaves_keep_all_branches ( // 'g' like group
						$conditions[$html_container]['g']
						, $GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']['research_operators']['cond_group']
					);
					$group_name = $conditions[$html_container]['g']['name'];
					$conditions[$group_name][$html_container] = $conditions[$html_container];
					unset ($conditions[$html_container]);

					// check integrity of params:
					if (
						strtolower($conditions[$group_name][$html_container]['g']['join_op']) !== "and"
						&& strtolower($conditions[$group_name][$html_container]['g']['join_op']) !== "or"
					)
					{
						$conditions[$group_name][$html_container]['g']['join_op'] = "AND";
					}

					// create the list of condition groups. Grouping by parent is necessary in order to list all the children of one parent:
					$parent = $conditions[$group_name][$html_container]['g']['parent'];
					$cond_groups[$parent][$group_name] = array (
						'name' => $conditions[$group_name][$html_container]['g']['name']
						, 'parent' => $conditions[$group_name][$html_container]['g']['parent']
						, 'join_op' => $conditions[$group_name][$html_container]['g']['join_op']
						, 'oper_opt' => $conditions[$group_name][$html_container]['g']['oper_opt']
					);
				}
			}

			//echo trace2web ($conditions, "conditions");
			//echo trace2web ($cond_groups, "cond_groups");

			//$group_info = $cond_groups['none']['default'];

			//trace2file ("", "", __FILE__, true);

			$group_info = exists_and_not_empty($cond_groups['none']['default']) ? $cond_groups['none']['default'] : $GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']['research_operators']['cond_group'];

			$full_string_condition = get_WHERE_group ($group_info, $conditions, $cond_groups, $mysqli_con);
		}
		else
		{
			new mil_Exception (
				__FUNCTION__ . " : Vars conditions is not an array, please check."
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			$function_return['metadata']['returnCode'] = "ERROR";
			$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
		}

		//$mysqli_con->close();
	}

	return $full_string_condition;
}

/**
 * Returns the string corresponding to a group in a SQL WHERE clause. This function is mainly used by get_WHERE_clause().
 *
 * Example of what are $conditions and $cond_groups:
 * @code
 * conditions: Array
 * (
 *     [default] => Array
 *         (
 *             [zipcode] => Array
 *                 (
 *                     [t] => data_registered
 *                     [c] => Array
 *                         (
 *                             [zipcode] => 57...|54...
 *                         )
 *                     [o] => Array
 *                         (
 *                             [zipcode] => regexp
 *                         )
 *                     [g] => Array
 *                         (
 *                             [name] => default
 *                             [parent] => none
 *                             [join_op] => AND
 *                         )
 *                 )
 *             [country_id] => Array
 *                 (
 *                     [t] => data_registered
 *                     [c] => Array
 *                         (
 *                             [country_id] => 76
 *                         )
 *                     [g] => Array
 *                         (
 *                             [name] => default
 *                             [parent] => none
 *                             [join_op] => AND
 *                         )
 *                 )
 *         )
 *     [professions] => Array
 *         (
 *             [1_reg_id_20130401184553676] => Array
 *                 (
 *                     [t] => data_registered_2_profession
 *                     [c] => Array
 *                         (
 *                             [reg_id] => 
 *                             [profession_id] => 1
 *                         )
 *                     [g] => Array
 *                         (
 *                             [name] => professions
 *                             [parent] => default
 *                             [join_op] => OR
 *                         )
 *                 )
 *             [3_reg_id_20130401184553693] => Array
 *                 (
 *                     [t] => data_registered_2_profession
 *                     [c] => Array
 *                         (
 *                             [reg_id] => 
 *                             [profession_id] => 2
 *                         )
 *                     [g] => Array
 *                         (
 *                             [name] => professions
 *                             [parent] => default
 *                             [join_op] => OR
 *                         )
 *                 )
 *         )
 * )
 * cond_groups: Array
 * (
 *     [none] => Array
 *         (
 *             [default] => Array
 *                 (
 *                     [name] => default
 *                     [parent] => none
 *                     [join_op] => AND
 *                 )
 *         )
 *     [default] => Array
 *         (
 *             [professions] => Array
 *                 (
 *                     [name] => professions
 *                     [parent] => default
 *                     [join_op] => OR
 *                 )
 *         )
 * )
 * @endcode
 */
function get_WHERE_group ($group_info, $conditions, $cond_groups, &$mysqli_con)
{
	//trace2file ($group_info, "group_info", __FILE__);
	//trace2file (gettype($group_info), "type group_info", __FILE__);
	//trace2file ($conditions[$group_info['name']], "conditions[".$group_info['name']."]", __FILE__);
	//trace2file ($conditions, "conditions", __FILE__);
	//trace2file (gettype($conditions[$group_info['name']]), "type conditions...", __FILE__);
	//trace2file ($cond_groups, "cond_groups", __FILE__);

	$conditions['default'] = exists_and_not_empty($conditions['default']) ? $conditions['default'] : array();

	// $conditions[$group_info['name']];
	//
	// foreach ($cond_groups[$group_info['name']] as $child_name => $child_info)
	// {
	// 	$tmp_conditions_array = get_WHERE_group ($child_info, $conditions, $cond_groups, &$mysqli_con);
	// }
	// return;


	//echo trace2web ("--------------------- get_WHERE_group () ----------------------");
	//echo trace2web ($group_info['name']);

	$tmp_conditions_array = array();
	$full_string_condition = "";
	//foreach ($conditions as $full_field_name => $condition_op_and_val)
	//foreach ($conditions as $html_container => $condition)
	foreach ($conditions[$group_info['name']] as $html_container => $condition)
	{
		//echo trace2web ($condition, "condition");
		if (
			exists_and_not_empty ($condition['t'])
			//&& exists_and_not_empty ($condition['c'])
		)
		{
			$table_name = $condition['t'];

			foreach ($condition['c'] as $field_name => $search_value)
			{
				//echo trace2web ("------------------");
				//echo trace2web ($field_name, "field_name");
				//echo trace2web ($search_value, "search_value: " .__LINE__);
				$search_value_is_not_empty = $search_value !== "" ? true : false;
				$research_criteria_is_valid = false;

				$can_vertically_access_field;
				$vertical_field_name;
				if (preg_match("/\_MAX$/", $field_name)) $vertical_field_name = preg_replace("/\_MAX$/", "", $field_name);
				else $vertical_field_name = $field_name;
				$can_vertically_access_field = can_vertically_access_field ( array (
					'manipulation' => "select_where"
					, "field_name" => $vertical_field_name
					, 'field_infos' => array (
						'field_direct' => array (
							'table' => $table_name
						)
					)
				));

				//$authorized_conditions;
				$is_authorized_cond = true;
				if (exists_and_not_empty ($authorized_conditions))
				{
					$is_authorized_cond = false;
					foreach ($authorized_conditions as $index => $auth_cond)
					{
						if ($table_name . "." . $field_name === $auth_cond)
						{
							$is_authorized_cond = true;
						}
					}
				}

				// if there is NO data_validator for this research_criteria:
				if (!exists_and_not_empty ($GLOBALS['data_validator'][$table_name][$field_name]['research_criteria']))
				{
					$research_criteria_is_valid = true;
				}

				// if there is a data_validator for this research_criteria:
				else
				{

					// Data Validator, Research Criteria:
					$DVRC = $GLOBALS['data_validator'][$table_name][$field_name]['research_criteria'] ($search_value);
					if ($DVRC['valid'] === true)
					{
						$research_criteria_is_valid = true;
						$search_value = $DVRC['checked_value'];
					}
				}

				//echo trace2web ($search_value_is_not_empty, "search_value_is_not_empty");
				//echo trace2web ($research_criteria_is_valid, "research_criteria_is_valid");
				//echo trace2web ($can_vertically_access_field, "can_vertically_access_field");
				//echo trace2web ($is_authorized_cond, "is_authorized_cond");

				if (
					$search_value_is_not_empty === true
					&& $research_criteria_is_valid === true
					&& $can_vertically_access_field === true
					&& $is_authorized_cond === true
				)
				{
					//echo trace2web('Can be added as condition');
					//echo trace2web($condition, "condition");
					//echo trace2web ("Can be added as condition - $full_field_name:[$search_value]");

					$full_field_name = $table_name . "." . $vertical_field_name;

					// ##########################
					// Get field type:
					// 	The type is necessary to know 
					// 		- if we can use the operator like (for strings) or = (for others)
					// 		- if the right hand expression must be surrounded by wildcard markers %

					$sql = "
						SELECT DATA_TYPE 
						FROM INFORMATION_SCHEMA.columns 
						WHERE TABLE_NAME = '$table_name'
						AND COLUMN_NAME = '$field_name'
						";

					$data_type;

					if ($mysqli_result = $mysqli_con->query($sql))
					{
						//$nbRes = $mysqli_result->num_rows;	// SELECT
						$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

						if ($nbRes === 1)
						{ 
							$row = $mysqli_result->fetch_row();
							$data_type = $row[0];
						}
						else
						{
							new mil_Exception (__FUNCTION__ . " : There should be only one result for: $sql", "1201111240", "WARN"
								, __FILE__ .":". __LINE__ );
						}

						$mysqli_result->free();
					}
					else
					{
						new mil_Exception ("This is not possible to execute the request: $sql, " 
							. trace2web($mysqli_con->error, "mysqli_con->error")
							, "1201111240", "WARN", __FILE__ .":". __LINE__ );
						//echo trace2web($mysqli_con->error, "mysqli_con->error");
					}

					// ##########################
					// if the field is known and its type has been found:
					if (exists_and_not_empty ($data_type))
					{
						$quote = "'";
						//$right_hand_expressions;

						// String fields
						if (
							$data_type === "varchar"
							|| $data_type === "text"
							|| $data_type === "mediumtext"
							|| $data_type === "longtext"
						)
						{
							//echo trace2web ("$full_field_name:[$search_value]");
							$quote = "'";

							$expr = $search_value;
							$op;

							// Is the string an exact expression? (surrounded by double quotes)
							$exact_expr = false;
							$first_char = substr($expr, 0, 1);
							$last_char = substr($expr, -1, 1);
							//echo trace2web ($first_char, "first_char");
							//echo trace2web ($last_char, "last_char");
							if ($first_char === '"' && $last_char === '"')
							{
								$exact_expr = true;
							}
							//echo trace2web ($exact_expr, "exact_expr");

							// #####################
							// set operator:
							if (exists_and_not_empty ($condition['o']))
							{
								switch ($condition['o'][$vertical_field_name]) {
								case 'like':
									$op = 'LIKE';
									break;
								case 'notlike':
									$op = 'NOT LIKE';
									break;
								case 'eq':
									$op = '=';
									break;
								case 'noteq':
									$op = '<>';
									break;
								case 'lt':
									$op = '<';
									break;
								case 'gt':
									$op = '>';
									break;
								case 'lt_or_eq':
									$op = '<=';
									break;
								case 'gt_or_eq':
									$op = '>=';
									break;
								case 'betw':
									if (preg_match("/\_MAX$/", $field_name)) $op = '<=';
									else $op = '>=';
									break;
								case 'regexp':
									$op = 'REGEXP';
									break;
								case 'notregexp':
									$op = 'NOT REGEXP';
									break;
								case 'begins':
									$op = 'begins';
									break;
								case 'notbegins':
									$op = 'notbegins';
									break;
								case 'ends':
									$op = 'ends';
									break;
								case 'notends':
									$op = 'notends';
									break;
								default:
									$op = 'LIKE';
								}								
							}
							else
							{
								$op = 'LIKE';
							}


							// #####################
							// Specify the $tmp_conditions_array[]:
							// if is LIKE  and without double quotes:
							if (
								$op === 'LIKE'
								&& $exact_expr === false
							)
							{
								$tmp_conditions_array = array_merge (
									get_one_intelligent_condition ( array (
										'full_field_name' => $full_field_name
										, 'op' => $op
										, 'expr' => $expr
										, 'quote' => $quote
										, 'oper_opt' => $condition['g']['oper_opt']
									))
									, $tmp_conditions_array
								);								
							}
							else
							{
								if ($op === 'LIKE') $op = '=';

								if ($op === 'begins')
								{
									$op = "REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";
								}
								else if ($op === 'notbegins')
								{
									$op = "NOT REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";
								}
								else if ($op === 'ends')
								{
									$op = "REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";
								}
								else if ($op === 'notends')
								{
									$op = "NOT REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";
								}

								// For any other operator REGEXP included:
								else
								{
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
								}
							}
						}

						// Numeric fields
						else if (
							$data_type === "tinyint"
							|| $data_type === "smallint"
							|| $data_type === "mediumint"
							|| $data_type === "int"
							|| $data_type === "bigint"
							|| $data_type === "decimal"
						)
						{
							$quote = "'";
							$op;

							// #####################
							// Set operator:
							if (exists_and_not_empty ($condition['o']))
							{
								switch ($condition['o'][$vertical_field_name]) {
								case 'like':
									$op = 'LIKE';
									break;
								case 'notlike':
									$op = 'NOT LIKE';
									break;
								case 'eq':
									$op = '=';
									break;
								case 'noteq':
									$op = '<>';
									break;
								case 'lt':
									$op = '<';
									break;
								case 'gt':
									$op = '>';
									break;
								case 'lt_or_eq':
									$op = '<=';
									break;
								case 'gt_or_eq':
									$op = '>=';
									break;
								case 'betw':
									if (preg_match("/\_MAX$/", $field_name)) $op = '<=';
									else $op = '>=';
									break;
								case 'regexp':
									$op = 'REGEXP';
									break;
								case 'notregexp':
									$op = 'NOT REGEXP';
									break;
								case 'begins':
									$op = 'begins';
									break;
								case 'notbegins':
									$op = 'notbegins';
									break;
								case 'ends':
									$op = 'ends';
									break;
								case 'notends':
									$op = 'notends';
									break;
								default:
									$op = '=';
								}
							}
							else
							{
								$op = '=';
							}

							// #####################
							// Specify the $tmp_conditions_array[]:
							if ($op === 'begins')
							{
								$op = "REGEXP";
								$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";
							}
							else if ($op === 'notbegins')
							{
								$op = "NOT REGEXP";
								$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";
							}
							else if ($op === 'ends')
							{
								$op = "REGEXP";
								$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";
							}
							else if ($op === 'notends')
							{
								$op = "NOT REGEXP";
								$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";
							}

							// For any other operator REGEXP included:
							else
							{
								//echo trace2web ($condition['o'][$vertical_field_name]);
								//echo trace2web ("$full_field_name $op $quote$search_value$quote");
								$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
							}
						}

						// Time fields
						else if (
							$data_type === "datetime"
							|| $data_type === "timestamp"
						)
						{
							$quote = "'";
							$op;

							// #####################
							// Set operator:
							// AND Specify the $tmp_conditions_array[]:
							if (exists_and_not_empty ($condition['o']))
							{
								switch ($condition['o'][$vertical_field_name]) {
								case 'like':
									$op = 'LIKE';
									$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
									break;
								case 'notlike':
									$op = 'NOT LIKE';
									$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
									break;
								case 'eq':
									$op = 'LIKE';
									$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
									break;
								case 'noteq':
									$op = 'NOT LIKE';
									$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
									break;
								case 'lt':
									$op = '<';
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
									break;
								case 'gt':
									$op = '>';

									$unixdate = strtotime($search_value); // eg: "2012-11-30"
									$h=24; $min=60; $sec=60;
									$one_second = 1;
									$unixdate_day_plus_one = $unixdate + ($sec * $min * $h) - $one_second;
									$isodate = date("Y-m-d H:i:s", $unixdate_day_plus_one);
									$tmp_conditions_array[] = "$full_field_name $op $quote$isodate$quote";

									break;
								case 'lt_or_eq':
									// because in MySQL "datefield <= '2012-12-08'" doesn't work 
									// and is like "datefield < '2012-12-09'"
									$op = '<=';

									$unixdate = strtotime($search_value); // eg: "2012-11-30"
									$h=24; $min=60; $sec=60;
									$one_second = 1;
									$unixdate_day_plus_one = $unixdate + ($sec * $min * $h) - $one_second;
									$isodate = date("Y-m-d H:i:s", $unixdate_day_plus_one);
									$tmp_conditions_array[] = "$full_field_name $op $quote$isodate$quote";

									break;
								case 'gt_or_eq':
									$op = '>=';
									$unixdate = strtotime($search_value); // eg: "2012-11-30"
									$isodate = date("Y-m-d H:i:s", $unixdate);
									$tmp_conditions_array[] = "$full_field_name $op $quote$isodate$quote";
									//$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
									break;
								case 'betw':
									if (preg_match("/\_MAX$/", $field_name))
									{
										$op = '<';

										$unixdate = strtotime($search_value); // eg: "2012-11-30"
										$h=24; $min=60; $sec=60;
										$one_second = 1;
										$unixdate_day_plus_one = $unixdate + ($sec * $min * $h) - $one_second;
										$isodate = date("Y-m-d H:i:s", $unixdate_day_plus_one);
										$tmp_conditions_array[] = "$full_field_name $op $quote$isodate$quote";
									}
									else
									{
										$op = '>=';
										$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
									}
									break;
								case 'regexp':
									$op = 'REGEXP';
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
									break;
								case 'notregexp':
									$op = 'NOT REGEXP';
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value$quote";
									break;
								case 'begins':
									$op = 'REGEXP';
									$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";
									break;
								case 'notbegins':
									$op = 'NOT REGEXP';
									$tmp_conditions_array[] = "$full_field_name $op $quote^$search_value$quote";	
									break;
								case 'ends':
									$op = "REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";	
									break;
								case 'notends':
									$op = "NOT REGEXP";
									$tmp_conditions_array[] = "$full_field_name $op $quote$search_value"."$"."$quote";
									break;
								default:
									$op = 'LIKE';
								}								
							}
							else
							{
								$op = 'LIKE';
								$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
							}

							//$tmp_conditions_array[] = "$full_field_name $op $quote%$search_value%$quote";
						}

						else
						{
							new mil_Exception (__FUNCTION__ . " : Hey there is the type '$data_type' that you should consider: $sql"
								, "1201111240", "WARN", __FILE__ . ":" . __LINE__);
						}
					}
				}
			}
		}
	}

	//echo trace2web ($tmp_conditions_array, "tmp_conditions_array 1");

	if (exists_and_not_empty ($cond_groups[$group_info['name']]))
	{
		foreach ($cond_groups[$group_info['name']] as $child_name => $child_info)
		{
			$tmp_conditions_array[] = get_WHERE_group ($child_info, $conditions, $cond_groups, $mysqli_con);
		}
	}

	if (exists_and_not_empty ($tmp_conditions_array)) $full_string_condition = "\n# Condition Group: " . $group_info['name'] . get_ANDOR_condition ($tmp_conditions_array, $group_info['join_op']);
	else $full_string_condition = "1";	// necessary for a group with no condition: "WHERE 1"

	//echo trace2web ($tmp_conditions_array, "tmp_conditions_array 2");

	//trace2file ($full_string_condition, "full_string_condition", __FILE__);
	return $full_string_condition;
}

/**
 * Return an intelligent condition as an array, in order to make a SQL condition.
 *
 * @param params: {associative array} (mandatory)
 * 	- full_field_name: {string} (mandatory) the full field name, eg: "mytable.myfield"
 * 	- op: {string} (optional, default is "LIKE") The operator to use in the SQL condition, eg: = LIKE, < ...
 * 	- expr: {string} (optional, default is "") The full raw expression typed by the end-user, eg: "all my key words"
 * 	- quote: {string} (optional, default is "'" single quote) The quote to use to surround the expression.
 * 	- oper_opt: (see also datamalico_server_ajax::research_build_select_query() and the frontend_access structure in frontend_access.conf.php)
 * 		- exact_word: {bool} (optional, default is false) If true, it searches the exact word: 'word' and not a part of word: '%word%'
 * 		- exact_expr: {bool} (optional, default is false) If true, it searches the exact expression: 'hello world' and not several expression: 'hello' + 'world'
 * 		- all_words: {bool} (optional, default is true) If true, it searches if ALL expressions are present in a field, instead of only one expression.
 *
 * @return tmp_conditions_array: {numerical array} (mandatory) Is an array of conditions for one full_field_name.
 * 	- 0: "mytable.myfield LIKE '%hello%'"
 * 	- 1: "mytable.myfield LIKE '%world%'"
 *
 * Theory:
 * 	- exact_expr ==> 'hello world' and not 'hello' + 'world'
 * 	- exact_word ==> 'word' and not '%word%'
 * 	- all_words (all words required) ==> If exact_expr is false, then use AND join operator to search ALL words for the same field mytable.myfield, eg:
 * 	@code
 * 	mytable.myfield LIKE '%hello%' AND mytable.myfield LIKE '%world%'
 * 	@endcode
 */
function get_one_intelligent_condition ($params)
{
	//trace2file ($params, "params", __FILE__);
	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	$default_params = array (
		'full_field_name' => null
		, 'op' => "LIKE"
		, 'expr' => ""
		, 'quote' => "'"
		//, 'exact_word' => false
		, 'oper_opt' => array (
			'exact_word' => false
			, 'exact_expr' => false
			, 'all_words' => true
		)

		// Not implemented yet:		
		, 'exact_expr' => false	// implies to cut the expression in several words or not...
		, 'all_words' => true		// implies to use AND or OR between expression, and can be in conflict with other grouping params...
	);

	$config = replace_leaves_keep_all_branches (
		$params
		, $default_params
	);

	$full_field_name = $config['full_field_name'];
	$op = $config['op'];
	$expr = $config['expr'];
	$quote = $config['quote'];



	$tmp_conditions_array = array ();

	if ($config['oper_opt']['exact_expr'] === true)
	{
		$esc_value = $mysqli_con->real_escape_string($expr);
		$tmp_conditions_array[] = "$full_field_name $op $quote$esc_value$quote";
	}
	else
	{
		// #####
		// clean expression of all marks: commas, points, semicolon...

		// About the \p class, see: Regular Expression Unicode Syntax Reference: 
		// 	http://www.regular-expressions.info/unicode.html#prop


		// any kind of punctuation character
		$pattern = '/[\p{P}]/i';
		$replacement = ' ';
		$expr = preg_replace($pattern, $replacement, $expr);

		// any kind of whitespace or invisible separator
		$pattern = '/[\p{Z}]/i';
		$replacement = ' ';
		$expr = preg_replace($pattern, $replacement, $expr);

		// clean expression of multi ASCII blank chars:
		$pattern = '/\s{2,}/i';
		$replacement = ' ';
		$expr = preg_replace($pattern, $replacement, $expr);

		// #####
		//explode expression in several words:
		$right_hand_expressions = explode (" ", $expr);

		foreach ($right_hand_expressions as $key => $val)
		{
			$right_hand_expressions[$key] = array (
				'op' => $op
				, 'val' => "%".$val."%"
			);

			$right_hand_expressions[$key]['op'] = $op;

			// exact_word:
			if ($config['oper_opt']['exact_word'] === true) $right_hand_expressions[$key]['val'] = $val;
			else $right_hand_expressions[$key]['val'] = "%".$val."%";	
		}

		foreach ($right_hand_expressions as $key => $condition_op_and_val_2)
		{
			$op = $condition_op_and_val_2['op'];
			$esc_value = $mysqli_con->real_escape_string($condition_op_and_val_2['val']);
			$tmp_conditions_array[] = "$full_field_name $op $quote$esc_value$quote";

			//echo trace2web ("$full_field_name $op $quote$esc_value$quote");
		}
	}


	if ($config['oper_opt']['all_words'] === true)
	{
		$one_only_cond = get_ANDOR_condition ($tmp_conditions_array, "AND");
		$tmp_conditions_array = array(); // make the tmp_conditions_array empty
		$tmp_conditions_array[] = $one_only_cond;

	}

	//trace2file ($tmp_conditions_array, "tmp_conditions_array", __FILE__);
	return $tmp_conditions_array;
}


?>
