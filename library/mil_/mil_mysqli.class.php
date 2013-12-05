<?php
/** 
 * @file
 * File where the mil_mysqli class is defined
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2013
 */




/**
 * The best practice to use this class is to create only one connection for the whole scripting: that is to say using a global variable:
 * @code
 * $GLOBALS['dbcon'] = new mil_mysqli ();
 * @endcode
 */
class mil_mysqli extends mysqli
{
	/**
	 */
	//public $DB_name;
	public $metadata;

	/**
	 * results {associative array} (
	 */
	public $results;

	/*
	* Constructor. You normally don't need to call it because, this is normally called once in library/config/mysql-connection.php.
	 */
	function __construct ()
	{
		/*
		$DB_name = $GLOBALS['DB']['DB_name'];
		$DB_server_name = $GLOBALS['DB']['DB_server_name'];
		$port = $GLOBALS['DB']['port'];
		$DB_user_name = $GLOBALS['DB']['DB_user'];
		$DB_pass = "*************";
		$socket = $GLOBALS['DB']['socket'];
		$description = "Decorons";

		parent::__construct($DB_server_name, $DB_user_name, $DB_pass, $DB_name, $port /*, $socket * / );
		 */
		parent::__construct(
			$GLOBALS['DB']['DB_server_name']
			, $GLOBALS['DB']['DB_user']
			, $GLOBALS['DB']['DB_pass']
			, $GLOBALS['DB']['DB_name']
			, $GLOBALS['DB']['port']
			//, $GLOBALS['DB']['socket']
		);
		if ($this->connect_errno) 
		{
			new mil_Exception ("This is not possible to connect to the DataBaseServer - " . $this->connect_error, "1112101505", "ERROR", __FILE__ .":". __LINE__ );
			//return;
		}
		
		//$this->DB_name = $DB_name;


		//echo trace2web("At the begining of the " . __CLASS__ . " constructor");
		/*
		$this->timing = array (
			'begin' => ''	// look for debug_chronometer () in mil_.lib.php
			, 'laps' => ''
			, 'end' =>  ''
		);

		$this->get_config__constructor ($params);	//echo trace2web($this, "after get_config__constructor()");
		 */

		//echo trace2web($this, "Constructor end of " . __CLASS__);
		//trace2file($this, "Constructor end of " . __CLASS__, __FILE__, true);
	}

	/*
	private function get_config__constructor ($params)
	{
		if (exists_and_not_empty($params['a_param']))
		{
			$this->input = $params['a_param'];
		}
		else $this = NULL;
	}
	 */

	/*
	function __destruct ()
	{
		//echo trace2web($this, "Destruction of the object " . __CLASS__);
		trace2file($this, "Destruction of the object " . __CLASS__, __FILE__);

		$dump = var_dump_nooutput ($this);

		//echo trace2web($dump, "dump");
		trace2file($dump, "dump", __FILE__);

		//$polo = $this->poll ($read , $error , $reject , 10, 10 );
		//$polo_dump = var_dump_nooutput ($polo);
		//echo trace2web($polo_dump, "polo_dump");
		//trace2file($polo_dump, "polo_dump", __FILE__);

		$this->close();
	}
	 */

