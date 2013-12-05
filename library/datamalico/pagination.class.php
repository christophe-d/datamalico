<?php
/** 
 * @file
 * File where the pagination class is defined.
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 */

/**
 * This class is a tiny class. But is so much handy. You can use such a tiny 'pagination' object to specify for any of your web page what page number you want to see
 * and how many results per page you want.
 *
 * We created such a tiny class because, it is used by several other classes (in our case the page handling class and the beautifull 'datamalico' class
 * (Data Mamangement Library for Coders) which makes a so much easy and powerfull bridge between a web page interface and a database.
 *
 * @warning How does the pagination works? For each page, you can specify a particular pagination through 2 parameters:
 * - page: the page number you want to see. (Just like in any search engine, if you want to see the 3rd page of research, then you click on '3'.)
 * - perpage: the number of results per page you want to display.
 *
 * @warning In practice, you can parameter the pagination through the URL:
 * @code
 * www.mydomain.com/mypage.php?page=3&perpage=15
 * @endcode
 * - But of-course you can set it via the standard GET request mthod of the page.
 * - You can set it via the POST request method (Note that if you send theses params in GET and POST, GET would override POST)
 * - You can also set default values in 2 ways:
 * 	- directly in the __construct() constructor: page=1, perpage=999
 * 	- but we advise you to set prior to the constructor call, two default values (that will be taken into account in the constructor) for your whole application:
 * @code
 * $GLOBALS['pagination']['page'] = $the_value_you_want;		// most of the time, you want 1
 * $GLOBALS['pagination']['perpage'] = $the_value_you_want;	// 15 for example.
 * @endcode
 *
 * This pagination behavior is inspired of the jquery paging extension behavior: infusion-jQuery-Paging-1121b46.zip taken 
 * 	at http://www.xarg.org/2011/09/jquery-pagination-revised/
 * 	and the HTML modelling for pagination is contained into the method paginate() of the javascript class 'datamalico' (actually datamalico_client) 
 * 	in datamalico.lib.js
 */
class pagination
{
	/**
	 * See __construct()
	 */
	public $page = null;

	/**
	 * See __construct()
	 */
	public $perpage = null;

	/**
	 * See __construct()
	 */
	public $nbRes = null;

	/**
	 * lastpage: {integer} Is the last page number the query could generate.
	 */
	public $lastpage = null;

	/**
	 * num_of_first_elem_on_page: {integer} Is the number of the first element that the current page must present.
	 */
	public $num_of_first_elem_on_page = null;

	/**
	 * num_of_last_elem_on_page: {integer} Is the number of the last element that the current page must present.
	 */
	public $num_of_last_elem_on_page = null;



