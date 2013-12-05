<?php
/** 
 * @file
 * File where the datamalico_server_dbquery API is defined.
 *
 * You must make the difference between, this (here) library, and the datamalico ajax library (see datamalico_server_ajax.lib.php), which recieves data from a client page,
 * and then uses this (here) library to make the interface with the database itself.
 * The present file manages ajax handling.
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 *
 * How does pagination works? --> See the property pagination of the datamalico_server_dbquery object to learn more about pagination.
 *
 * 
 *
 * @todo This file content sould be transform to a class. But How to use such a class? Do I do:
 * - $dco = new datamalico_server_dbquery ("select multiselist", $frontend_access...) OR
 * - $dco = new datatamalico ($frontend_access...); $dco->select_multiselist();
 *
 * For information:
 * Mysqli and connection time :
 * 	- http://www.siteduzero.com/forum-83-449218-p1-optimiser-le-temps-de-connection-mysql.html
 * 	- http://www.incapable.fr/conseil-mysql-19
 * - max_connections = 310 on my own server 
 */




$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
include_once $doc_root."/1001_addon/library/datamalico/datamalico.conf.php";

// Other datamalico PHP classes:
include_once $doc_root."/1001_addon/library/datamalico/datamalico_server_ajax.lib.php";
include_once $doc_root."/1001_addon/library/datamalico/pagination.class.php";

// Datamalico configuration files:
include_once $doc_root."/1001_addon/library/datamalico/backend_access/backend_access.conf.php";
include_once $doc_root."/1001_addon/library/datamalico/frontend_access/frontend_access.conf.php";
include_once $doc_root."/1001_addon/library/datamalico/relationship/relationship.conf.php";
include_once $doc_root."/1001_addon/library/datamalico/data_validator/data_validator.conf.php";

// Other used libraries:
include_once $doc_root."/1001_addon/library/mil_/mil_.lib.php";
//include_once $doc_root."/1001_addon/library/mil_/mil_exception.class.php";
//include_once $doc_root."/1001_addon/library/mil_/mil_log.class.php";
include_once $doc_root."/1001_addon/library/config/mysql-connection.php";


/**
 * @warning You must make the difference between all datamalico classes:
 * - datamalico_server_dbquery, a php class for server side pages. (See datamalico_server_dbquery.lib.php)
 * - datamalico_server_ajax, a php class making the interface between javascript client side pages and php server-side pages. (See datamalico_server_ajax.lib.php) 
 * - datamalico_client (or its better alias datamalico), a javascript class for client side handling: (See datamalico.lib.js)
 *   	- display of data
 *   	- saving of data.
 *
 * @todo Continue the transformation from the procedural style to the object style.
 * @code
 $dco = new datamalico_server_dbquery ($frontend_access);
$dco->select_multiselist();
$ajaxReturn = $dco->output;
* @endcode
 */
class datamalico_server_dbquery
{
	/**
	 * - $input {associative array} This is an array containting params given as input for this datamalico_server_dbquery object.
	 * 	- pagination: {pagination object} Pagination properties. See the pagination class in pagination.class.php
	 */
	public $input = array (
		'pagination' => null
	);

	public $output = array(
		'metadata' => ''
		, 'error_stack' => ''
		, 'select' => ''
		, 'delete' => ''
		, 'update' => ''
		, 'insert' => ''
	);

	function __construct ()
	{
		//trace ("In constructor\n");
		$this->timing = array (
			'begin' => ''	// look for debug_chronometer () in mil_.lib.php
			, 'laps' => ''
			, 'end' =>  ''
		);

		//echo trace2web($this, "At the end of the " . __CLASS__ . " constructor");
	}

	function __destruct ()
	{
		//trace ("Destruction of an object " . __CLASS__);
	}