	/**
	 * qexec means "query exec". SELECT, SHOW, DESCRIBE, EXPLAIN or INSERT, REPLACE, UPDATE, DELETE
	 *
	 * @param params: {associative array} (mandatory) Are the params to be executed by the method:
	 * 	- sql: {string} (mandatory) Is the sql string that has been run.
	 *	- expected_affected_rows: {string} (optional, because it is needed only if the query is a SELECT or SHOW...) Is the expected_affected_rows specified at call: "min:max" 
	 *		- The : sign speparates the min and the max values: eg: "0:inf" or "1:1"
	 *		- The min value can be any number.
	 *		- The max value can also be any number or you can also specify "inf" or "infinity" for infinity.
	 * 	- get_field_structure: {bool} (only for SELECT, SHOW... and optional, default is false) Specifies if the field_structure of the result set must be returned
	 * 		(See mil_mysqli::results['field_structure'])
	 * 	- script_place: {string} (optional, defaul is __FILE__.":".__LINE__ of the mil_mysqli::qexec() method) If specifyed at call,
	 * 		is string identifying what is the original calling place. Is used for debuging.
	 * 
	 * @return fn_return : {associative array} (mandatory) Is a structure containing the whole result.
	 * 	- metadata {associative array} This is an array containting all the metadata of a query (See mil_mysqli::qexec()).
	 * 		- sql: {string} (mandatory) Is the param['sql'], see param above.
	 * 		- get_field_structure: {bool} (mandatory) Is the param['get_field_structure'], see param above.
	 * 		- script_place: {string} (mandatory) Is the param['script_place'], see param above.
	 * 		- error: {string} (mandatory, default is NULL) Is the error message of the parent class mysqli()
	 * 		- affected_rows: {int} (mandatory) Is the number of rows affected by the query.
	 * 		- expected_affected_rows: {associative array} (optional, only if the last query was a SELECT...) Info relative to the expected affected rows:
	 * 			- pattern: {string} (mandatory) Is the expected_affected_rows specified at call: eg: "0:inf" or "1:1"
	 * 			- min: {int} (mandatory) Is the minimum number of rows expected: eg: 0 or 1
	 * 			- max: {int} (mandatory) Is the maximum number of rows expected: eg: 1 or inf (meaning infinity)
	 * 		- insert_id: {int} (only in case of INSERT or REPLACE) Is the last_insert_id, by the query.
	 * 	- results: {associative array} (optional, only if SELECT, SHOW...)
	 * 		- records: {numerical array} (mandatory) Are the records returned by the query.
	 * 			- 1: begins at 1 not 0.
	 * 				- {field name of the column} => {value stored in DB}
	 * 			- ...
	 * 		- field_structure: {associative array} (optional, depending on the param['get_field_structure']) Is the result of the function 
	 * 			get_field_structure() in datamalico_server_dbquery.lib.php
	 *
	 * Exemple of use with SELECT, SHOW, DESCRIBE or EXPLAIN:
	 * @code
	 * $tn = "config_gender";
	 * $tn_protected = $GLOBALS['dbcon']->real_escape_string($tn);
	 *
	 * $myq = $GLOBALS['dbcon']->qexec( array (
	 *         'sql' => "SELECT * FROM  $tn_protected"
	 *         , 'expected_affected_rows' => "0:inf"
	 *         , 'get_field_structure' => false
	 *         , 'script_place' => __FILE__.":".__LINE__
	 * ));
	 *
	 * echo trace2web ($myq, "myq");
	 * foreach ($myq['results']['records'] as $rowNum => $row)
	 * {
	 * 	$a_all_services[] = $myq['results']['records'][$rowNum][$lang];
	 * }
	 * if ($myq['metadata']['affected_rows'] === 1)
	 * {
	 * 	$fake_var = "valid";
	 * }
	 * @endcode
	 *
	 * Exemple of use with INSERT, UPDATE, DELETE:
	 * @code
	 * $myq = $GLOBALS['dbcon']->qexec( array (
	 *         'sql' => "UPDATE  `mil_test` SET  `field` =  'Salut coucou' WHERE  `mil_test`.`key` =16"
	 *         , 'script_place' => __FILE__.":".__LINE__
	 * ));
	 * 
	 * echo trace2web ($myq['metadata']['insert_id'], "myq['metadata']['insert_id']");
	 * @endcode
	 * 
	 * @todo It could be relevant to use another function than get_field_structure() in datamalico_server_dbquery.lib.php to get the fn_retrun['results']['field_structure']
	 */
	public function qexec ($params)
	{
		//echo trace2web ($this, "this");
		//var_dump($this);

		$query_default_params = array (
			'sql' => "SELECT NULL"
			, 'expected_affected_rows' => "1:1"	// number of expected result numbers: 0:inf -> 0 to infinity, 1 -> only 1... and if the range is other, then, log as an error.
			, 'get_field_structure' => false
			, 'script_place' => __FILE__.":".__LINE__
		);

		$config = replace_leaves_keep_all_branches ($params, $query_default_params);

		//echo trace2web ($config, "config");

		$this->metadata = array ();

		$this->metadata['sql'] = $config['sql'];
		//$this->metadata['expected_affected_rows'] = $config['expected_affected_rows'];
		$this->metadata['get_field_structure'] = $config['get_field_structure'];
		$this->metadata['script_place'] = $config['script_place'];
		$this->metadata['error'] = NULL;

		//$mil_sql->sql
		//$mil_sql->expected_affected_rows
		//$mil_sql->get_field_structure
		//$mil_sql->script_place
		//
		//$mil_sql->error
		//
		//$mil_sql->metadata['affected_rows'];
		//$mil_sql->metadata['insert_id'];
		//
		//$mil_sql->results['records'];
		//$mil_sql->results['field_structure'];


		$mysqli_result = $this->query($config['sql']);
		if ($mysqli_result !== FALSE)	// Means successfull query
		{
			//var_dump($mysqli_result);
			//echo trace2web ("[".gettype($mysqli_result)."]");

			$mysqli_result_type = gettype($mysqli_result);
			if ($mysqli_result_type === 'object') // Means: SELECT, SHOW, DESCRIBE or EXPLAIN - See http://fr2.php.net/manual/fr/mysqli.query.php
			{
				$this->results = array ();

				// ####################################
				// expected_affected_rows:
				$this->metadata['affected_rows'] = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
				//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT
				//echo trace2web ($this->affected_rows, "this->affected_rows");	// insert, update, delete, select


				$expected_affected_rows = explode(":", $config['expected_affected_rows']);
				$this->metadata['expected_affected_rows'] = array (
					'pattern' => $config['expected_affected_rows']
					, 'min' => $expected_affected_rows[0]
					, 'max' => $expected_affected_rows[1]
				);

				if (
					strtolower ($this->metadata['expected_affected_rows']['max']) === "inf"
					|| strtolower ($this->metadata['expected_affected_rows']['max']) === "infinity"
				)
				{
					$this->metadata['expected_affected_rows']['max'] = getrandmax();
				}


				// ####################################
				// Fetch results

				// NOT OK: affected_rows is NOT ok regarding expected_affected_rows:
				if (
					$this->metadata['affected_rows'] < $this->metadata['expected_affected_rows']['min']
					|| $this->metadata['affected_rows'] > $this->metadata['expected_affected_rows']['max']
				)
				{
					if ($this->metadata['affected_rows'] < $this->metadata['expected_affected_rows']['min'])
					{
						$this->results['records'][0] = "LESS_RESULTS_THAN_EXPECTED";
						new mil_Exception (__FUNCTION__ . " : LESS_RESULTS_THAN_EXPECTED: $sql", "1201111240", "WARN", $config['script_place']);
					}
					if ($this->metadata['affected_rows'] > $this->metadata['expected_affected_rows']['max'])
					{
						$this->results['records'][0] = "MORE_RESULTS_THAN_EXPECTED";
						new mil_Exception (__FUNCTION__ . " : MORE_RESULTS_THAN_EXPECTED: $sql", "1201111240", "WARN", $config['script_place']);
					}
				}

				// OK: affected_rows is ok regarding expected_affected_rows:
				else
				{
					for ($l = 1; $row = $mysqli_result->fetch_assoc(); $l++) {
						$this->results['records'][$l] = $row;
					}

					// ####################################
					// get_field_structure:
					if ($config['get_field_structure'] === true)
					{
						$field_structure;
						$this->results['field_structure'] = get_field_structure ($config['sql']);
					}
				}

				$mysqli_result->free();				
			}
			else if ($mysqli_result_type === 'boolean') // Means: INSERT, REPLACE, UPDATE, DELETE - See http://fr2.php.net/manual/fr/mysqli.query.php
			{
				// ####################################
				// INSERT:
				$this->metadata['insert_id'] = $this->insert_id; // IS SET IF INSERT.
				// echo trace2web ($this->insert_id, "this->insert_id") ;		// for insert

				// ####################################
				// INSERT, UPDATE, REPLACE ou DELETE, SELECT
				$this->metadata['affected_rows'] = $this->affected_rows;	
			}
		} else {
			$this->metadata['error'] = $this->error;
			//echo trace2web ($this->error);
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($this->error, "this->error")
				, "1201111240", "WARN", $config['script_place'] );
			//echo trace2web($this->error, "this->error");
		}

		$fn_return = array (
			'metadata' => $this->metadata
			, 'results' => $this->results
		);

		$this->metadata = NULL;
		$this->results = NULL;

		return $fn_return;	
	}
}


?>