	/**
	 * Pagination object constructor.
	 *
	 * It automatically handles possible values for the pagination. For example if you enter a perpage=-10, or perpage=foo, then, it is automatically, set to a default
	 * 	value.
	 *
	 * @param $params {associative array} You can configure a correct pagination according to  theses 3 criteria:
	 * 	- page: (optional) {integer} Is the page number you want to get. Set the $default_page in the __construct().
	 * 		- If absent, the GET param is taken. If GET param is absent, the POST param is taken.
	 * 		- If you specify it, then it overrides the defaults, but not the GET or POST 'page' param.
	 * 	- perpage: (optional) {integer} Is the number of results per page you want to display. Set the $default_perpage in the __construct().
	 * 		- If absent, the GET param is taken. If GET param is absent, the POST param is taken.
	 * 		- If you specify it, then it overrides the defaults, but not the GET or POST 'page' param.
	 * 	- nbRes: (optional) {integer} Is the total number of results the datamalico_server_dbquery::select() query will fetch.
	 * 		See the $default_nbRes in the __construct() for more information.
	 * 		- If absent, then
	 * 			- $this->page = {its normal value}
	 * 			- $this->perpage = {its normal value}
	 * 			- $this->nbRes = 0
	 * 			- $this->lastpage = 1
	 * 			- $this->num_of_first_elem_on_page = 0
	 * 			- $this->num_of_last_elem_on_page = 0
	 *
	 * Once the object is constructed, the given parameters are corrected if they were malformed, ex:
	 * 	- page=foo or page=0 is corrected to the default value.
	 * 	- perpage=bar or perpage=0 is corrected to the default value.
	 *
	 * page (What if):
	 * 	- page=NaN then page={default value} ex: page=foo then page={default value}
	 * 	- page=0 then page={default value}
	 * 	- page={negative value} then page={lastpage - negative value + 1} ex: if lastpage=8 and page=-1, then page=8
	 * 		- so if page=-1 then page={lastpage}
	 * 		- and if page=-2 then page={lastpage - 1}
	 * 	- page={value bigger than the lastpage} then page=lastpage
	 *
	 * perpage (What if):
	 * 	- perpage=NaN then perpage={default value} ex: perpage=bar then perpage={default value}
	 * 	- perpage=0 then perpage={default value}
	 * 	- perpage={negative value} then perpage=abs({negative value})
	 *
	 * Preferences of settings: Here is the priority order of pagination setting:
	 * 	- $_GET['page'] and $_GET['perpage'] come first
	 * 	- $_POST['page'] and $_POST['perpage'] then
	 * 	- $params['page'] and $params['perpage'] then. $params are the params sent to the pagination constructor.
	 * 	- $GLOBALS['pagination']['page'] and $GLOBALS['pagination']['perpage'] then
	 * 	- 1 and 4294967296 (biggest unsigned int with 4 bytes) finally.
	 */
	function __construct ($params = array())
	{
		//echo trace2web ($params, __CLASS__." constructor params");
		$default_page = exists_and_not_empty($GLOBALS['pagination']['page']) ? $GLOBALS['pagination']['page'] : 1;
		$default_perpage = exists_and_not_empty($GLOBALS['pagination']['perpage']) ? $GLOBALS['pagination']['perpage'] : 4294967296;
		$default_nbRes = 0;
		$default_lastpage = 1;



		// ########################
		// set $this->page:
		if (exists_and_not_empty($_GET['page']))
		{
			$this->page = (float) $_GET['page']; // if Nan, then becomes 0		// float is used to cast, because big int are float actually.
			//echo trace2web (__LINE__ . ", this->page: $this->page");
		}
		else if (exists_and_not_empty($_POST['page']))
		{
			$this->page = (float) $_POST['page'];
			//echo trace2web (__LINE__ . ", this->page: $this->page");
		}
		else if (exists_and_not_empty ($params['page']))
		{
			$this->page = (float) $params['page'];
			//echo trace2web (__LINE__ . ", this->page: $this->page");
		}
		else
		{
			$this->page = $default_page;
			//echo trace2web (__LINE__ . ", this->page: $this->page");
		}

		$this->page = (float) $this->page;
		$this->page = $this->page === 0 ? $default_page : $this->page;
		//echo trace2web (__LINE__ . ", this->page: $this->page");
		

		// ########################
		// set $this->perpage:
		if (exists_and_not_empty($_GET['perpage']))
		{
			$this->perpage = (float) $_GET['perpage']; // if Nan, then becomes 0
			//echo trace2web (__LINE__ . ", this->perpage: $this->perpage");
		}
		else if (exists_and_not_empty($_POST['perpage']))
		{
			$this->perpage = (float) $_POST['perpage'];
			//echo trace2web (__LINE__ . ", this->perpage: $this->perpage");
		}
		else if (exists_and_not_empty ($params['perpage']))
		{
			$this->perpage = (float) $params['perpage'];
			//echo trace2web (__LINE__ . ", this->perpage: $this->perpage");
		}
		else
		{
			$this->perpage = $default_perpage;
			//echo trace2web (__LINE__ . ", this->perpage: $this->perpage");
		}

		$this->perpage = abs((float) $this->perpage);
		$this->perpage = $this->perpage === 0 ? $default_perpage : $this->perpage;
		//echo trace2web (__LINE__ . ", this->perpage: $this->perpage");

		// ########################
		// set $this->nbRes:
		// and set $this->lastpage:
		if (exists_and_not_empty ($params['nbRes']))
		{
			$this->nbRes = $default_nbRes;
			// ##################################
			// Manage pagination data
			//$GLOBALS['ajaxReturn']['metadata']['nbRes'] = $nbRes;
			$this->nbRes = abs((float) $params['nbRes']);
			$this->perpage = $this->perpage < 1 ? 1 : $this->perpage;
			$first_page = 1;
			$this->lastpage = ceil($this->nbRes / $this->perpage);
			$this->lastpage = $this->lastpage > 0 ? $this->lastpage : 1;
			$this->page = $this->page == 0 ? $first_page : $this->page;
			//$this->page = $this->page < $first_page ? ($this->lastpage+1) - abs($this->page) : $this->page;
			$tmp = abs($this->page) % $this->lastpage;
			if ((float) $tmp == 0) $tmp = $this->lastpage;
			$this->page = $this->page < $first_page ? $this->lastpage - $tmp + 1 : $this->page;
			$this->page = $this->page > $this->lastpage ? $this->lastpage : $this->page;

			$this->num_of_first_elem_on_page = (($this->page - 1) * $this->perpage) + 1;
			$this->num_of_last_elem_on_page = $this->num_of_first_elem_on_page + ($this->perpage - 1);
			$this->num_of_last_elem_on_page = $this->num_of_last_elem_on_page > $this->nbRes ? $this->nbRes : $this->num_of_last_elem_on_page;
			$this->num_of_first_elem_on_page = $this->nbRes === 0 ? 0 :  $this->num_of_first_elem_on_page;
		}
		else
		{
			// ##########################################################################################################
			// ##########################################################################################################
			//
			// PLEASE READ IT IF YOU WANT TO CHANGE THIS CODE:
			// -----------------------------------------------
			//
			// For this part, $params['nbRes'] doesn't exists.
			// When $params['nbRes'] doesn't exists, it means that a web page gets pagination params. 
			// But this web page is just an html template, and then has still not performed any search query (because, imagine it will be done just after 
			// through ajax), and consequently, this template html page has still no idea of the number of results the query will fetch.
			//
			// Nevertheless, from this template html page, you have to keep pagination params in memory and to transfere it to the ajax server page that is
			// going to perform the query.
			//
			// That's why this->nbRes could be undefined, but that is also why, you must keep the this->page to it normal value.
			//
			// ##########################################################################################################
			// ##########################################################################################################

			//$this->page = 1;	// you must keep the this->page to it normal value (see the explanation above).
			$this->nbRes = 0;
			$this->lastpage = 1;
			$this->num_of_first_elem_on_page = 0;
			$this->num_of_last_elem_on_page = 0;
		}

		//echo debugDisplayTable($this, "At the end of the " . __CLASS__ . " constructor");
	}

	function __destruct ()
	{
		//echo trace2web  ("Destruction of an object " . __CLASS__);
	}
}

?>