	/**
	 * Operates a sql select and returns a total response set.
	 * @param $params {associative array}
	 * 	An associative array containing:
	 * 	- sql: {string} SQL SELECT query, you want to execute. Do not add a LIMIT clause to this statement.
	 * 	- frontend_access: (optional) {associative array} Each column name of the SQL result can be an item of this array. So you can here override 
	 * 		settings you have predefined in frontend_access.conf.php and in data_validator.conf.php for the 'DVIC'.
	 * 		See frontend_access.conf.php to see how to fill it.
	 * 	- temp_insert_id: (optional) {associative array} See select_empty()
	 * 	- pagination: (optional) {associative array} When a query returns a lot of rows, you can paginate these results and require only a specific page.
	 * 		- page: (optional) {integer} The number of page you require (For more details, see the pagination class in pagination.class.php)
	 * 		- perpage: (optional) {integer} The number of results that you want a page displays. (see the pagination class in pagination.class.php)
	 * 	- runas: (optional) See your own function:
	 * 		- can_vertically_access_field() in backend_access.conf.php
	 *	- action: (optional) {associative array} THIS PARAM IS STILL IN BETA TEST. Specifies behavior data returned by the sql must have when there are interactions (mouse, keyboard...) on 
	 * 		the frontend page
	 * 		- save_mode: (optional) {string, default is 'no'} "no", "generic_atomic_save", "custom_atomic_save", "global_save". If not specified, the default value will be "no"
	 * 			- "no" (default) (means read only mode)
	 * 			- "generic_atomic_save" (update data on focuseout using dco_ajax_atomic_update())
	 * 			- "custom_atomic_save" (update data on focuseout using a custom function. Then input the name of the function as custom_atomic_save_fn)
	 * 			- "global_save" (must be defined by a button by the coder). If global_save, then, only one record is returned by the select
	 * 				(otherwise, the form will have several fields with the same name).
	 * 		- url: (optional) {string, Default is "noway.html"} You can use it if save_mode is "generic_atomic_save" or "custom_atomic_save".
	 * 			- This is the url to be used in the javascript script for the ajax save.
	 * 			- "noway.html" (default)
	 * 		- custom_atomic_save_fn: {string} (optional, no default) name of the javascript function to execute. Don't forget that your javascript function 
	 * 			must receive a 'config' argument. Then see how is done the dco_ajax_atomic_update() function in datamalico.lib.js
	 * @code
	 * function my_custom_atomic_save_fn (config)
	 * {
	 * 	//...
	 * }
	 * @endcode
	 * 	- calling_FILE: (optional) {string} something like __FILE__ to identify, in case of bugs, where is the origin of the problem
	 * 	- calling_LINE: (optional) {string} something like __LINE__ to identify, in case of bugs, where is the origin of the problem
	 *
	 *
	 *
	 * Example of simple call:
	 * @code
	 * $sql = "SELECT * FROM data_registered";
	 * $config = array (
	 * 	'sql' => $sql
	 * 	, 'calling_FILE' => __FILE__
	 * 	, 'calling_LINE' => __LINE__
	 * );
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->select($config);
	 * return $dco->output;
	 * @endcode
	 *
	 *
	 *
	 * Example of call with features:
	 * @code
	 * $name_condition = mil_escape_string ("%smith%");
	 * $sql = "SELECT reg_id, country_id FROM data_registered WHERE name like '$name_condition'";
	 * $country_valuelist = get_the_valuelist (
	 * 	"SELECT country_id, english FROM config_country WHERE enabled = 1 ORDER BY english"
	 * 	, $GLOBALS['mil_lang']['please_choose_a_mega_country'];	// manage the null value (for persons wihtout country_id)
	 * );
	 * $frontend_access = array (
	 * 	'country_id' => array (
	 * 		'field_label' => $GLOBALS['mil_lang']['country_id_label']
	 * 		, 'accesses' => array (
	 * 			'rights' => "write"
	 * 			, 'behavior' => "ondblclick"
	 * 			)
	 * 		, 'form_field_type' => "select"
	 * 		, 'valuelist' => $country_valuelist
	 * 		//, 'maxlength' => 100
	 * 		//, 'max_display_length' => 20
	 * 		//, 'DVIC' => 'function tintin_toto_titi_tata (event) {event.stopPropagation(); console.log ($(this).val()");}'
	 * 		)
	 * 	);
	 * $action = array (
	 * 	'save_mode' => "global_save"
	 * 	, 'url' => dirname(__FILE__) . "/my_dco_page.delupsert.ajax.php"
	 * 	);
	 * $config = array (
	 * 	'sql' => $sql
	 * 	, 'frontend_access' => $frontend_access
	 * 	, 'pagination' => $this_mil_page->_POST['pagination']
	 * 	, 'action' => $action
	 * 	, 'calling_FILE' => __FILE__
	 * 	, 'calling_LINE' => __LINE__
	 * 	);
	 *
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->select ($config);
	 * return $dco->output;
	 * @endcode
	 *
	 *
	 * @return {associative array} This array will obviously be used by the javascript file as an ajax return value. See datamalico.lib.js to see how to work with this.
	 * 	- metadata: (mandatory) {associative array} Metadata of the query.
	 * 		- returnCode: (mandatory) {string, default is 'ERROR'} The code returned by the datamalico datamalico_server_dbquery::select() method. Can be:
	 * 			- ERROR
	 * 			- NO_RESULT_DISPLAYED
	 * 			- 1_RESULT_DISPLAYED
	 * 			- X_RESULTS_DISPLAYED
	 * 		- returnMessage: (mandatory) {string, default is $GLOBALS['mil_lang_common']['ERROR']} The message returned by the datamalico datamalico_server_dbquery::select() method. Can be:
	 * 			- $GLOBALS['mil_lang_common']['ERROR']
	 * 			- $GLOBALS['mil_lang_common']['NO_RESULT_DISPLAYED']
	 * 			- $GLOBALS['mil_lang_common']['PAGE_X_OF_N_1'] . " " . $this->input['pagination']->page . " " . $GLOBALS['mil_lang_common']['PAGE_X_OF_N_2'] . " " . $this->input['pagination']->lastpage . " " ."(" . $this->input['pagination']->nbRes . ' ' . $singular_or_plural . ")"
	 * 				- eg: Page 1 of 15 (143 results)
	 * 		- sql_query: (mandatory) {string} The executed query.
	 * 		- affected_rows: (mandatory) {integer} Number of results found.
	 * 		- displayed_rows: (mandatory) {integer}€A€A€A Number of results on one page (see pagination for more details).
	 * 		- pagination: {mandatory) {associative array} Object pagination related to this request (see pagination for more details).
	 * 	- results: (mandatory) {associative array} Results of the SQL SELECT statement.
	 * 		- records: (optional) {associative array}
	 * 			- field_structure: (optional) {associative array} Gives meta information about fields returned.
	 * 				- {field_name}: (mandatory) {associative array} Is the result of get_field_structure() for each field.
	 * 			- records: (mandatory) {associative array} Records
	 * 				- 1: (mandatory) {associative array} the first record. Note that the index begins with 1, not 0.
	 * 					- {field_name}: (mandatory) {string} Value of this field for this field/column.
	 * 					- {field_name}: (optional) {string} Value of this field for this field/column.
	 * 					- ...
	 * 				- 2: (optional) {associative array} the second record.
	 * 					- ...
	 * 				- ...
	 * 			- primary_keys: (mandatory) {associative array} List of the primary keys for each table involved in the SQL result.
	 * 				- {table_name}: (mandatory) {associative array}
	 * 					- 1: (optional) {string} Name of the first field part of the primary key.
	 * 					- 2: (optional) {string} Name of the second field (if several fields) part of the primary key.
	 * 					- ...
	 * 	- action: (mandatory) {associative array} THIS PARAM IS STILL IN BETA TEST.  Specifies behavior data returned by the sql must have when there are interactions (mouse, keyboard...) on 
	 * 		the frontend page
	 * 		- manipulation:
	 * 		- table_type: data or config table.
	 * 		- save_mode: (optional) {string, default is 'no'} "no", "generic_atomic_save", "custom_atomic_save", "global_save". If not specified, the default value will be "no"
	 * 			- "no" (default) (means read only mode)
	 * 			- "generic_atomic_save" (update data on focuseout using dco_ajax_atomic_update())
	 * 			- "custom_atomic_save" (update data on focuseout using a custom function. Then input the name of the function as custom_atomic_save_fn)
	 * 			- "global_save" (must be defined by a button by the coder). If global_save, then, only one record is returned by the select
	 * 				(otherwise, the form will have several fields with the same name).
	 * 		- url: (optional) {string, Default is "noway.html"} You can use it if save_mode is "generic_atomic_save" or "custom_atomic_save".
	 * 			- This is the url to be used in the javascript script for the ajax save.
	 * 			- "noway.html" (default)
	 *
	 *
	 * Example of the returned array
	 * @code
	 * ajaxReturn :Array
	 * (
	 *     [metadata] => Array
	 *         (
	 *             [returnCode] => 1_RESULT_DISPLAYED
	 *             [returnMessage] => Page 1 of 1 (1421 results)
	 *             [sql_query] => SELECT reg_id, country_id FROM data_registered
	 * 		LIMIT 0, 15
	 * 		
	 *             [affected_rows] => 1
	 *             [displayed_rows] => 1
	 *             [pagination] => Array
	 *             	    (
	 *             	    	[page] => 1
	 *             	    	[perpage] => 15
	 *             	    	[nbRes] => 1421
	 *             	    	[lastpage] => 95
	 *             	    	[num_of_first_elem_on_page] => 1
	 *             	    	[num_of_last_elem_on_page] => 15
	 *             	    )
	 *         )
	 * 
	 *     [results] => Array
	 *         (
	 *             [records] => Array
	 *                 (
	 *                     [1] => Array
	 *                         (
	 *                             [reg_id] => 1
	 *                             [country_id] => 76
	 *                         )
	 *
	 *                     [2] => Array
	 *                         (
	 *                             [reg_id] => 2
	 *                             [country_id] => 76
	 *                         )
	 *
	 *                     ...
	 *
	 *                     [{perpage}]  => Array 		// There will be as many records as the $this->input['pagination']->perpage value
	 *                         (
	 *                             [reg_id] => 15
	 *                             [country_id] => 76
	 *                         )
	 *                 )
	 * 
	 *             [field_structure] => Array
	 *                 (
	 *                     [reg_id] => Array
	 *                         (
	 *                             [name] => reg_id
	 *                             [orgname] => reg_id
	 *                             [table] => data_registered
	 *                             [orgtable] => data_registered
	 *                             [def] => 
	 *                             [max_length] => 0
	 *                             [length] => 10
	 *                             [charsetnr] => 63
	 *                             [flags] => 49699
	 *                             [type] => 3
	 *                             [decimals] => 0
	 *                             [type_human_readable] => LONG
	 *                             [use_quotes_for_db_insertion] => 
	 *                             [frontend_access] => Array
	 *                                 ( 
	 *                             		[field_label] => 
	 *                             		[accesses] => Array
	 *                                 		(
	 *                                     		[rights] => read
	 *                                 		)
	 * 
	 *                             		[form_field_type] => 
	 *                             		[valuelist] => 
	 *                             		[maxlength] => 
	 *                             		[max_display_length] => 
	 *                             		[DVIC] => 
	 *                                 )
	 *                         )
	 * 
	 *                     [country_id] => Array
	 *                         (
	 *                             [name] => country_id
	 *                             [orgname] => country_id
	 *                             [table] => data_registered
	 *                             [orgtable] => data_registered
	 *                             [def] => 
	 *                             [max_length] => 0
	 *                             [length] => 4
	 *                             [charsetnr] => 63
	 *                             [flags] => 49192
	 *                             [type] => 2
	 *                             [decimals] => 0
	 *                             [type_human_readable] => SHORT
	 *                             [use_quotes_for_db_insertion] => 
	 *                             [field_label] => Pays
	 *                             [accesses] => Array
	 *                                 (
	 *                                     [rights] => write
	 *                                     [behavior] => ondblclick
	 *                                 )
	 * 
	 *                             [form_field_type] => select
	 *                             [valuelist] => Array
	 *                                 (
	 *                                     [1] => Afghanistan
	 *                                     [3] => Albania
	 *                                     [4] => Algeria
	 *                                     [5] => American Samoa
	 *                                    	... 
	 *                                     [249] => Zimbabwe
	 *                                 )
	 * 
	 *                             [maxlength] => 
	 *                             [max_display_length] => 
	 *                             [DVIC] => 
	 *                         )
	 * 
	 *                 )
	 * 
	 *             [primary_keys] => Array
	 *                 (
	 *                     [data_registered] => Array
	 *                         (
	 *                             [1] => reg_id
	 *                         )
	 * 
	 *                 )
	 * 
	 *         )
	 * 
	 *     [action] => Array
	 *         (
	 *             [manipulation] => update
	 *             [table_type] => data
	 *             [save_mode] => no
	 *             [url] => noway.html
	 *         )
	 * 
	 * )
	 * @endcode
	 *
	 * @todo Complete the development of the action parameter.
	 */
	public function select ($params)
	{
		//trace2file("", "", __FILE__, true);

		// ############################
		// Params and config
		$function_return['metadata']['returnCode'] = "ERROR";
		$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
		$function_return['metadata']['debugMessage'];
		$function_return['metadata']['affected_rows'];
		$function_return['metadata']['displayed_rows'];
		$function_return['metadata']['insert_id'];
		$function_return['metadata']['sql_query'];
		$function_return['results']['records'];
		$function_return['results']['field_structure'];
		$function_return['results']['primary_keys'];
		$function_return['action'];


		// ############################
		// init

		$config = array(); //foreach ($params as $key => $value) {$config[$key] = $value;}; 	// foreach ($params as $key => $value) {$$key = $value;};

		if (exists_and_not_empty($params['sql'])) $config['sql'] = $params['sql'];
		else return;

		if (exists_and_not_empty($params['frontend_access'])) $config['frontend_access'] = $params['frontend_access'];
		else $config['frontend_access'] = array ();


		// action:
		if (exists_and_not_empty($params['action'])) $config['action'] = $params['action'];
		else $config['action'] = array ();

		if (exists_and_not_empty($config['action']['save_mode'])) $config['action']['save_mode'] = $config['action']['save_mode'];
		else $config['action']['save_mode'] = "no";
		$config['action']['save_mode'] = strtolower($config['action']['save_mode']);

		$config['action'] = array (
			//'manipulation' => exists_and_not_empty ($params['action']['manipulation']) ? $params['action']['manipulation'] : "update", "insert", "update"

			// table_type: (optional), "data" or "join" serves only for join tables (multiselist)
			'table_type' => exists_and_not_empty ($params['action']['table_type']) ? $params['action']['table_type'] : "data" 
			, 'save_mode' => exists_and_not_empty ($params['action']['save_mode']) ? $params['action']['save_mode'] : "no"
			, 'url' => exists_and_not_empty ($params['action']['url']) ? $params['action']['url'] : "noway.html"
		);

		if (exists_and_not_empty ($params['action']['custom_atomic_save_fn'])) 
			$config['action']['custom_atomic_save_fn'] = $params['action']['custom_atomic_save_fn'];

		// empty_new_record = older selectempty:
		$config['empty_new_record'] = exists_and_not_empty($params['empty_new_record']) === true ? $params['empty_new_record'] : false;
		if ($config['empty_new_record'] === true) $params['action']['save_mode'] = "global_save";


		if (exists_and_not_empty($params['temp_insert_id']))
		{
			$config['temp_insert_id'] = $params['temp_insert_id'];
			if (!exists_and_not_empty($config['temp_insert_id']['field']))
			{
				new mil_Exception (__FUNCTION__ . " : \$config['temp_insert_id']['field'] must not be empty."
					, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
				$config['temp_insert_id']['field'] = "";
			}
			else
			{
				if (!exists_and_not_empty($config['temp_insert_id']['value']))
				{
					new mil_Exception (__FUNCTION__ . " : \$config['temp_insert_id']['value'] must not be empty."
						, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
					$config['temp_insert_id']['value'] = "";
				}
			}
		}
		else
		{
			$config['temp_insert_id']['field'] = "";
			$config['temp_insert_id']['value'] = "";
		}

		// pagination
		$config['pagination'] = array();
		if (exists_and_not_empty($params['pagination'])) $config['pagination'] = $params['pagination'];
		if (!exists_and_not_empty($params['pagination']['page'])) $config['pagination']['page'] = NULL;
		if (!exists_and_not_empty($params['pagination']['perpage'])) $config['pagination']['perpage'] = NULL;


		// tracing code
		$config['calling_FILE'] = exists_and_not_empty($params['calling_FILE']) ? $params['calling_FILE'] : __FILE__;
		$config['calling_LINE'] = exists_and_not_empty($params['calling_LINE']) ? $params['calling_LINE'] : __LINE__;
		$config['time'] = nowCET ();

		$config['runas'] = $params['runas'];



		// ############################
		// work
		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();	

		$sql = $config['sql'];
		$records;
		$field_structure;
		$nbRes;
		$limit_clause = "";

		// if select() or select_multiselist()
		if ($config['empty_new_record'] === false)
		{
			// if select()
			if ($config['action']['table_type'] !== "join")
			{
				// get nbRes before performing a the whole query, in order to perform the minimal query related to the pagination required.
				//$patterns = array();
				//$patterns[0] = '/\n/';
				//$patterns[1] = '/\r/';
				//$patterns[2] = '/SELECT.*FROM/i';
				//$replacements = array();
				//$replacements[0] = ' ';
				//$replacements[1] = ' ';
				//$replacements[2] = 'SELECT COUNT(DISTINCT *) FROM'; // Causes problems if a query having a group by. The count is the one of the whole line number without the group by.

				//$sql_count = preg_replace($patterns, $replacements, $sql);
				//echo trace2web ($sql, "sql");
				if ($mysqli_result = $mysqli_con->query($sql))
				{
					$row = $mysqli_result->fetch_row();
					//$nbRes = $row[0];
					$nbRes = $mysqli_con->affected_rows;
					//echo trace2web ($nbRes, "nbRes");
				}
				else
				{
					new mil_Exception (
						__FUNCTION__ . " : This is not possible to execute the request: $sql"
						. trace2web($mysqli_con->error, "mysqli_con->error")
						, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
				}

				// In order to get good pagination data, we need the nbRes, the number of results of the full query. That's why we make a:
				// 	SELECT COUNT(*)
				//echo trace2web ($config['pagination']['page'], "config['pagination']['page']");
				//echo trace2web ($config['pagination']['perpage'], "config['pagination']['perpage']");
				$this->input['pagination'] = new pagination ( array (
					'page' => $config['pagination']['page']
					, 'perpage' => $config['pagination']['perpage']
					, 'nbRes' => $nbRes
				));
				//echo trace2web ($this->input['pagination'], "this->input['pagination']");

				$limit1 = $this->input['pagination']->num_of_first_elem_on_page - 1;
				$perpage = $this->input['pagination']->perpage;
				$limit_clause = "LIMIT $limit1, $perpage";
			}

			// if select_multiselist()
			else if ($config['action']['table_type'] === "join")
			{
				// get nbRes to populate the perpage and nbRes pagination params:				
				$sql_count = "
					SELECT COUNT(*) FROM
					(
						$sql
					) multiselist_temp_table # this is an alias of this temp table allowing to count and avoiding 
					# the error: 1248 - Every derived table must have its own alias
					";
				if ($mysqli_result = $mysqli_con->query($sql_count))
				{
					$row = $mysqli_result->fetch_row();
					$nbRes = $row[0];
					$mysqli_result->free();
				}
				else
				{
					new mil_Exception (
						__FUNCTION__ . " : This is not possible to execute the request: $sql"
						. trace2web($mysqli_con->error, "mysqli_con->error")
						, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
				}

				$this->input['pagination'] = new pagination (
					array (
						'page' => 1
						, 'perpage' => $nbRes
						, 'nbRes' => $nbRes
					)
				);
			}
		}

		// if select_empty()
		else
		{
			$this->input['pagination'] = new pagination (
				array (
					'page' => 1
					, 'perpage' => 1
					, 'nbRes' => 1
				)
			);

			// The 2 following lines are needed because, if the user has specified params in the URL or GET or POST params, they must be 
			// 	corrected after pagination construtor.
			$this->input['pagination']->page = 1;
			$this->input['pagination']->perpage = 1;
			$limit_clause = "LIMIT 1";
		}

		$sql = $sql . "
			$limit_clause
			";
		$function_return['metadata']['sql_query'] = $sql;
		//echo trace2web ($sql, "minimal query for pagination");






		// ############################
		// perform the minimal query related to the pagination required and get results
		//trace2file($sql, "sql", __FILE__);
		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$nbRes = $mysqli_result->num_rows;
			//trace("mysqli_result -> num_rows:" . $mysqli_result->num_rows);
			//var_dump ($mysqli_con->affected_rows); //trace("mysqli_con -> affected_rows:" . $mysqli_con->affected_rows);

			$function_return['metadata']['affected_rows'] = $nbRes;

			//echo trace2web ($function_return['metadata']['affected_rows'], "affected_rows of minimal query for pagination");

			if ($nbRes == 0)
			{
				//new mil_Exception ("Should not happen: $sql", "1201111240", "WARN", __FILE__ .":". __LINE__ );
				$records[1] = $mysqli_result->fetch_assoc();
				//echo trace2web($records, "Nores");
			}
			else if ($nbRes >= 1)
			{
				for ($l = 1; $row = $mysqli_result->fetch_assoc(); $l++)
				{
					// "global_save" (In the javascript and html pages, the save action must be defined by a button by the coder). If global_save, then, only one record is returned by the select
					// 	(otherwise, the form will have several fields with the same name).
					//if ($config['action']['save_mode'] === "global_save" && $l>1)
					//{
					//	break;
					//}
					$records[$l] = $row;
				}
			}

			$mysqli_result->free();
		}
		else
		{
			new mil_Exception (
				__FUNCTION__ . " : This is not possible to execute the request: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}
		//$mysqli_con->close();

		//echo trace2web($records, "records");
		//trace2file($records, "records", __FILE__);
		$field_structure = get_field_structure ($sql, $config['frontend_access'], $config['action']);
		//echo trace2web($field_structure, "field_structure");



		// ############################
		// security 
		// 	Can create problems if several fields returned in the select have the same field_name.
		if (exists_and_not_empty($field_structure))
		{
			foreach ($field_structure as $field_name => $field_infos)
			{
				// In the case of a join table (multiselist) using a UNION clause, this is not intelligent to make this check here, 
				// 	because in the field info, both table and orgtable are empty. That's why, this security check is done in the select_multiselist ()

				//trace($field_name);
				$can_vertically_access_field = false;

				$can_vertically_access_field = can_vertically_access_field (
					array(
						"manipulation" => "select"
						, "field_name" => $field_name
						, "field_infos" => $field_infos
						, 'runas' => $config['runas']
					)
				);
				//trace("can_vertically_access_field:$can_vertically_access_field");
				//trace("field_name:$field_name");
				//echo trace2web($field_infos, "field_infos");

				if ($can_vertically_access_field)
				{
					$function_return['results']['field_structure'][$field_name] = $field_infos;
					foreach ($records as $line => $row)
					{
						$orgtable = $function_return['results']['field_structure'][$field_name]['field_direct']['orgtable'];
						$orgname = $function_return['results']['field_structure'][$field_name]['field_direct']['orgname'];

						// take into account the temp_insert_id for insertion of multi-selection-list cases
						if ($config['temp_insert_id']['field'] === "$orgtable.$orgname")
						{
							$function_return['results']['records'][$line][$field_name] = $config['temp_insert_id']['value'];
						}
						else
						{
							$function_return['results']['records'][$line][$field_name] = $row[$field_name];
						}
					}
				}
			}
		}
		//echo trace2web($function_return['results']['records'], "function_return['results']['records']");
		//trace2file($records, "records", __FILE__);



		// ############################
		// primary_keys for each tables and fields in the field_structure
		$primary_keys;
		if (exists_and_not_empty ($function_return['results']['field_structure']))
		{
			foreach ($function_return['results']['field_structure'] as $field_name => $field_infos)
			{
				$orgtable = $field_infos['field_direct']['orgtable'];
				if (!exists_and_not_empty($primary_keys[$orgtable]))
				{
					$primary_keys[$orgtable] = get_primary_keys ($orgtable);
				}

			}
			$function_return['results']['primary_keys'] = $primary_keys;
		}


		// ############################
		// Set action
		$function_return['action'] = $config['action'];



		// ########################
		// output_data_validator:
		//trace2file ($function_return['results']['field_structure'], "function_return['results']['field_structure'] ##############################", __FILE__);
		if (exists_and_not_empty ($function_return['results']['field_structure']))
		{
			foreach ($function_return['results']['field_structure'] as $field_name => $field_structure)
			{
				$orgtable = $function_return['results']['field_structure'][$field_name]['field_direct']['orgtable'];
				$orgname = $function_return['results']['field_structure'][$field_name]['field_direct']['orgname'];

				/*
				//if (exists_and_not_empty ($function_return['results']['field_structure'][$field_name]['frontend_access']['output_data_validator']))
				if (exists_and_not_empty ($GLOBALS['data_validator'][$orgtable][$orgname]['output']))
				{
					//$output_data_validator = $function_return['results']['field_structure'][$field_name]['frontend_access']['output_data_validator'];
					$output_data_validator = $GLOBALS['data_validator'][$orgtable][$orgname]['output'];
					foreach ($function_return['results']['records'] as $row_num => $row)
					{
						$function_return['results']['records'][$row_num][$field_name] = $output_data_validator($row[$field_name]);
					}
				}
				 */

				if (exists_and_not_empty ($field_structure['frontend_access']['data_validator']['output']))
				{
					//$output_data_validator = $function_return['results']['field_structure'][$field_name]['frontend_access']['output_data_validator'];
					$output_data_validator = $field_structure['frontend_access']['data_validator']['output'];
					foreach ($function_return['results']['records'] as $row_num => $row)
					{
						$function_return['results']['records'][$row_num][$field_name] = $output_data_validator($row[$field_name]);
					}					
				}

				unset ($function_return['results']['field_structure'][$field_name]['frontend_access']['data_validator']['output']); // no need to send this to the client.
			}
		}



		// ############################
		// For empty_new_record
		//echo trace2web($function_return['results']['records'], "function_return['results']['records']");
		if ($config['empty_new_record'] === true)
		{
			$records = $function_return['results']['records'];
			$function_return['results']['records'] = array ();
			//echo trace2web($function_return['results']['records'], "function_return['results']['records']");

			//echo trace2web($config['temp_insert_id'], "config['temp_insert_id']");
			if (exists_and_not_empty($records))
			{
				foreach ($records[1] as $field_name => $field_value)
				{
					$orgtable = $function_return['results']['field_structure'][$field_name]['field_direct']['orgtable'];
					$orgname = $function_return['results']['field_structure'][$field_name]['field_direct']['orgname'];

					//trace("$orgtable.$orgname");
					if ($config['temp_insert_id']['field'] === "$orgtable.$orgname")
					{
						$function_return['results']['records'][1][$field_name] = $config['temp_insert_id']['value'];
					}
					else
					{
						$function_return['results']['records'][1][$field_name] = "";
					}
				}
			}
		}
		//echo trace2web($function_return['results']['records'], "function_return['results']['records']");




		// ########################
		// re-check nbRes and returnMessage: function_return['metadata']['affected_rows']:

		if ($function_return['metadata']['affected_rows'] == 0)
		{
			$function_return['metadata']['displayed_rows'] = 0;
		}
		else
		{
			$function_return['metadata']['displayed_rows'] = count ($function_return['results']['records']);
		}

		$function_return['metadata']['affected_rows'] = $this->input['pagination']->nbRes;

		if ($function_return['metadata']['displayed_rows'] == 0)
		{
			$function_return['metadata']['returnCode'] = "NO_RESULT_DISPLAYED";
			$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['NO_RESULT_DISPLAYED'];
		}
		else if ($function_return['metadata']['displayed_rows'] >= 1)
		{
			if ($function_return['metadata']['displayed_rows'] == 1)
			{
				$function_return['metadata']['returnCode'] = "1_RESULT_DISPLAYED";
				if ($function_return['metadata']['affected_rows'] > 1)
				{
					$singular_or_plural = $GLOBALS['mil_lang_common']['X_RESULTS_DISPLAYED'];
				}
				else
				{
					$singular_or_plural = $GLOBALS['mil_lang_common']['1_RESULT_DISPLAYED'];
				}
			}
			else if ($function_return['metadata']['displayed_rows'] > 1)
			{
				$function_return['metadata']['returnCode'] = "X_RESULTS_DISPLAYED";
				$singular_or_plural = $GLOBALS['mil_lang_common']['X_RESULTS_DISPLAYED'];
			}

			// Page 3 sur 15 - (146 rÃ©sultats)	// Page 3 of 15 - (146 results)
			$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['PAGE_X_OF_N_1'] . " " . 
				$this->input['pagination']->page . " " . 
				$GLOBALS['mil_lang_common']['PAGE_X_OF_N_2'] . " " . 
				$this->input['pagination']->lastpage . " " .
				"(" . $this->input['pagination']->nbRes . ' ' . $singular_or_plural . ")";
		}

		$function_return['metadata']['pagination'] = $this->input['pagination'];

		//echo trace2web($function_return, "function_return");
		$this->output = $function_return;
	}

	/**
	 * Operates a sql select and returns a total response set containting an empty row.
	 *
	 * When, in a web page, you have to display empty fields in order to insert a new record, then use this function to return an empty row.
	 * With such an empty row, and its empty fields, then you can, in the HTML page edit these empty fields and so insert (with a global_save see select() ) 
	 * a new record.
	 *
	 * @warning Note that in order to insert a multiselist related to an entity record, select_empty() is not necessary. For such a use, use select_multiselist 
	 * 	even if no record exist in the join table. For more information see select_multiselist().
	 *
	 * @param $params {associative array} 
	 * 	@note Almost all params for this function are the same that params sent to select(). But here you can also add:
	 * 	- temp_insert_id (optional) {associative array} 
	 * 	You need to specify this parameter only if, you must insert a record for an entity, and also, a record for one or several join tables of this entity
	 * 	(for example, add info related to multiselists...). ex: Let's imagine that you need to insert a Person record. But you also need to input data about 
	 * 	one of the Person multislist such as groups he belongs to (thus there will be records (in a join table)). Then, in order to make the relation between 
	 * 	both tables, such a temp_insert_id is necessary to be received by the select_empty() for the Person table and also, the select_empty() for 
	 * 	the Person_Groups join table.
	 * 		- field: {string} The full name of the field that must not be returned empty ex: "Person.person_id" or "Person_Profession.person_id"
	 * 		- value: {string} This value is directly linked to the javascript function dco_get_temp_insert_id() (see datamalico.lib.js and the example below)
	 * 	@note Please also note that when using this temp_insert_id parameter, the sql param you send to this function must contain this field in the SELECT clause.
	 *
	 * @note Note that for any insertion, using select_empty() the primary key of the record is required in the SELECT clause.
	 *
	 * @return {associative array} See the returned value of select().
	 *
	 * @attention datamalico_server_ajax::research_get_form_structure() and datamalico_server_dbquery::select_empty() are not twin methods, but their philosophy are the same:
	 * 	- getting an empty row on purpose.
	 *
	 * @warning
	 * - In order to increase performances, note that you don't have to write "LIMIT 1" at the end of your sql query, because the function will do it for you.
	 * - Also note that the $params['action']['save_mode'] will automatically be overwritten with "global_save", see select()
	 * - The request returns an empty row, but to do so, their must be at least one result returned by the original request in order 
	 * 	to feed the ['field_structure'] element. Otherwise, this will generate an error. So don't don it on a empty table.
	 *
	 * Here is the list of functions related to this concept of selecting an empty row in order to make an insertion:
	 * - dco_get_temp_insert_id() in datamalico.lib.js
	 *
	 * Here is the list of variables related to this concept of selecting an empty row in order to make an insertion:
	 * - temp_insert_id: {field: "data_registered.reg_id", value: temp_insert_id} : Javascript var sent via ajax to the server page, before retrieving the empty row.
	 *
	 * Simple example WITHOUT temp_insert_id:
	 * @code
	 * $sql = "SELECT firstname, lastname FROM Person";
	 * $frontend_access = array (
	 * 	'firstname' => array (
	 * 		'field_label' => "First Name"
	 * 		, 'accesses' => array (
	 * 			'rights' => "write"
	 * 			, 'behavior' => "ondblclick"
	 * 		)
	 * 		, 'form_field_type' => "text"
	 * 	)
	 * 	, 'lastname' => array (
	 * 		'field_label' => "Familly Name"
	 * 		, 'accesses' => array (
	 * 			'rights' => "write"
	 * 			, 'behavior' => "ondblclick"
	 * 		)
	 * 		, 'form_field_type' => "text"
	 * 	)
	 * );
	 * $config = array (
	 * 	'sql' => $sql
	 * 	, 'frontend_access' => $frontend_access
	 * 	, 'calling_FILE' => __FILE__
	 * 	, 'calling_LINE' => __LINE__
	 * );
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->select_empty($config);
	 * @endcode
	 *
	 *
	 *
	 * Simple example WITH temp_insert_id:
	 * @code
	 * $sql = "SELECT person_id, firstname, lastname FROM Person";
	 * $frontend_access = array (
	 * 	'firstname' => array (
	 * 		'field_label' => "First Name"
	 * 		, 'accesses' => array (
	 * 			'rights' => "write"
	 * 			, 'behavior' => "ondblclick"
	 * 		)
	 * 		, 'form_field_type' => "text"
	 * 	)
	 * 	, 'lastname' => array (
	 * 		'field_label' => "Familly Name"
	 * 		, 'accesses' => array (
	 * 			'rights' => "write"
	 * 			, 'behavior' => "ondblclick"
	 * 		)
	 * 		, 'form_field_type' => "text"
	 * 	)
	 * );
	 * $config = array (
	 * 	'sql' => $sql
	 * 	, 'frontend_access' => $frontend_access
	 * 	, 'temp_insert_id' => $_POST['temp_insert_id']	// See also the following example in javascript
	 * 	, 'calling_FILE' => __FILE__
	 * 	, 'calling_LINE' => __LINE__
	 * );
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->select_empty($config);
	 * @endcode
	 *
	 *
	 * Javacript calling the server page with the above php script:
	 * @code
	 * $(document).ready(init_document);
	 * function init_document () 
	 * {
	 * 	...
	 * 	var temp_insert_id = dco_get_temp_insert_id();
	 * 	select_my_entity_ajax (temp_insert_id);		// select for the entity table ex: Person
	 * 	select_my_join_ajax (temp_insert_id);		// select for the join table ex: Person_Profession
	 * }
	 * 
	 * function select_my_entity_ajax (temp_insert_id)
	 * {
	 * 	//console.log("select_ajax()");
	 * 
	 * 	var div_return_display = "div_ajax_serverScript";
	 * 
	 * 	my_ajax (
	 * 			{form_id : "select_objects_form"						
	 * 			, data : {temp_insert_id: {field: "Person.person_id", value: temp_insert_id}		// send this object to the server page
	 * 			, success : on_success
	 * 			}
	 * 		);	// the server page obviously uses select_empty() with the temp_insert_id param.
	 * 	
	 * 	...
	 * }
	 * 
	 * 
	 * function select_my_join_ajax (temp_insert_id)
	 * {
	 * 	//console.log("select_ajax()");
	 * 
	 * 	var div_return_display = "div_ajax_serverScript";
	 * 
	 * 	my_ajax (
	 * 			{
	 * 			form_id : "select_objects_form"						
	 * 			, data : {temp_insert_id: {field: "Person_Profession.person_id", value: temp_insert_id}		// send this object to the server page
	 * 			, success : on_success
	 * 			}
	 * 		);	// the server page obviously uses select_empty_multiselist() with the temp_insert_id param.
	 * 	
	 * 	...
	 * }
	 * @endcode
	 */
	public function select_empty ($params)
		//	$this->output = select_empty ($params);
		//	//function select_empty ($params)
	{
		$params['empty_new_record'] = true;
		//$params['temp_insert_id'];

		$this->select ($params);
	}

	/**
	 * Returns records relative to a multi-selection-list of a particular entity record. Ex: For the entity Person, you can retrieve the multi-selection-list 
	 * 	Person_Profession of the particular record #128 (to see his/her professions).
	 * 	- This sql string will return an UNION query to get for a certain entity_id all selected and not selected items in the multiselist, 
	 * 	relying on the join table and the list table where the multiselist is defined.
	 *
	 * @warning Regarding the following parameters, either entity_id or temp_insert_id must be filled. See also select_empty_multiselist() for more information.
	 * 
	 * @param $params: {associative array} An associative array containing:
	 * 	- entity_table: (mandatory) {string} name of the entity table eg: demand_entity
	 * 	- entity_id: (optional only if temp_insert_id is not filled) {string or integer} is the entity_id you refer to. eg: a particular demand.
	 * 	- temp_insert_id: (optional only if entity_id is not filled) {associative array} Instead of using this parametre in select_multiselist()
	 * 		we advise you to prefer the use of select_empty_multiselist().
	 * 	- list_table: (mandatory) {string} name of the list table of the multiselist. eg: services_list
	 * 	- frontend_access: (optional) in order to display a checkbox_multiselist.
	 * 		- You must know:
	 * 			- We advise you to take the entity_id as 'form_field_type' => "checkbox_multiselist"|"radio_singleselist", in order to display the 
	 * 				checkbox or radiobutton.
	 * 			- This is better to make a custom frontend_access at the select_multiselist() call level instead of in the frontend_access.conf.php
	 *	- runas: (optional) See your own function:
	 * 		- can_vertically_access_field() in backend_access.conf.php
	 * 	- action: (optional) {associative array} THIS PARAM IS STILL IN BETA TEST.
	 * 		- save_mode: (optional) {string} "no", "generic_atomic_save", "global_save"
	 * 		- url: (optional) {string} url to be used in the javascript script for the ajax save. By default, this will be the one of select()
	 * 	- calling_FILE: (optional) {string} something like __FILE__ to identify, in case of bugs, where is the origin of the problem
	 * 	- calling_LINE: (optional) {string} something like __LINE__ to identify, in case of bugs, where is the origin of the problem
	 *
	 * @return {string} Same structure as the one retruned by datamalico_server_dbquery::select(). This structure reveals for the entity_id, the selected 
	 * 	and not selected items of the list table. It relies on the join_table off-course.
	 * 	- Columns are:
	 * 		- selected_in_multiselist_in_db which is per default frontend_access.accesses.rights = "hidden"
	 * 		- columns of the list_table (only fields that are accessible after a vertical access.)
	 * 		- columns of the join_table (only fields that are accessible after a vertical access.)
	 * 
	 * @note You must know that for multiple selection list, the join table must have as primary keys, the ids related to other tables. 
	 * 	You must anyway have an auto_increment field, that identifies the record, but, this must not be the primary key, but just a UNIQUE key.
	 * 	Primary keys are the ids which join tables!!!
	 *
	 *
	 * - Here is an example of the returned query:
	 * @code
	 * (
	 * 	# Simple query fetching present items into a multiple selection list of the list table linked to the wanted entity (register, demand...)
	 * 	
	 * 	SELECT
	 * 	true as selected_in_multiselist_in_db		# report the presence of this element in the selection
	 * 	, config_service.serviceCommRule_id
	 * 	, config_service.sort_index
	 * 	, config_service.service_type_id
	 * 	, config_service.french
	 * 	, data_demand_service.demand_id
	 * 	, data_demand_service.service_quantity_100th
	 * 	, data_demand_service.service_quality_id
	 * 	FROM config_service
	 * 	INNER JOIN data_demand_service ON data_demand_service.serviceCommRule_id = config_service.serviceCommRule_id
	 * 	WHERE data_demand_service.demand_id = $entity_id
	 * )
	 * UNION
	 * (
	 * 	# Query related to the list table. It serves only to display values which are not in the first query
	 * 	SELECT
	 * 	false as selected_in_multiselist_in_db		# report the absence of this element in the selection
	 * 	, config_service.serviceCommRule_id
	 * 	, config_service.sort_index
	 * 	, config_service.service_type_id
	 * 	, config_service.french
	 * 	, $entity_id			# selected_in_multiselist_in_db or not, this value must be accessible for any future handling (upsert or delete)
	 * 	, NULL
	 * 	, NULL
	 * 	FROM config_service
	 * 	WHERE config_service.serviceCommRule_id NOT IN
	 * 	(
	 * 		# Same query as the first query but the select clause is different and does the exclusion of what is already selected in the 1st query
	 * 		SELECT
	 * 		config_service.serviceCommRule_id
	 * 		FROM config_service
	 * 		INNER JOIN data_demand_service ON data_demand_service.serviceCommRule_id = config_service.serviceCommRule_id
	 * 		WHERE data_demand_service.demand_id = $entity_id
	 * 	)
	 * )
	 * ORDER BY sort_index
	 * @endcode
	 *
	 *
	 * Example of call:
	 * @code
	 * $entity_id = 2;
	 * $action = array (
	 *	'save_mode' => "global_save"
	 * );
	 * $select_multiselist_api_config = array (
	 *	'entity_table' => "data_demand"
	 *	, 'entity_id' => $entity_id
	 *	, 'list_table' => "config_service"
	 *	, 'frontend_access' => $frontend_access
	 *	, 'action' => $action		// optional
	 *	, 'calling_FILE' => __FILE__	// optional
	 *	, 'calling_LINE' => __LINE__	// optional
	 * );
	 * $results = select_multiselist ($select_multiselist_api_config);
	 * @endcode
	 *
	 * You must also know:
	 * - If you want an item of the list table to be absent of the list, then, set its 'enabled' field (in the table) to false.
	 * - The order used as result output depends on the sort_index field of the listtable.
	 */
	public function select_multiselist ($params)
	{
		//echo trace2web ($params, "select_multiselist() params");
		// ############################
		// Params and config
		$function_return['metadata']['returnCode'] = "ERROR";
		$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
		$function_return['metadata']['debugMessage'];
		$function_return['metadata']['affected_rows'];
		$function_return['metadata']['displayed_rows'];
		$function_return['metadata']['insert_id'];
		$function_return['metadata']['sql_query'];
		$function_return['results']['records'];
		$function_return['results']['field_structure'];
		$function_return['results']['primary_keys'];
		$function_return['action'];


		// ############################
		// init
		$config;

		if (!exists_and_not_empty ($params))
		{
			new mil_Exception (
				__FUNCTION__ . " : \$params must not be empty"
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
			return $function_return;
		}

		// tracing code
		$config['calling_FILE'] = exists_and_not_empty($params['calling_FILE']) ? $params['calling_FILE'] : __FILE__;
		$config['calling_LINE'] = exists_and_not_empty($params['calling_LINE']) ? $params['calling_LINE'] : __LINE__;

		if (!exists_and_not_empty ($params['entity_table']))
		{
			new mil_Exception (
				__FUNCTION__ . " : \$params['entity_table'] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}
		else
		{
			$config['entity_table'] = $params['entity_table'];
		}

		if (exists_and_not_empty ($params['entity_id']) ^ exists_and_not_empty ($params['temp_insert_id']))
		{
			if (exists_and_not_empty ($params['entity_id']))
			{
				$config['entity_id'] = $params['entity_id'];
			}
			else if (exists_and_not_empty($params['temp_insert_id']))
			{
				$config['temp_insert_id'] = $params['temp_insert_id'];
				if (!exists_and_not_empty($config['temp_insert_id']['field']))
				{
					new mil_Exception (__FUNCTION__ . " : \$config['temp_insert_id']['field'] must not be empty."
						, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
					$config['temp_insert_id']['field'] = "";
				}
				else
				{
					if (!exists_and_not_empty($config['temp_insert_id']['value']))
					{
						new mil_Exception (__FUNCTION__ . " : \$config['temp_insert_id']['value'] must not be empty."
							, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
						$config['temp_insert_id']['value'] = "";
					}
				}
				$config['entity_id'] = "'".$config['temp_insert_id']['value']."'";
			}
			else
			{
				$config['temp_insert_id']['field'] = "";
				$config['temp_insert_id']['value'] = "";
				$config['entity_id'] = "'".$config['temp_insert_id']['value']."'";
			}
		}
		else
		{
			new mil_Exception (
				__FUNCTION__ . " : \$params['entity_id'] XOR \$params['temp_insert_id'] must be filled, but not noone, neither both"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}

		if (!exists_and_not_empty ($params['list_table']))
		{
			new mil_Exception (
				__FUNCTION__ . " : \$params['list_table'] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}
		else
		{
			$config['list_table'] = $params['list_table'];
		}

		$config['frontend_access'] = exists_and_not_empty($params['frontend_access']) ? $params['frontend_access'] : $params['frontend_access'];

		if (exists_and_not_empty($params['action'])) $config['action'] = $params['action'];
		else $config['action'] = array ();
		$config['action']['table_type'] = "join";

		$config['runas'] = $params['runas'];


		// #############################
		// Prepare variables for queries:
		$entity_id = $config['entity_id'];

		$entity_table = $config['entity_table'];
		$list_table = $config['list_table'];
		if (!exists_and_not_empty ($GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['join_table']))
		{
			new mil_Exception (__FUNCTION__ . " : \$GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['join_table'] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}
		$join_table = $GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['join_table'];


		if (!exists_and_not_empty ($GLOBALS['relationship'][$join_table]['many_to_one'][$list_table]))
		{
			new mil_Exception (__FUNCTION__ . " : \$GLOBALS['relationship'][$join_table]['many_to_one'][$list_table] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}
		if (!exists_and_not_empty ($GLOBALS['relationship'][$list_table]['one_to_many'][$join_table]))
		{
			new mil_Exception (__FUNCTION__ . " : \$GLOBALS['relationship'][$list_table]['one_to_many'][$join_table] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}
		if (!exists_and_not_empty ($GLOBALS['relationship'][$join_table]['many_to_one'][$entity_table]))
		{
			new mil_Exception (__FUNCTION__ . " : \$GLOBALS['relationship'][$join_table]['many_to_one'][$entity_table] must not be empty"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
		}

		$join_2_list = $GLOBALS['relationship'][$join_table]['many_to_one'][$list_table] . " = " . 
			$GLOBALS['relationship'][$list_table]['one_to_many'][$join_table]; 
		//$GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['join_2_list'];

		$KEY_join_2_entity = $GLOBALS['relationship'][$join_table]['many_to_one'][$entity_table];
		//$GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['KEY_join_2_entity'];

		$KEY_list_2_join = $GLOBALS['relationship'][$list_table]['one_to_many'][$join_table];
		//$GLOBALS['relationship'][$entity_table]['many_to_many'][$list_table]['KEY_list_2_join'];


		// columns
		$list_columns = get_show_columns ($list_table);
		$join_columns = get_show_columns ($join_table);

		//trace2file($list_columns, "list_columns");
		//trace2file ($join_columns, "join_columns");

		$list_fields_array;
		$join_fields_array;

		$nb_field = count ($list_columns['Field']);
		$i=1;
		foreach ($list_columns as $i => $field_name)
		{
			$can_vertically_access_field = can_vertically_access_field (	// security backend_access
				array(
					"manipulation" => "select"
					, "field_name" => $list_columns[$i]['Field']
					, "field_infos" => array (
						'field_direct' => array (
							'table' => $list_table
							, 'orgtable' => $list_table
						)
					)
					, 'runas' => $config['runas']
				)
			);

			if ($can_vertically_access_field)
			{
				$list_fields_array[] = "$list_table." . $list_columns[$i]['Field'];
			}
		}

		$nb_field = count ($join_columns['Field']);
		$i=1;
		foreach ($join_columns as $i => $join_field_show_columns)
		{
			//trace2file ($join_columns[$i]['Field']);
			$redundancy = false;
			// the following loop is here to avoid a redundance of field name. This could have a major impact on fields and their PK and FK.
			foreach ($list_columns as $j => $list_field_name)
			{
				if ($join_columns[$i]['Field'] === $list_columns[$j]['Field'])
				{
					//trace2file("BREAK !!! " . $join_columns[$i]['Field'] ." === ".$list_columns[$j]['Field']);
					//break 2;
					$redundancy = true;
				}
				else
				{
					//trace2file($join_columns[$i]['Field'] ." !== ".$list_columns[$j]['Field']);
				}
			}

			if ($redundancy === false)
			{
				$can_vertically_access_field = can_vertically_access_field (	// security backend_access
					array(
						"manipulation" => "select"
						, "field_name" => $join_columns[$i]['Field']
						, "field_infos" => array (
							'field_direct' => array (
								'table' => $join_table
								, 'orgtable' => $join_table
							)
						)
						, 'runas' => $config['runas']
					)
				);

				if ($can_vertically_access_field)
				{
					$full_field_name = "$join_table." . $join_columns[$i]['Field'];


					//if ($full_field_name !== $GLOBALS['relationship'][$join_table]['many_to_one'][$list_table])
					//{
					$join_fields_array[] = "$join_table." . $join_columns[$i]['Field'];
					//}
				}
			}
		}

		//trace2file($list_fields_array, "list_fields_array");
		//trace2file($join_fields_array, "join_fields_array");

		$list_fields_string = get_field_string ($list_fields_array);
		$join_fields_string = get_field_string ($join_fields_array);


		// ############################
		// work

		// #########
		// 1st query: Simple query fetching present items into a multiple selection list of the list table linked to the wanted entity (register, demand...)
		$select_1 = "SELECT\n";
		$select_1 .= "true as selected_in_multiselist_in_db\n";		// report the presence of this element in the selection
		$select_1 .= ", $list_fields_string, $join_fields_string\n";
		$from_1 = "FROM\n";
		$from_1 .= "$list_table\n";
		$from_1 .= "INNER JOIN $join_table ON $join_2_list\n";
		$where_1 = "WHERE\n";
		$where_1 .= "$KEY_join_2_entity = $entity_id\n";
		$sql_1 = $select_1 . $from_1 . $where_1;


		// #########
		// 2nd query: related to the config listing table. It serves only to display values which are not in the first query
		foreach ($join_fields_array as $i => $table_dot_field)
		{
			if ($table_dot_field === $KEY_join_2_entity) $join_fields_array[$i] = $entity_id;
			else $join_fields_array[$i] = "NULL";
		}
		$join_fields_string = get_field_string ($join_fields_array);

		$select_2 = "SELECT\n";
		$select_2 .= "false as selected_in_multiselist_in_db\n";		// report the absence of this element in the selection
		$select_2 .= ", $list_fields_string, $join_fields_string\n";
		$from_2 = "FROM $list_table\n";
		$where_2 = "WHERE\n";
		$where_2 .= "$list_table.enabled = true\n";
		$where_2 .= "AND $KEY_list_2_join NOT IN\n";
		$sql_2 = $select_2 . $from_2 . $where_2;


		// #########
		// 3rd query: Same query as the first query but the select clause is different and does the exclusion of what is already selected in the 1st query
		$select_3 = "SELECT\n";
		$select_3 .= "$KEY_list_2_join\n";		// report the absence of this element in the selection
		$sql_3 = $select_3 . $from_1 . $where_1;





		// #########
		// Global query
		$sql = "
			(
				$sql_1
			)
			UNION
			(
				$sql_2
				(
					$sql_3
				)
			)
			ORDER BY sort_index
			";

		//echo trace2web($sql, "sql");
		//trace2file ("", "", __FILE__, true);
		//trace2file ($sql, "sql", __FILE__);

		// #########
		// Exec
		$config = array (
			'sql' => $sql
			, 'frontend_access' => $config['frontend_access']
			, 'action' => $config['action']
			, 'temp_insert_id' => $config['temp_insert_id']
			, 'calling_FILE' => __FILE__
			, 'calling_LINE' => __LINE__
		);
		$this->select ($config);
	}


	/**
	 * Use this method when you also use a concurrent select_empty().
	 *
	 * @attention The parameter temp_insert_id must be the same in select_empty() and select_empty_multiselist() in order to create a link between both, so that
	 * 	both records, in the entity table and in the join table can be linked by this same identiticator.
	 * 
	 * Use this method when you also use this param in a concurrent select_empty()
	 *
	 * @param $params {associative array}
	 * 	@note Almost all params for this function are the same that params sent to select_multiselist().
	 * 	But here you must remove the entity_id parameter and you must add:
	 * 	- temp_insert_id (optional) {associative array}
	 * 	Use this when you insert a record for an entity, and also, a record for one or several join tables of this entity
	 * 	(for example, add info related to multiselists...). The parameter temp_insert_id must be the same in select_empty() and select_empty_multiselist()
	 * 	in order to create a link between both, so that both records, in the entity table and in the join table can be linked by this same identiticator.
	 * 	ex: Let's imagine that you need to insert a Person record. But you also need to input data about 
	 * 	one of the Person multislist such as groups he belongs to (thus there will be records (in a join table)). Then, in order to make the relation between 
	 * 	both tables, such a temp_insert_id is necessary to be received by the select_empty() for the Person table and also, the select_empty() for 
	 * 	the Person_Groups join table.
	 * 		- field: {string} The full name of the field that must not be returned empty ex: "Person.person_id" or "Person_Profession.person_id"
	 * 		- value: {string} This value is directly linked to the javascript function dco_get_temp_insert_id() (see datamalico.lib.js and the example below)
	 *
	 * @return {associative array} See the returned value of select_multiselist().
	 *
	 * Example of call:
	 * @code
	 * $dco = new datamalico_server_dbquery ($frontend_access);
	 * $dco->select_empty_multiselist ( array (
	 *	'entity_table' => "data_demand"
	 *	, 'temp_insert_id' => $_POST['temp_insert_id']	// See also select_empty() documentation to see how Javascript generates this value on the client page.
	 *	, 'list_table' => "config_service"
	 *	, 'frontend_access' => array () // optional
	 *	, 'action' => array (		// optional
	 *		'save_mode' => "global_save"
	 * 	)
	 *	, 'calling_FILE' => __FILE__	// optional
	 *	, 'calling_LINE' => __LINE__	// optional
	 * ));
	 * $ajaxReturn = $dco->output;
	 * @endcode
	 */
	public function select_empty_multiselist ($params)
	{
		unset($params['entity_id']);
		$this->select_multiselist($params);
	}


	/**
	 * Operates a DELETE statement, and actually calls the dco_delete_api() function.
	 *
	 * @params $params
	 * 	- table_name (mandatory) {string}
	 * 	- conditions (mandatory) {associative array} Specify conditions (WHERE clause) for the 'DELETE' query.
	 * 	- runas: (optional) See your own function:
	 * 		- can_access_table() in backend_access.conf.php
	 * 	- calling_FILE: (optional) Help to log a good error message in case of error.
	 * 	- calling_LINE: (optional) Help to log a good error message in case of error.
	 *
	 * @return The result of the function dco_delete_api()
	 *
	 * See also dco_delete_api()
	 */
	public function delete ($params)
	{
		$this->output = dco_delete_api ($params);
	}

	/**
	 * Operates an INSERT or an UPDATE statement, and actually calls the dco_upsert_api() function.
	 *
	 * @params $params
	 * 	- table_name (mandatory) {string}
	 * 	- fields (mandatory) {associative array} Specify fields for the 'INSERT' or 'UPDATE' query.
	 * 	- conditions (optional, default is unset) {associative array} Specify conditions (WHERE clause) for the 'UPDATE' query.
	 * 	- runas: (optional) See your own functions:
	 * 		- can_access_table() in backend_access.conf.php
	 * 		- can_vertically_access_field() in backend_access.conf.php
	 * 	- calling_FILE: (optional) Help to log a good error message in case of error.
	 * 	- calling_LINE: (optional) Help to log a good error message in case of error.
	 *
	 * @return none, but fills up the datamalico_server_dbquery::output with the result of dco_upsert_api().
	 *
	 * See also dco_upsert_api()
	 *
	 * Example of code:
	 * @code
	 * // INSERT (no condition):
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->upsert(array (
	 *      'table_name' => "data_registered"
	 *      , 'fields' => array (
	 *            'webuser_id' => $webuser_id
	 *            , 'firstname' =>  "Arnold"
	 *      )
	 *      , 'calling_FILE' => __FILE__
	 *      , 'calling_LINE' => __LINE__
	 * ));
	 *
	 * // UPDATE (with condition):
	 * $dco = new datamalico_server_dbquery ();
	 * $dco->upsert(array (
	 *      'table_name' => "data_registered"
	 *      , 'fields' => array (
	 *            'webuser_id' => $webuser_id
	 *            , 'firstname' =>  "Arnold"
	 *      )
	 *      , 'conditions' => array (
	 *            'reg_id' => 18
	 *      )
	 *      , 'calling_FILE' => __FILE__
	 *      , 'calling_LINE' => __LINE__
	 * ));
	 *
	 * // ###############################################
	 * // As a result, the dco object will be as follows: (for INSERTION)
	 * dco: datamalico_server_dbquery Object
	 * (
	 *     [input] => Array
	 *         (
	 *             [pagination] => 
	 *         )
	 * 
	 *     [output] => Array
	 *         (
	 *             [insert_api] => Array
	 *                 (
	 *                     [metadata] => Array
	 *                         (
	 *                         	...
	 *                         )
	 * 
	 *                     [update_api] => Array
	 *                         (
	 *                         	...
	 *                         )
	 * 
	 *                 )
	 * 
	 *         )
	 * 
	 *     [timing] => Array
	 *         (
	 *             [begin] => 
	 *             [laps] => 
	 *             [end] => 
	 *         )
	 * 
	 * )
	 * @endcode
	 */
	public function upsert ($params)
	{
		//echo trace2web ($params, "datamalico_server_dbquery->upsert()");
		$this->output = dco_upsert_api ($params);
	}
}










/**
 * Upsert method:
 *
 * @return {associative array} The array returned either by insert_api() or by update_api()
 *
 * Example of call:
 * @code
 * $upsert_api_config = array (
 * 		'table_name' => $meta_and_data['data_itself']['table_name']	// mandatory
 * 		, 'fields' => $meta_and_data['data_itself']['fields']		// $fields['credate'] = "2012-06-01 10:00:03"; $fields['creby'] = 5
 * 		, 'conditions' => $meta_and_data['data_itself']['conditions']	// (optional) if absent, then INSERT, if present, then UPDATE.
 * 		, 'calling_FILE' => __FILE__
 * 		, 'calling_LINE' => __LINE__
 * 		);
 * $ajaxReturn[$manipulation][$field] = dco_upsert_api ($upsert_api_config);
 * @endcode
 *
 * @todo This upsert is not a real upsert, because in a real upsert if the element (table_name + fields + conditions) doen't exist, then it is inserted.
 * 	But on the other hand, such a real upsert, making first a SELECT, and then INSERT or UPDATE is quite long to process and uses ressources.
 */
function dco_upsert_api ($params)
{
	//echo trace2web($params, "dco_upsert_api () : params");

	// ############################
	// Params and config
	$function_return['metadata']['returnCode'];
	$function_return['metadata']['returnMessage'];
	$function_return['metadata']['debugMessage'];

	$config = array(); //foreach ($params as $key => $value) {$config[$key] = $value;}; 	// foreach ($params as $key => $value) {$$key = $value;};s

	if (exists_and_not_empty($params['table_name'])) $config['table_name'] = $params['table_name'];
	else return;

	//if (exists_and_not_empty($params['fields'])) $config['fields'] = $params['fields'];
	$config['fields'] = exists_and_not_empty($params['fields']) ? $params['fields'] : null;	// can be null for deletion

	if ($config['fields'] !== null && gettype($config['fields']) !== "array") return;	// means that the format for this var is not understandable

	//if (exists_and_not_empty($params['field_name'])) $config['field_name'] = $params['field_name'] else return;
	//if (exists_and_not_empty($params['field_new_value'])) $config['field_new_value'] = $params['field_new_value'] else return;

	// if no condition, then insertion
	if (exists_and_not_empty($params['conditions'])) $config['conditions'] = $params['conditions'];

	// tracing code
	$config['calling_FILE'] = exists_and_not_empty($params['calling_FILE']) ? $params['calling_FILE'] : null;
	$config['calling_LINE'] = exists_and_not_empty($params['calling_LINE']) ? $params['calling_LINE'] : null;
	$config['time'] = nowCET ();

	$config['runas'] = $params['runas'];


	// ############################
	// work

	//echo trace2web($config, "dco_api-config");

	// action
	/*if (is_a_join_table ($config['table_name']))	// or if want to update a primkey, (then it's a join table)
	{
		dco_delete_api();
		insert_api();
	}
	else
	{*/
	if (exists_and_not_empty($config['conditions']))
	{
		$function_return = update_api ($config);
	}
	else
	{
		$function_return = insert_api ($config);
	}
	//}

	return $function_return;
}


/**
 * Deletes records according to parameters
 * @brief In any SQL language, the DELETE instruction is defined by the PRESENCE of conditions and the ABSENCE of fields.
 * @param $params {associative array}
 * 	An associative array containing:
 * 	- table_name: {string} nbame of the table where to delete one or several records
 * 	- conditions: {associative array} containing conditions
 * 		- {field_name_of_the_1st_condition}: {string or integer}
 * 		- {field_name_of_the_2nd_condition}: {string or integer}
 * 		- ... Put as conditions as you wish
 *
 * This no longer belongs to the param.
 * @warning There must not be any $params['fields']. 
 * As you never delete fields (defferent from a drop column), if ever there is this $params['fields'], 
 * then the function thinks that the coder makes an error, and stop immadiately the function, so that no delete instruction is executed.
 * @return {associative array} a report on the deletion
 * 	- delete_api: {associative array}
 * 		- metadata: {associative array}
 * 			- returnCode: {string} (default is 'ERROR') Either or : "DELETION_SUCCESSFULL", "DELETION_ERROR", "ERROR_NO_RIGHT_TO_DELETE"
 * 			- returnMessage: {string} (default is $GLOBALS['mil_lang_common']['ERROR']) related to the returnCode, but in a correct form.
 * 			- params {associative array} Params received by the function
 * 				- table_name
 * 				- conditions:
 * 					- field_a
 * 					- field_b
 * 					- ...
 * 				- calling_FILE: (optional)
 * 				- calling_LINE: (optional)
 * 				- time: (optional)
 * 				- runas: (optional)
 * 			- sql_query: {string} (optional)
 * 			- affected_rows: {string} (optional, default is 'ERROR') if the delete execution has succeed.
 * 			- debugMessage: {string} (optional)
 */
function dco_delete_api ($params)
{
	//echo trace2web($params, "delete_api-params");

	// ############################
	// Params and config
	$function_return['delete_api']['metadata']['returnCode'] = "ERROR";
	$function_return['delete_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
	$function_return['delete_api']['metadata']['affected_rows'];
	$function_return['delete_api']['metadata']['debugMessage'];

	$config = array(); //foreach ($params as $key => $value) {$config[$key] = $value;}; 	// foreach ($params as $key => $value) {$$key = $value;};s

	if (exists_and_not_empty($params['table_name'])) $config['table_name'] = $params['table_name'];
	else return;

	// security : if there are fields, then this must be something else than a delete, so exit
	if (exists_and_not_empty($params['fields'])) return;

	// if no condition, exit
	if (exists_and_not_empty($params['conditions'])) $config['conditions'] = $params['conditions'];
	else return;

	$config['runas'] = $params['runas'];

	// tracing data
	$config['calling_FILE'] = exists_and_not_empty($params['calling_FILE']) ? $params['calling_FILE'] : null;
	$config['calling_LINE'] = exists_and_not_empty($params['calling_LINE']) ? $params['calling_LINE'] : null;
	$config['time'] = nowCET ();


	$function_return['delete_api']['metadata']['params'] = $params;
	//$function_return['delete_api']['metadata']['config'] = $config;

	// ############################
	// work

	$table_name = $params['table_name'];

	$can_access_table = can_access_table (
		array (
			'manipulation' => "delete"
			, 'table_name' => $table_name
			, 'runas' => $config['runas']
		)
	);

	if ($can_access_table === true)
	{
		//echo trace2web($config, "config");
		$full_string_AND_condition = get_full_string_AND_condition ($config['conditions']);
		if (!exists_and_not_empty($full_string_AND_condition)) return;


		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$sql = "
			DELETE FROM `$table_name`
			WHERE $full_string_AND_condition;		
		";
		$function_return['delete_api']['metadata']['sql_query'] = $sql;

		//trace("dco_delete_api ==> : $sql");
		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$function_return['delete_api']['metadata']['returnCode'] = "DELETION_SUCCESSFULL";
			$function_return['delete_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['DELETION_SUCCESSFULL'];
			$function_return['delete_api']['metadata']['affected_rows'] = $mysqli_con->affected_rows;		// insert, update, delete, select
		}
		else
		{
			$function_return['delete_api']['metadata']['returnCode'] = "DELETION_ERROR";
			$function_return['delete_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
			new mil_Exception (
				__FUNCTION__ . " : This is not possible to execute the request: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}

		//$mysqli_con->close();
	}
	else
	{
		$function_return['delete_api']['metadata']['returnCode'] = "ERROR_NO_RIGHT_TO_DELETE";
		$function_return['delete_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_NO_RIGHT_TO_DELETE'];
	}

	return $function_return;
}





// ####################################################################################
// ####################################################################################
// ####################################################################################
// ####################################################################################
// ####################################################################################
//
// PRIVATE FUNCTIONS Don't use directly
//
// ####################################################################################
// ####################################################################################


/**
 * The function doing a real SQL INSERT.
 *
 * @warning Keep in mind that an insert is a 'blank' insert. A blank insert is: 
 * @code
 * INSERT INTO `$table_name` () VALUES ()
 * @endcode
 * This blank insert is followed by one or several updates. Thus, the returned value of the function is: [metadata]
 * of the SQL INSERT, followed by [update_api]
 *
 * @return {associative array} A report on the insertion called by datamalico_server_dbquery::upsert()
 * 	- insert_api: {associative array}
 * 		- metadata: {associative array}
 * 			- returnCode: {string}
 * 			- returnMessage: {string}
 * 			- params {associative array} Params received by the function
 * 				- table_name: {string}
 * 				- fields: {associative array}
 * 					- field_1
 * 					- field_2
 * 					- ...
 * 				- calling_FILE: (optional)
 * 				- calling_LINE: (optional)
 * 				- time: (optional)
 * 				- runas: (optional)
 * 			- sql_query: {string}
 * 			- affected_rows
 * 			- insert_id: {int} (optional) If a row is inserted, insert_id is the id of this just inserted row.
 * 				Note, that the following update relies on this just inserted id.
 * 			- update_api: See the returned value of update_api()
 * 			- debugMessage: {string} (optional)
 * 		- update_api: {associative array} Is the result of the update(s) done after the blank INSERT.
 * 			See the update_api() function.
 *
 * @warning It may happen that a row after being correctly 'blank' inserted (eg: INSERT INTO `$table_name` () VALUES ();), is not correctly updated.
 * - Why?
 * - Solution applied:
 * 	- pre-calculate the next object id and insert with this object.
 * 		- SELECT AUTO_INCREMENT as next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = '$table_name';
 * 		- SELECT MAX(id)+1 FROM table AS next_id
 * 		- SELECT id+1 as next_id FROM table ORDER BY id ASC LIMIT 1
 * - Solutions if the problem occurs again:
 * 	- LOCK TABLES (but check if it is really good on MyIsam tables.
 * 	- check more in detail the function get_autoincrement_field()
 */
function insert_api ($params)
{
	//echo trace2web ($params, "insert_api-params");

	// ############################
	// Params and config
	$function_return['insert_api']['metadata']['returnCode'] = "ERROR";
	$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
	$function_return['insert_api']['metadata']['sql_query'];
	$function_return['insert_api']['metadata']['debugMessage'];
	$function_return['insert_api']['metadata']['affected_rows'];
	$function_return['insert_api']['metadata']['insert_id'] = null;
	$function_return['insert_api']['records'];
	$function_return['insert_api']['field_structure'];

	$config['runas'] = $params['runas'];

	// ###################
	// The only insert
	$table_name = $params['table_name'];

	$can_access_table = can_access_table (
		array (
			'manipulation' => "insert"
			, 'table_name' => $table_name
			, 'runas' => $config['runas']
		)
	);

	$function_return['insert_api']['metadata']['params'] = $params;

	$config['calling_FILE'] = $params['calling_FILE'];
	$config['calling_LINE'] = $params['calling_LINE'];

	if ($can_access_table === true)
	{
		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$autoincrement_field = get_autoincrement_field ($params['table_name']);

		if (!exists_and_not_empty($autoincrement_field))
		{
			$function_return['insert_api']['metadata']['returnCode'] = "ERROR_ON_INSERT_NO_AUTOINC";
			$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_ON_INSERT_NO_AUTOINC'] . 
				" " . $params['table_name'];
			return $function_return;
		}

		// ###################
		// Prepare the next_id (because in the past there were problems with concurrent insertions because the insert and update could be delayed...)
		$db_name = $GLOBALS['DB']['DB_name'];
		$sql = "
			SELECT AUTO_INCREMENT as next_id FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db_name' AND TABLE_NAME = '$table_name';
		";

		//echo trace2web ($sql, "sql");

		$records;
		$field_structure;

		//echo trace2web (__LINE__);
		if ($mysqli_result = $mysqli_con->query($sql))
		{
			//echo trace2web (__LINE__);
			$nbRes = $mysqli_result->num_rows;	// SELECT
			//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

			if ($nbRes === 1) {
				//echo trace2web (__LINE__);
				$records[1] = $mysqli_result->fetch_assoc();
				$next_id = $records[1]['next_id'];
			}
			else if ($nbRes == 0) {
				//echo trace2web (__LINE__);
				new mil_Exception (__FUNCTION__ . " : Should not happen: $sql"
					. trace2web($mysqli_con->error, "mysqli_con->error")
					, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
				$function_return['insert_api']['metadata']['returnCode'] = "ERROR_ON_INSERT_CANT_GET_AUTOINC";
				$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_ON_INSERT_CANT_GET_AUTOINC'] . 
					" " . $params['table_name'];
			}

			//$mysqli_result->free();
		} else {
			//echo trace2web (__LINE__);
			$function_return['insert_api']['metadata']['returnCode'] = "ERROR_ON_INSERT_CANT_GET_AUTOINC";
			$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_ON_INSERT_CANT_GET_AUTOINC']
				. " " . $params['table_name'];
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			return $function_return;
			//echo debugDisplayTable($mysqli_con->error, "mysqli_con->error");
		}

		//echo trace2web ($records, "records next_id");



		// ###################
		// Real insertion
		//$sql = "INSERT INTO `$table_name` () VALUES ();";
		$sql = "
			INSERT INTO  `$table_name` (`$autoincrement_field`) VALUES ($next_id);
		";

		//echo trace2web ($sql, "sql");

		$function_return['insert_api']['metadata']['sql_query'] = $sql;
		//trace("insert_api ==> $sql");

		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$function_return['insert_api']['metadata']['affected_rows'] = $mysqli_con->affected_rows;

			if ($function_return['insert_api']['metadata']['affected_rows'] === 0)
			{
				$function_return['insert_api']['metadata']['returnCode'] = "NO_INSERT_DONE";
				$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['NO_INSERT_DONE'];
			}
			else if ($function_return['insert_api']['metadata']['affected_rows'] === 1)
			{
				$function_return['insert_api']['metadata']['returnCode'] = "INSERT_SUCCESSFULL";
				$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['INSERT_SUCCESSFULL'];

				$just_inserted_id = $mysqli_con->insert_id;

				if ((int) $next_id === (int) $just_inserted_id)
				{
					$function_return['insert_api']['metadata']['insert_id'] = (int) $just_inserted_id;
				}
				else
				{
					$function_return['insert_api']['metadata']['returnCode'] = "ERROR_ON_INSERT_NEXTID_VS_JUSTINSERTEDID";
					$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_ON_INSERT_NEXTID_VS_JUSTINSERTEDID'] . 						" ($next_id vs $just_inserted_id)";

					new mil_Exception (
						__FUNCTION__ . " : " . $function_return['insert_api']['metadata']['returnMessage']
						, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
				}
			}
		}
		else
		{
			$function_return['insert_api']['metadata']['returnCode'] = "ERROR_ON_INSERT";
			$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_ON_INSERT'] . 
				" " . $GLOBALS['mil_lang_common']['webmaster_is_notified'];
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}




		// ###################
		// updates for the remaining fields
		if ($function_return['insert_api']['metadata']['affected_rows'] === 1)
		{
			// primary keys
			/*
			$primary_keys = get_autoincrement_field ($params['table_name']);
			// populate the insert_id array:
			for ($i=0; $i<count($primary_keys); $i++)
			{
				$function_return['insert_api']['metadata']['insert_id'][$i] = $mysqli_con->insert_id;
			}
			$function_return['insert_api']['metadata']['insert_id'][$i] = $mysqli_con->insert_id;
			 */

			$config = array (
				'table_name' => $params['table_name']
				, 'fields' => $params['fields']
				, 'conditions' => array ("$autoincrement_field" => $function_return['insert_api']['metadata']['insert_id']) //array_combine($primary_keys, $function_return['insert_api']['metadata']['insert_id'])
				, 'runas' => $config['runas']
				, 'calling_FILE' => $params['calling_FILE']
				, 'calling_LINE' => $params['calling_LINE']
			);

			$function_return['insert_api'] = array_merge (
				$function_return['insert_api']
				, update_api ($config)
			);
		}
		//$mysqli_con->close();
	}
	else
	{
		$function_return['insert_api']['metadata']['returnCode'] = "ERROR_NO_RIGHT_TO_INSERT";
		$function_return['insert_api']['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR_NO_RIGHT_TO_INSERT'];
	}

	return $function_return;
}


/**
 * The function browsing into fields to be updated sent by datamalico_server_dbquery::upsert().
 *
 * @return {associative array} A report on the update
 * 	- update_api: {associative array}
 * 		- metadata: {associative array}
 * 			- params {associative array} Params received by the function
 * 				- table_name: {string}
 * 				- fields: {associative array}
 * 					- field_1
 * 					- field_2
 * 					- ...
 * 				- conditions: {associative array}
 * 					- field_a
 * 					- field_b
 * 					- ...
 * 				- calling_LINE: (optional)
 * 				- time: (optional)
 * 				- runas: {string} (optional)
 * 			- (no returnCode, sql_query... contrary to the deletion or the insertion because, update_api is just a transitional 
 * 				function giving atomic instructions to update_one_field_api() )
 * 		- update_set: {numerical array} Array containing reports for each atomic update done
 * 			- 0: See the returned value of update_one_field_api()
 * 			- 1: (optional)
 * 			- 2: (idem)
 * 			- ...
 */
function update_api ($params)
{
	//echo trace2web($params, "update_api-params");

	$function_return = array();

	$full_string_AND_condition = get_full_string_AND_condition ($params['conditions']);

	$function_return['update_api']['metadata']['params'] = $params;

	foreach ($params['fields'] as $field_name => $field_new_value)
	{
		$config = array (
			'table_name' => $params['table_name']
			, 'field_name' => $field_name
			, 'field_new_value' => $field_new_value
			, 'full_string_AND_condition' => $full_string_AND_condition
			, 'runas' => $params['runas']
			, 'calling_FILE' => $params['calling_FILE']
			, 'calling_LINE' => $params['calling_LINE']
		);
		$function_return['update_api']['update_set'][] = update_one_field_api ($config);
	}

	return $function_return;
}

/**
 * The function doing a real SQL UPDATE.
 *
 * @return {associative array} A report on one unique UPDATE (done on one field) called called by update_api(), called by datamalico_server_dbquery::upsert()
 * 	- metadata: {associative array}
 * 		- returnCode: {string}
 * 		- returnMessage: {string}
 * 		- params: {associative array} Params received by the function
 * 			- table_name: {string}
 * 			- field_name: {string}
 * 			- field_new_value
 * 			- full_string_AND_condition: {string}
 * 			- runas: {string} (optional)
 * 			- calling_FILE: {string} (optional)
 * 			- calling_LINE: {string} (optional)
 * 			- time: (optional)
 * 		- sql_query: {string} The sql query done in order to make the atomic (one field only) update.
 * 		- affected_rows
 * 		- value_just_inserted: {string}
 * 		- debugMessage: {string} (optional)
 */
function update_one_field_api ($params)
{
	//echo trace2web($config, "update_one_field_api-config");

	// ############################
	// Params and config
	$function_return['metadata']['returnCode'] = "ERROR";
	$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['ERROR'];
	$function_return['metadata']['params'] = $params;
	$function_return['metadata']['debugMessage'];
	$function_return['metadata']['sql_query'];
	$function_return['metadata']['affected_rows'];


	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	// ###################
	// config
	$table_name = $params['table_name'];
	$field_name = $params['field_name'];
	$field_new_value = $mysqli_con->real_escape_string($params['field_new_value']);
	$full_string_AND_condition = $params['full_string_AND_condition'];

	$config['runas'] = $params['runas'];
	$config['calling_FILE'] = $params['calling_FILE'];
	$config['calling_LINE'] = $params['calling_LINE'];


	// ###################
	// check about field type
	// 	This WHERE condition can accept single quotes even for numerical data !!!
	$sql = "
		SELECT `$field_name`
		FROM `$table_name`
		LIMIT 1
		;
	";
	//trace("check about field type: $sql");

	$records;
	$field_structure;

	if ($mysqli_result = $mysqli_con->query($sql))
	{
		//$nbRes = $mysqli_result->num_rows;
		//if ($nbRes !== 1) new mil_Exception ("There must be only one result for: $sql", "1201111240", "WARN", __FILE__ .":". __LINE__ );

		$field_structure = get_field_structure ($sql);

		$mysqli_result->free();
	}
	else
	{
		new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
			. trace2web($mysqli_con->error, "mysqli_con->error")
			, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
	}

	$quote = "";
	if ($field_structure[$field_name]['use_quotes_for_db_insertion'])
	{
		$quote = "'";

		// Sepcial case to nullify timestamp, (date). In order to avoid update to 0000-00-00 00:00:00 when NULL or "" or 0 is desired:
		if ($field_structure[$field_name]['show_columns']['Type'] === "timestamp")
		{
			if (exists_and_not_empty($field_new_value))
			{
				if (
					strtolower($field_new_value) === "null"
					|| (bool) (int) (string) $field_new_value === false
				)
				{
					$quote = "";
					$field_new_value = "null";
				}
			}
		}
	}
	else 
	{
		if (!exists_and_not_empty($field_new_value))
		{
			$field_new_value = "null";
		}
	}


	// ###################
	// update itself

	$can_vertically_access_field = can_vertically_access_field (
		array(
			"manipulation" => "update"
			, "field_name" => $field_name
			, "field_infos" => $field_structure[$field_name]
			, 'runas' => $config['runas']
		)
	);

	//trace("can_vertically_access_field:$can_vertically_access_field");
	if ($can_vertically_access_field)
	{
		// 	This WHERE condition can accept single quotes even for numerical data !!!
		$sql = "
			UPDATE `$table_name`
			SET `$field_name` = $quote$field_new_value$quote
			WHERE $full_string_AND_condition
			;
		";

		$function_return['metadata']['sql_query'] = $sql;

		//trace ("update_one_field_api ==> $sql");
		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$affected_rows = $mysqli_con->affected_rows;

			//trace("mysqli_result -> num_rows:" . $mysqli_result->num_rows);
			//var_dump ($mysqli_con->affected_rows); //trace("mysqli_con -> affected_rows:" . $mysqli_con->affected_rows);

			if ($affected_rows === 0 ) $function_return['metadata']['returnCode'] = "NO_ROW_UPDATED";
			else if ($affected_rows === 1 ) $function_return['metadata']['returnCode'] = "1_ROW_UPDATED";
			else if ($affected_rows > 1 ) $function_return['metadata']['returnCode'] = "X_ROWS_UPDATED";

			$returnCode = $function_return['metadata']['returnCode'];

			$function_return['metadata']['returnMessage'] = $affected_rows . " " . $GLOBALS['mil_lang_common'][$returnCode];
			$function_return['metadata']['affected_rows'] = $affected_rows;
		}
		else
		{
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}
	}
	else
	{
		$returnCode = "ERROR_NO_ACCESS_TO_UPDATE";
		$function_return['metadata']['returnCode'] = "ERROR_NO_ACCESS_TO_UPDATE";
		$function_return['metadata']['returnMessage'] = $GLOBALS['mil_lang_common'][$returnCode];
		$function_return['metadata']['affected_rows'] = 0;
	}



	// ###################
	// re-Get the value just inserted and return it to the calling page
	$can_vertically_access_field = can_vertically_access_field (
		array(
			'manipulation' => "select"
			, 'field_name' => $field_name
			, 'field_infos' => $field_structure[$field_name]
			, 'runas' => $config['runas']
		)
	);

	//trace("can_vertically_access_field:$can_vertically_access_field");
	if ($can_vertically_access_field)
	{
		$sql = "
			SELECT `$field_name`
			FROM `$table_name`
			WHERE `$field_name` = $quote$field_new_value$quote
			LIMIT 1
			;
		";
		//trace($sql);

		$records;
		$field_structure;


		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$nbRes = $mysqli_result->num_rows;

			if ($nbRes == 0)
			{
				new mil_Exception (__FUNCTION__ . " : Should not happen: $sql"
					. trace2web($mysqli_con->error, "mysqli_con->error")
					, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
			}
			else if ($nbRes >= 1)
			{
				for ($l = 1; $row = $mysqli_result->fetch_assoc(); $l++)
				{
					$records[$l] = $row;
					$function_return['metadata']['value_just_inserted'] = $records[$l][$field_name];
				}
			}

			$mysqli_result->free();
		}
		else
		{
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}
	}


	//$mysqli_con->close();

	return $function_return;
}

// Returns the names of the fields belonging to the primary key
function get_primary_keys ($table_name)
{
	$primary_keys = array();

	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	$sql = "
		SHOW COLUMNS FROM `$table_name`;
	";

	$records;
	$field_structure;


	if ($mysqli_result = @$mysqli_con->query($sql))
	{
		$nbRes = @$mysqli_result->num_rows;

		if ($nbRes == 0)
		{
			new mil_Exception (__FUNCTION__ . " : The table $table_name has no column: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
		else if ($nbRes >= 1)
		{
			for ($l = 1; $row = @$mysqli_result->fetch_assoc(); $l++)
			{
				if($row['Key'] === 'PRI')
				{
					$primary_keys[$l] = $row['Field'];
				}
			}
		}

		@$mysqli_result->free();
	}
	else
	{
		if ($table_name !== "CREATED_INTO_THE_SELECT_CLAUSE")
		{
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
	}

	//$mysqli_con->close();

	return $primary_keys;
}


/**
 * Returns the only field name which is auto_increment
 */
function get_autoincrement_field ($table_name)
{
	$auto_increment;

	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	$sql = "
		SHOW COLUMNS FROM `$table_name` WHERE EXTRA = 'auto_increment';
	";

	$records;
	$field_structure;


	if ($mysqli_result = /*@*/$mysqli_con->query($sql))
	{
		$nbRes = /*@*/$mysqli_result->num_rows;

		if ($nbRes == 0)
		{
			new mil_Exception (__FUNCTION__ . " : The table $table_name has no column: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
		else if ($nbRes >= 1)
		{
			for ($l = 1; $row = @$mysqli_result->fetch_assoc(); $l++)
			{
				if($row['Extra'] === 'auto_increment')
				{
					$auto_increment = $row['Field'];
				}
			}
		}

		/*@*/$mysqli_result->free();
	}
	else
	{
		if ($table_name !== "CREATED_INTO_THE_SELECT_CLAUSE")
		{
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
	}

	//$mysqli_con->close();

	return $auto_increment;
}



// Returns the only field name whidh is auto_increment
function get_show_columns ($table_name, $column_name = null)
{
	//trace($table_name, $column_name);

	$columns = array();

	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();	

	$sql;

	if ($column_name === null)
	{
		$sql = "
			SHOW COLUMNS FROM `$table_name`;
		";
	}
	else
	{
		$sql = "
			SHOW COLUMNS FROM `$table_name`
			WHERE Field = '$column_name';
		";
	}
	//trace($sql);

	$records;
	$field_structure;


	if ($mysqli_result = @$mysqli_con->query($sql))
	{
		$nbRes = @$mysqli_result->num_rows;

		if ($nbRes == 0)
		{
			new mil_Exception (__FUNCTION__ . " : The table $table_name has no column: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
		else if ($nbRes >= 1)
		{
			for ($l = 1; $row = @$mysqli_result->fetch_assoc(); $l++)
			{
				$columns[$l] = $row;
			}
		}

		@$mysqli_result->free();
	}
	else
	{
		if ($table_name !== "CREATED_INTO_THE_SELECT_CLAUSE")
		{
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql"
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		}
	}

	//$mysqli_con->close();

	//echo trace2web($columns, "columns");


	// if only for one field
	if ($column_name !== null)
	{
		return $columns[1];
	}

	return $columns;
}

/**
 * Returns the field structure related to fields of the select clause of the sql sent.
 *
 * @return $field_structure {associative array} having this form:
 * - $field_structure[$field_name]
 * 	- field_direct
 * 	- show_columns
 * 	- type_human_readable
 * 	- use_quotes_for_db_insertion
 * 	- frontend_access
 *
 * Example of call:
 * @code
 * $country_id_values = get_the_valuelist (
 * 	"SELECT country_id as db_stored_value, $lang as value_to_be_displayed FROM config_countries ORDER BY $lang"
 * 	, " --- Please choose a value --- "
 * );
 * 
 * $frontend_access['data_mytable'] = array(
 * 	"id" => array (
 * 		'field_label' => "Name of country"	// not something like : "country_name", but like "Name of country"
 * 		, 'access_rights' => "read"		// "read", "write"
 * 		, 'form_field_type' => "text"		// "text", "textarea", "select"
 * 		, 'valuelist' => array ()		// $valuelist['index'] => db_stored_value  (index of valuelist = db_stored_value, value = value to be displayed).
 * 		// could also be : $country_id_values
 * 		, 'maxlength' => 20			// maxlength of the field itself in the db.
 * 		, 'max_display_length' => 50		// equals to size of an input text field or to rows of textarea
 * 	)
 * 	, 'demand_id' => ""
 * 	, 'demand_title' => ""
 * );
 * @endcode
 */
function get_field_structure ($sql, $frontend_access = array(), $action = array())
{
	//echo trace2web($frontend_access, "frontend_access");
	// #############################################
	// Part for the $field_structure
	$field_structure;

	//global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	// ###########################################################
	// this is here very important to prepare because in preparing, the fetch_field_direct returns better table and orgtable than without preparation
	// ###########################################################
	//if ($mysqli_result = $mysqli_con->prepare($sql))
	if ($mysqli_result = $GLOBALS['dbcon']->prepare($sql))
	{
		$metadata = $mysqli_result->result_metadata();

		$numFields = $mysqli_result->field_count;//mysql_num_fields($result_resource);
		for ($i=0; $i < $numFields; $i++)
		{
			$finfo = $metadata->fetch_field_direct($i);



			/**<
				$field_structure[$field_name]['field_direct']['name'] {string} returned by the fetch_field_direct() of the mysqli php library
				- According to the mysqli API, this value MUST BE in this order:
					- alias 
					- OR field source name
					- OR just select clause instruction if created into the select clause
						- For select clause instruction
						- (eg: Hello for SELECT 'Hello' or CURTIME() for SELECT CURTIME()
				- in case of CREATED INTO THE SELECT CLAUSE : as it must be
				- in case of VIEW : as it must be
				- in case of a CALCULATED FIELD : as it must be
				- in case of UNION : as it must be
				- ==> the most reliable
			 */
			$name = $finfo->name;

			/**<
				$field_structure[$field_name]['field_direct']['orgname'] {string} returned by the fetch_field_direct() of the mysqli php library
				- According to the mysqli API, this value MUST BE in this order:
					- column source name 
					- OR empty if field created into the select clause ==> CAUTION, DO NOT rely on it
				- in case of CREATED INTO THE SELECT CLAUSE : as it must be
				- in case of VIEW : There is a BUG, because the alias of the view is returned, but not the original field name of the original table.
					Thus, for any view, we advise you to use as alias, the same fieldname as in the original table.
				- in case of a CALCULATED FIELD : this orgname is the same of the name 
					- (eg: mil_v_demand_status.publication_status_real_time, or mil_v_demandLastHisto.demandLastHisto which is MAX())
					- ==> CAUTION, DO NOT rely too much on it
				- in case of UNION : as it must be
				- ==> CAUTION, DO NOT rely too much on it (php5.2.17 and mysql Ver 14.12 Distrib 5.0.92)
			 */
			$orgname = $finfo->orgname;

			/**<
				$field_structure[$field_name]['field_direct']['table'] {string} returned by the fetch_field_direct() of the mysqli php library
				- According to the mysqli API, this value MUST BE in this order:
					- alias in the FROM clause if any 
					- OR table name 
					- OR empty if field created into the select clause ==> CAUTION, DO NOT rely on it
				- in case of CREATED INTO THE SELECT CLAUSE : as it must be
				- in case of VIEW : Name of the view itself
				- in case of a CALCULATED FIELD : Can be the name of the view if in a view
				- in case of UNION : as it must be
				- ==> Seems to be fairly ok
			 */
			$table = $finfo->table;

			/**<
				$field_structure[$field_name]['field_direct']['orgtable'] {string} returned by the fetch_field_direct() of the mysqli php library
				- According to the mysqli API, this value MUST BE in this order:
					- table source name 
					- OR empty if field created into the select clause 
					- OR view name (bug) if one unique table as source
				- in case of VIEW :
					- For a view with a unique table as source (such as mil_v_demand_status) orgname is NOT the one from the orgtable but the view name...
					- For a view with several tables as source (such as mil_v_demandLastHisto) this field is really the orgtable name...
					- ==> CAUTION, DO NOT rely too much on it (php5.2.17 and mysql Ver 14.12 Distrib 5.0.92)
				- in case of a CALCULATED FIELD : as it must be
				- in case of UNION : as it must be
				- ==> Seems to be fairly ok, except for view with one unique table as source
			 */
			$orgtable = $finfo->orgtable;


			// ####################################
			// What reference?
			// 	- orgname or name?
			// 	- orgtable or table?
			// At this step, if above comments say that you must not rely on the orgname and orgtable, as of now, we are going to 
			// 	do our best to make that orgname and orgtable are the most reliable values you can use in order to identify a field
			// 	closer to its original definition (that is to say in the source table itself).
			// CAUTION: As of now, please rely on orgtable and orgname. These won't be aliasses or something else, but, should match the best
			// 	with the backend_access and frontend_access configurations.
			if (!exists_and_not_empty($orgname))
				$orgname = $name;

			if (!exists_and_not_empty($table))
				$table = "CREATED_INTO_THE_SELECT_CLAUSE";

			if (!exists_and_not_empty($orgtable))
				$orgtable = $table;


			$field_name = $name; // $orgtable . "." . $orgname;
			// $field_name = $orgtable . "." . $orgname; // This is not possible as long as the real select that fetch rows, 
			// 	calls columns only with the field_name or alias or formula, ommiting the table name.


			$field_structure[$field_name]['field_direct'] = array (
				'name' => $name
				, 'orgname' => $orgname
				, 'table' => $table
				, 'orgtable' => $orgtable
				, 'def' => $finfo->def // The default value for this field, represented as a string
				, 'max_length' => $finfo->max_length // The maximum width of the field for the result set.
				, 'length' => $finfo->length // The width of the field, as specified in the table definition.
				, 'charsetnr' => $finfo->charsetnr // The character set number for the field.
				, 'flags' => $finfo->flags // An integer representing the bit-flags for the field.
				, 'type' => $finfo->type // The data type used for this field
				, 'decimals' => $finfo->decimals // The number of decimals used (for integer fields)
			);




			// ####################################
			// add info from the get_show_columns()
			$show_columns = get_show_columns ($field_structure[$field_name]['field_direct']['orgtable'], $field_structure[$field_name]['field_direct']['orgname']);
			$field_structure[$field_name]['show_columns'] = $show_columns; //array_merge ($field_structure[$field_name], (array) $show_columns);


			// ####################################
			// Essential for wrtting sql insert or update queries easily
			$field_structure[$field_name]['type_human_readable'] = get_human_readable_mysql_field_type ($finfo->type);
			$field_structure[$field_name]['use_quotes_for_db_insertion'] = use_quotes_for_db_insertion ($field_structure[$field_name]['type_human_readable']);

			//$field_structure[$field_name]['flags_human_readable'] = get_human_readable_flags ($finfo->flags);


			// ####################################
			// Integration of the config structure called : frontend_access

			$orgtable = $field_structure[$field_name]['field_direct']['orgtable'];
			$orgname = $field_structure[$field_name]['field_direct']['orgname'];







			// ####################################
			// ####################################
			// Overriding of frontend_access:
			// frontend_access.conf.php overrides DEFAULT_FRONTEND_SETTINGS
			//echo trace2web('OVERRIDING 1: frontend_access.conf.php overrides DEFAULT_FRONTEND_SETTINGS, so:');
			//echo trace2web($GLOBALS['security']['frontend_access'][$orgtable][$orgname], "GLOBALS['security']['frontend_access'][$orgtable][$orgname] overrides");
			//echo trace2web($GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS'], "GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']");
			$field_structure[$field_name]['frontend_access'] = replace_leaves_keep_all_branches (
				$GLOBALS['security']['frontend_access'][$orgtable][$orgname]	// taken from frontend_access.conf.php
				, $GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']
			);
			//echo trace2web($field_structure[$field_name]['frontend_access'], "field_structure[$field_name]['frontend_access'] is the result");

			// ####
			// data_validator:
			//if (exists_and_not_empty ($GLOBALS['data_validator'][$orgtable][$orgname]['input']['client']))
			//	$field_structure[$field_name]['frontend_access']['DVIC'] = $GLOBALS['data_validator'][$orgtable][$orgname]['input']['client'];

			if (isset ($GLOBALS['data_validator'][$orgtable][$orgname]['input']['client']))
				$field_structure[$field_name]['frontend_access']['data_validator']['input']['client'] = $GLOBALS['data_validator'][$orgtable][$orgname]['input']['client'];
			if (isset ($GLOBALS['data_validator'][$orgtable][$orgname]['output']))
				$field_structure[$field_name]['frontend_access']['data_validator']['output'] = $GLOBALS['data_validator'][$orgtable][$orgname]['output'];


			// $frontend_access[$field_name] param sent overrides frontend_access.conf.php and data_validator.conf.php
			//echo trace2web('OVERRIDING 3: frontend_access[$field_name] param sent overrides frontend_access.conf.php and data_validator.conf.php, so:');
			//echo trace2web($frontend_access[$field_name], "frontend_access[$field_name] overrides");
			//echo trace2web($field_structure[$field_name]['frontend_access'], "field_structure[$field_name]['frontend_access']");
			$field_structure[$field_name]['frontend_access'] = replace_leaves_keep_all_branches (
				$frontend_access[$field_name]
				, $field_structure[$field_name]['frontend_access']
			);
			//echo trace2web($field_structure[$field_name]['frontend_access'], "field_structure[$field_name]['frontend_access'] is the final result:");
			// ####################################
			// ####################################







			// maxlength:
			//$field_structure[$field_name]['frontend_access']['maxlength'] = $field_structure[$field_name]['field_direct']['length'];
			$field_structure[$field_name]['frontend_access']['maxlength'] = replace_leaves_keep_all_branches (
				$GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS']['maxlength']
				, $field_structure[$field_name]['field_direct']['length']
			);

			$field_structure[$field_name]['frontend_access']['maxlength'] = replace_leaves_keep_all_branches (
				$field_structure[$field_name]['field_direct']['length']
				, $field_structure[$field_name]['frontend_access']['maxlength']
			);

			$field_structure[$field_name]['frontend_access']['maxlength'] = replace_leaves_keep_all_branches (
				$GLOBALS['security']['frontend_access'][$orgtable][$orgname]['maxlength']
				, $field_structure[$field_name]['frontend_access']['maxlength']
			);

			$field_structure[$field_name]['frontend_access']['maxlength'] = replace_leaves_keep_all_branches (
				$frontend_access[$field_name]['maxlength']
				, $field_structure[$field_name]['frontend_access']['maxlength']
			);


			// if checkbox_multiselist, and write then onready
			if (
				exists_and_not_empty($field_structure[$field_name]['frontend_access']['form_field_type'])
				&& (
					strtolower($field_structure[$field_name]['frontend_access']['form_field_type']) === "checkbox_multiselist"
					|| strtolower($field_structure[$field_name]['frontend_access']['form_field_type']) === "radio_singleselist"
				)
			)
			{
				//$field_structure[$field_name]['frontend_access']['accesses']['rights'] = "write";
				$field_structure[$field_name]['frontend_access']['accesses']['behavior'] = "onready";
			}

			if ($field_name === "selected_in_multiselist_in_db")
			{
				$field_structure[$field_name]['frontend_access']['accesses']['rights'] = "hidden";
			}

			// if $frontend_access[$field_name]['accesses']['rights'] has any stupid value:
			if (
				$field_structure[$field_name]['frontend_access']['accesses']['rights'] !== "read"
				&& $field_structure[$field_name]['frontend_access']['accesses']['rights'] !== "write"
				&& $field_structure[$field_name]['frontend_access']['accesses']['rights'] !== "hidden"
			)
			{
				$field_structure[$field_name]['frontend_access']['accesses']['rights'] = "read";	// default value for this param
			}

			if ($field_structure[$field_name]['frontend_access']['accesses']['rights'] === "write")
			{
				if (strtolower($action['save_mode']) === "global_save")
					$field_structure[$field_name]['frontend_access']['accesses']['behavior'] = "onready";

				// if $frontend_access[$field_name]['accesses']['behavior'] has any stupid value:
				if (
					strtolower($field_structure[$field_name]['frontend_access']['accesses']['behavior'] !== "onready")
					&& strtolower($field_structure[$field_name]['frontend_access']['accesses']['behavior'] !== "onmouseenter")
					&& strtolower($field_structure[$field_name]['frontend_access']['accesses']['behavior'] !== "onclick")
					&& strtolower($field_structure[$field_name]['frontend_access']['accesses']['behavior'] !== "ondblclick")
				)
				{
					$field_structure[$field_name]['frontend_access']['accesses']['behavior'] = "onready";	// default value for this param
				}
			}




			// #########################################
			// Flushing unused values (in order to minimize the size to transfer via the network):
			if (strtolower($field_structure[$field_name]['frontend_access']['form_field_type']) !== 'autocomplete')
			{
				unset($field_structure[$field_name]['frontend_access']['autocomplete_vars']);
			}
		}
	}
	else
	{
		//new mil_Exception ("This is not possible to execute the request: $sql"
		//	. trace2web($mysqli_con->error, "mysqli_con->error")
		//	, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		new mil_Exception ("This is not possible to execute the request: $sql"
			. trace2web($GLOBALS['dbcon']->error, "GLOBALS['dbcon']->error")
			, "1201111240", "WARN", __FILE__ .":". __LINE__ );
	}

	//$mysqli_con->close ();

	return $field_structure;
}

// In order to populate field_structure information
function get_human_readable_mysql_field_type ($type_num)
{
	if ($type_num === 0) return "DECIMAL";
	if ($type_num === 1) return "TINY";
	if ($type_num === 2) return "SHORT";
	if ($type_num === 3) return "LONG";
	if ($type_num === 4) return "FLOAT";
	if ($type_num === 5) return "DOUBLE";
	if ($type_num === 6) return "NULL";
	if ($type_num === 7) return "TIMESTAMP";
	if ($type_num === 8) return "LONGLONG";
	if ($type_num === 9) return "INT24";
	if ($type_num === 10) return "DATE";
	if ($type_num === 11) return "TIME";
	if ($type_num === 12) return "DATETIME";
	if ($type_num === 13) return "YEAR";
	if ($type_num === 14) return "NEWDATE";
	if ($type_num === 247) return "ENUM";
	if ($type_num === 248) return "SET";
	if ($type_num === 249) return "TINY_BLOB";
	if ($type_num === 250) return "MEDIUM_BLOB";
	if ($type_num === 251) return "LONG_BLOB";
	if ($type_num === 252) return "BLOB";
	if ($type_num === 253) return "VAR_STRING";
	if ($type_num === 254) return "STRING";
	if ($type_num === 255) return "GEOMETRY";
}


// doesn't really work
// In order to populate field_structure information
function get_human_readable_flags ($flags_num)
{
	if ($flags_num === 1) return "NOT_NULL_FLAG"; // Field can't be NULL
	if ($flags_num === 2) return "PRI_KEY_FLAG"; // Field is part of a primary key
	if ($flags_num === 4) return "UNIQUE_KEY_FLAG"; // Field is part of a unique key
	if ($flags_num === 8) return "MULTIPLE_KEY_FLAG"; // Field is part of a key
	if ($flags_num === 16) return "BLOB_FLAG"; // Field is a blob
	if ($flags_num === 32) return "UNSIGNED_FLAG"; // Field is unsigned
	if ($flags_num === 64) return "ZEROFILL_FLAG"; // Field is zerofill
	if ($flags_num === 128) return "BINARY_FLAG"; // Field is binary
	if ($flags_num === 256) return "ENUM_FLAG"; // field is an enum
	if ($flags_num === 512) return "AUTO_INCREMENT_FLAG"; // field is a autoincrement field
	if ($flags_num === 1024) return "TIMESTAMP_FLAG"; // Field is a timestamp

	if ($flags_num === 2048) return "SET_FLAG"; 
	if ($flags_num === 32768) return "NUM_FLAG";
	if ($flags_num === 16384) return "PART_KEY_FLAG";
	if ($flags_num === 32768) return "GROUP_FLAG";
	if ($flags_num === 65536) return "UNIQUE_FLAG";
}

// In order to populate field_structure information
function use_quotes_for_db_insertion ($type_human_readable)
{
	// fields that use quotes on insertion or update :
	if (
		$type_human_readable === "TIMESTAMP"
		|| $type_human_readable === "DATE"
		|| $type_human_readable === "TIME"
		|| $type_human_readable === "DATETIME"
		|| $type_human_readable === "YEAR"
		|| $type_human_readable === "NEWDATE"
		|| $type_human_readable === "TINY_BLOB"
		|| $type_human_readable === "MEDIUM_BLOB"
		|| $type_human_readable === "LONG_BLOB"
		|| $type_human_readable === "BLOB"
		|| $type_human_readable === "VAR_STRING"
		|| $type_human_readable === "STRING"
	)
	{
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Populate a valuelist so that a foreign key of a DB table (as numerical field) can be replaced (for its display only) by a human readeable field.
 *
 * @param $sql: (mandatory) {string} This param is the query getting understandable data in order to replace a numerical field.
 * 	- Any query should have 2 fields to be returned as result:
 * 		- The first field will be the key of an array corresponding to the numerical field.
 * 		- The second field will be the value to be displayed.
 * @param $index_zero: (optional) {string} This param is the first element (index 0) to put in the list. This is used most of the time for such things:
 * 	" --- Please select a value --- "
 * 	- Most of the time, if you use this param, you will have to use add a DVRC data validator, in order to avoid making researchs on a field with 0.
 *
 * @return $field_structure {associative array} The returned value is an array fetching understandable data from the config DB table, with as:
 * 	- key: the db_stored_value (most of the time, this will be the primary key of the config table) 
 * 		- eg: for the config table Country the identifier will be the 'country_id'
 * 	- value: the human understandable value to be displayed in the interface (something like a label)
 * 		- eg: for the config table Country the label will be for example the field 'english' (containing the english label).
 *
 * Example of call
 * @code
 * // The following countries_valuelist (see frontend_access.conf.php) can be the living country
 * // of your client stored in the DB table.field: client.livingcountry
 * $lang = $my_page->lang;
 * $countries_valuelist = get_the_valuelist (
 * 	"SELECT country_id, $lang FROM `country` WHERE enabled = 1 ORDER BY sort_index"
 * 	, " --- Please choose a country --- " // You can add a header to your list, provide the 0 value is not a PK in the table.
 * );
 * // Here above, $lang can be for example: 'english', 'french', 'german' containing the name of the country in the table itself.
 * @endcode
 */
function get_the_valuelist ($sql, $index_zero = "") //get_valuelist
{
	$function_return;

	if (exists_and_not_empty($index_zero)) $function_return[0] = $index_zero;

	//echo trace2web ($GLOBALS['dbcon'], "GLOBALS['dbcon'] - " . __FILE__.":".__LINE__);
	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	$records;

	if ($mysqli_result = $mysqli_con->query($sql))
	{
		$nbRes = $mysqli_result->num_rows;

		if ($nbRes == 0)
		{
			$function_return = array();
		}
		else if ($nbRes >= 1)
		{
			for ($l = 1; $row = $mysqli_result->fetch_row(); $l++)
			{
				$records[$l] = $row;
				$db_stored_value = (string) $records[$l][0];	// db_stored_value
				$value_to_be_displayed = $records[$l][1];	// value_to_be_displayed

				$function_return[$db_stored_value] = $value_to_be_displayed;
			}
		}

		$mysqli_result->free();
	}
	else
	{
		new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql"
			. trace2web($mysqli_con->error, "mysqli_con->error")
			, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
	}

	//$mysqli_con->close();
	//echo trace2web ($function_return, "function_return");

	return $function_return;
}

// get_ANDOR_condition_between_array_elements
function get_ANDOR_condition ($arr, $ANDOR)
{
	$arr = (array) $arr;

	$condition = "";
	if (count($arr))
	{
		$condition = "\n(\n";
		$i = 1;
		foreach ($arr as $key => $value)
		{
			if ($i == 1)
			{
				$condition .= "$value\n";
				$i++;
			}
			else
			{
				$condition .= "$ANDOR $value\n";
			}
		}
		$condition .= ")";
	}
	return $condition;
}

// get_OR_condition_between_array_elements
function get_OR_condition ($array)
{
	return get_ANDOR_condition ($array, "OR");
}

// get_AND_condition_between_array_elements
function get_AND_condition ($array)
{
	return get_ANDOR_condition ($array, "AND");
}

/**
 * Get a full string with AND conditions of a WHERE clause of a SQL query, relying on an associative array of conditions.
 * Each condition is escaped, and each condition is put into simple quotes, so that there must not be problem, even integers and decimals can be included into quotes.
 * 
 * @warning Remark: This function is very similar to datamalico_server_ajax::get_WHERE_clause() which is newer.
 *
 * @param $conditions (optional) {associative array} Conditions
 * @return {string} The full string condition.
 */
function get_full_string_AND_condition ($conditions)
{
	$full_string_condition = "";

	if (exists_and_not_empty ($conditions))
	{
		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		// if array or string
		if (gettype ($conditions) === "array")
		{
			$tmp_conditions_array;
			foreach ($conditions as $condition_name => $condition_value)
			{
				$esc_value = $mysqli_con->real_escape_string($condition_value);
				$tmp_conditions_array[] = "`$condition_name` = '$esc_value'";
			}

			$full_string_condition = get_AND_condition ($tmp_conditions_array);
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
 * Function get_field_string. If you send an array with select clause components, you'll get a string.
 * @param fields_array {numerical array} 
 * @return a string with the correct number of commas between field names.
 * @todo Let this function as a function and not a method, because, this is also used by the datamalico_server_ajax class.
 */
function get_field_string ($fields_array)
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

	return $field_string;
}



function mil_escape_string ($str)
{
	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();
	$str = $mysqli_con->real_escape_string($str);
	//$mysqli_con->close();
	return $str;
}


?>
