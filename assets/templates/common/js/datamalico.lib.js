/** 
* @file
* File where the javascript datamalico library is defined.
*
* Of-course, if you use this javascript datamalico library (on the client side), you must also use datamalico_server_dbquery.lib.php and/or the datamalico.ajax.lib.php
*
* @author	Christophe DELCOURTE
* @version	1.0
* @date	2012
*
* @warning In the class javascript 'datamalico' you need to focus on 4 methods to do almost every thing:
* 
* @warning
* - display() allows you to display any result got from a formed JSON string (eg: via an ajax request very easily)
* @code
* // The following line is going to write in the HTML element #firstname, the content of the field 'firstname' of the 
* // 	record number 1 of the 'ajaxReturn' result set got through an ajax request.
* $jq1001("#firstname").datamalico(ajaxReturn).display({field_name : "firstname", row_num: 1});
* 
* // Other writting:
* $jq1001("#firstname")						// jquery selector
* 	.datamalico(ajaxReturn)					// datamalico obj creation with the server result set (json).
* 	.display({field_name : "firstname", row_num: 1});	// display action with a simple configuration
*
* // The following instruction will do the same but for the field called lastname.
* $jq1001("#lastname").datamalico(ajaxReturn).display({field_name : "lastname", row_num: 1});
* @endcode
* 
* @warning
* - display_datatable() allows you to display any a set of result (eg: a result of a SELECT database query) got from an ajax request very easily, and paginated.
* @code
* var display = {
* 	datatable_css_class_names: {
* 		odd_row: "odd_row"
* 		, even_row: "even_row"
* 	}
* };
* $jq1001("#page_content").datamalico(ajaxReturn).display_datatable(display);
* @endcode
*
* @warning
* - display_multiselist() allows you to display any a set of result (eg: a result of a SELECT database query) got from an ajax request very easily, and paginated.
* @code
* var display = {
* 	datatable_css_class_names: {
* 		odd_row: "odd_row"
* 		, even_row: "even_row"
* 	}
*	, optgroup: [{field: "group_id"}]
* };
* $jq1001("#multiselist_content").datamalico(ajaxReturn).display_multiselist(display);
* @endcode
*
* Most of the functions of this library recieve a parameter called params.
* @param params {object}
* 	- ajaxReturn: {object} Data answered by the server. Most of the time this will be the result of the dco_select_sql() or other main functions of the datamalico_server_dbquery.lib.php
* 		or datamalico_server_ajax.lib.php
* 	- display: (object) (optional) {object} Is the configuration for the display
* 		- html_container: (optional) Is the selector (a jquery selector) where the function must operate.
* 		- ... (Some other params are required according to the function you use. See their documentation.)
*
* @warning Notes for Developers. This above params parameter is checked at the begning of functions. It is then transformed to a param called config.
* @param config {object}
* 	- ajaxReturn: {object}
* 	- display: (object) (optional) {object}
* 	- edition
* 	- org_field_name
* 	- table_name
*
* @todo Avoid identifying an HTML element by its config.display_html_container, but by a this object property of datamalico_client type.
*
* @todo Avoid sending a big ajax parameter to each function, but use the jquery data() to attach the ajaxReturn to the object.
*/



// {{{ datamalico class definition
// ################################################################################
// ################################################################################

/**
* \class datamalico The javascript 'datamalico' class is actually, not real class, but just alias of the class named: 'datamalico_client'
*
* Anyway, we advise you to use it instead of the datamalico_client class. Technically speaking, 'datamalico_client' is the class, and 'datamalico' is just 
* an easy access extending jquery and providing chaining in order to append the datamalico call, with a method call, like that:
*
* @code
$jq1001("#firstname")			// what HTML element to fill
.datamalico(ajaxReturn)		// ajaxReturn is the server response
.display({				// call the display method
field_name : "firstname"	// specify that you display the "firstname" column of the sql results returned in ajaxReturn
, row_num: 1			// specify that you display the row number 1 of the sql results returned in ajaxReturn
});
* @endcode
*
* This function is 'the' easy alias we recommend you to use in order to create a client objet (javascript) based on the class datamalico_client.
*
* Thus, combined with a jquery use, you fill any web page with your database results.
*
* @warning Technically speaking, this function is the datamalico extension for jquery. But we will also consider this as the constructor of our 
* datamalico client side (javascript object) (even if the real constructor is the global function datamalico_client() ). 
* We advise you to use it instead of the datamalico_client() constructor.
* By using it, you can chain the datamalico constructor with any object, identified by a jquery selector, ex:
*
* Example of best practice:
* @code
* // Chained style: (recommended)
* $jq1001("#firstname").datamalico(ajaxReturn).display({field_name : "firstname", row_num: 1}); // this instruction is going to display in the #firstname HTML element
*
* // Standard object style:
* var dco = new datamalico(ajaxReturn, $jq1001("#firstname"));
* dco.display({field_name : "firstname", row_num: 1});
* @endcode
*
* This function is the constructor of the Javascript datamalico class.
*
* @return the datamalico instance: (you can chain this instance as it is returned)
* 	- 0: datamalico_client instance
* 		- ajaxReturn: {object} (mandatory) The object used for the datamalico object creation.
* 		- html_container_obj: {jquery_elem} (mandatory) The jquery_container for this datamalico object.
*
* @warning Keep the html_container and the container. The reason is that container is necessary in order to make any standard or custom atomic_update 
* 	(and add the form id dco_ajax_atomic_update_form)
*
* @todo Prevent potential jQuery version conflicts with other versions which could be loaded in any CMS you could use surrounding your datamalico features. See jQuery.noConflict().
*/
$jq1001.fn.datamalico = function (ajaxReturn)
{
	//$jq1001("#div_debug_display").append(debugDisplayTable (arguments, "arguments"));

	//console.log("arguments:");
	//console.log(arguments);

	// ############################
	// Params and config

	var config = check_params(arguments);	// In order to manage Overloading: sends arguments get by the function above
	function check_params(params)		// Local function to check parameters
	{
		var config = params;

		// reset arguments according to there order.
		for (i=0; i < params.length; i++)
		{
			//var j = parseInt(i) + 1;
			var arg = i + 1;
			//console.log("i:"+ i + ", arg:"+arg);

			if (arg === 1)		// first argument given.
			{
				config.ajaxReturn = params[i];
				config[i] = undefined;
			}
		}

		return config;
	}
	config.html_container_obj = $jq1001(this);

	//console.log("config:");
	//console.log(config);

	var dco = new datamalico_client (config.ajaxReturn, config.html_container_obj);

	return dco;
};

/**
* This is the real datamalico_client constructor for the client side.
*
* @warning You must make the difference between all datamalico classes:
* - datamalico_server_dbquery, a php class for server side pages. (See datamalico_server_dbquery.lib.php)
* - datamalico_server_ajax, a php class making the interface between javascript client side pages and php server-side pages. (See datamalico_server_ajax.lib.php) 
* - datamalico_client (or its better alias datamalico), a javascript class for client side handling: (See datamalico.lib.js)
*   	- display data
*   	- save data.
*
* @warning Nevertheless even if datamalico_client is the real javascript datamalico class constructor, for more ease in using datamalico, 
* we advise you not to use this directly, but use the datamalico() extension for jquery instead.
*
* @warning We would have loved to call class 'datamalico' but there were a javascript conflict between this datamalico() and the jquery extension also called datamalico().
* That's why, the class has a different name. Moreover, we have choosen 'datamalico_client' in order to make the distinction between the datamalico class on the client side,
* and the datamalico class on the server side.
*
* @param ajaxReturn: (mandatory) {object} Is the JSON response given by the server page.
* @param html_container_obj: (mandatory) {object} An HTML CONTAINER, COOONNNNTTTAAAIIINNNEEERRRRRR (not an input field or something so), where dat amust take place.
*/
function datamalico_client (ajaxReturn, html_container_obj)
{
	// ############################
	// Params and config
	if ($jq1001.type(ajaxReturn) === "undefined") return;
	else this.ajaxReturn = ajaxReturn;

	if ($jq1001.type(html_container_obj) === "undefined")
	{
		return;
	} else {
		this.html_container_obj = html_container_obj;
		//this.html_container_obj.data('ajaxReturn', ajaxReturn);
	}
}

/**
* \memberof datamalico
*
* Public method of the datamalico object. See also dco_display_procedural()
*
* It displays in the jquery selected element, a value from a record (returned by the server page, and from the database).
*
* @param display: (object) (optional) {object} See details on datamlico.lib.js and about the param 'params'. Params below are specific for this function.
* 	- field_name {string} Name of the field of the record.
* 	- row_num {integer} Number of the record where to take the value to display. Note that the frist row is the number 1, not 0.
* 	- manipulation: (optional, default is delupsert) {string} Possible values are:
* 		- 'delupsert' for INSERT, UPDATE or DELETE queries (See the general documentation of datamalico_server_ajax.lib.php)
* 		- 'research' for a SELECT sql query, that is to say, perform a research (See datamalico_server_ajax::research_get_form_structure())
*
* @return the datamalico object, so that you keep the chain jquery chaining ability.
*
* Example of use:
* @code
* $jq1001("#firstname").datamalico(ajaxReturn).display({field_name : "firstname", row_num: 1});
* 
* // Other writting:
* $jq1001("#firstname")						// jquery selector
* 	.datamalico(ajaxReturn)					// datamalico obj creation with the server result set (json).
* 	.display({field_name : "firstname", row_num: 1});	// display action with a simple configuration
* @endcode
*
* Example of one research field with operator choice:
* @code
* $jq1001('#jetest').datamalico(ajaxReturn).display({
* 	field_name : "ET_supposedServicePrice"
* 	, row_num: 1
* 	, manipulation: "research"
* });
* @endcode
*
* @todo The setting of params.display.html_container is wrong in this function. Actually, there must be a bigger work area to solve this:
* 	instead of passing data via arguments for each function, store data at the level of the first elem found by the selector used to create the datamalico structure.
* 	- Eg: 
* 		- if you use: $jq1001('.class_toto').datamalico(ajaxReturn.totoresults).display({field_name : "toto_field", row_num: 1});
* 		- thus strore the datamalico.ajaxReturn object via data() at the level of the first elem of $jq1001('.class_toto')
* 		- and store each datamalico.html_container_obj object at the level of the $jq1001('.class_toto').get(x) itself.
*
*/
datamalico_client.prototype.display = function(display)
{
	var params = {ajaxReturn: this.ajaxReturn, display: display};

	//console.log(params);
	// ############################
	// Params and config
	if ($jq1001.type(params.ajaxReturn) === "undefined") return;

	// ###########################
	// work
	//console.log($jq1001(this).get(0).html_container_obj);
	//console.log(this.html_container_obj);
	//return;

	$jq1001.each (this.html_container_obj, function( index, html_element )
	{
		var jelem = $jq1001(html_element);
		var id = $jq1001(html_element).attr("id");

		// if no id for this element:
		if (!isset_notempty_notnull (id))
		{
			id = $jq1001(html_element).set_unique_id_generator ();	// or create and set a unique_id
		}
		params.display.html_container = id;
	});

	dco_display_procedural (params);

	return this;
};

/**
* \memberof datamalico
*
* Public method of the datamalico object. See also and dco_display_datagrid_procedural()
*
* It displays in the jquery selected element, a result set (accroding to the configuration given into the server page sending the result set).
* 
* The display_datagrid() method replaces old methods:
* 	- display_datatable()
* 	- display_datatemplate()
* 	- display_multiselist()
* 	- display_singleselist()
*
* @param display: (object) (optional) {object} See details on datamlico.lib.js and about the param 'params'. Params below are specific for this function.
* 	- template: (optional) {object} Specify a HTML template in order to display the results in a grid, rows, cells:
* 		- grid: (optional, default is "<div></div>") {string} HTML tag specifying what must be the conatainer for the grid. Eg:
* 			@code
* 			'<table></table>'
* 			@endcode
* 			This container gets the class = 'grid_class'
* 		- row: (mandatory) {string} HTML tag specifying what must be the container for a row. Eg:
* 			@code
* 			'<tr class="row_class"></tr>'
* 			// or
* 			'<div></div>'
* 			@endcode
* 			This container gets the class = 'row_class'
* 		- cell: (mandatory) {string} HTML tag specifying what must be the container for a cell. Eg:
* 			@code
* 			'<td class="cell_class"></td>'
*			// or 
*			'<span></span>'
*			@endcode
*			This container gets the class = 'cell_class'
* 		- header_cell: (optional) {string} HTML tag specifying what must be the container for a header_cell. Eg:
* 			@code
* 			'<th class="cell_class"></th>'
* 			@endcode
* 			Note that if you don't specify this parameter, there will be no column header.
* 			This container gets the class = 'header_cell_class'
* 	- manipulation: (optional, default is delupsert) {string} Specify what the grid is made for (a delupsert or a research). Possible values are:
* 		- 'delupsert' for INSERT, UPDATE or DELETE queries (See the general documentation of datamalico_server_ajax.lib.php)
* 		- 'research' for a SELECT sql query, that is to say, perform a research (See datamalico_server_ajax::research_get_form_structure())
* 	- columns_order: (optional) {numerical array} If you want to reorder results, you can specify it in an numerical array. all columns not specified in this list,
* 		keep their order, and placed after thoses in the columns_order list.
* 	- optgroup: (optional) {numrical array of objects} You can specify optgroups (like for any HTML &lt;select&gt; tag) ex: [{field: "service_type_id"}, {field: "service_id"}]. 
* 		The first element is the most external optgroup.
* 		- field: (mandatory) {string}
* 		- class: not implemented yet
* 		- accordion: not implemented yet
* 			- default: not implemented yet
* 		- REMARK that you can set css styles for optgroups. 
* 			- The first highest level of optgroup gets the class .optgroup_1
* 			- The second level of optgroup, gets the class .optgroup_2
* 			- ...
* 	- datatable_css_class_names (deprecated, optional) {object} This object contain css property for the target datatable:
* 		- odd_row: (optional) {string} Name of the class to use for odd rows.
* 		- even_row: (optional) {string} Name of the class to use for even rows.
*
* @return the datamalico object, so that you keep the chain jquery chaining ability.
*
* Example of use:
* @code
* $jq1001('#services_raw_results').datamalico(ajaxReturn).display_datagrid({
* 	template: {
* 		grid: '<table></table>'
* 		, row: '<tr></tr>' // '<div></div>'
* 		, cell: '<td></td>' //'<span></span>'
* 		, header_cell: '<th></th>' //'<i></i>'
* 	}
* 	, columns_order: ["demand_id", lang]
* 	, optgroup: [{field: "service_type_id"}, {field: "service_subtype1_id"}]
* });
* 
* makeup_rows ('#services_raw_results tr.row_class'); // put a make-up to rows
* @endcode
*
* Example of generated HTML code:
* @code
* <div class="optgroup_1" id="optgroup_1_1">
* 	<div class="optgroup_title_1">First level of optgroup, item 1</div>
* 
* 	<div class="optgroup_2" id="optgroup_2_1">
* 		<div class="optgroup_title_2">Second level of optgroup, item 1</div>
* 
* 		<div id="grid_20121009094740647" class="grid_class">
* 			<div id="row_1" class="row_class even_row">
* 				<span id="1_demand_id_20121009094740648" class="cell_class">
* 					<div id="1_demand_id_20121009094740648_sub">
* 						<input type="checkbox" name="inmulsel[1_demand_id_20121009094740648]" value="1">
* 						<input type="hidden" name="select[where][1_demand_id_20121009094740648][t]" value="mil_d_demand_2_service">
* 					</div>
* 				</span>
* 				<span id="1_french_20121009094740652" class="cell_class">
* 					<div id="1_french_20121009094740652_sub">
* 						<div class="element_into_display_mode">A checkbox label</div>
* 					</div>
* 				</span>
* 			</div>
* 			<div id="row_2" class="row_class odd_row">
* 				<span id="2_demand_id_20121009094740689" class="cell_class">
* 					<div id="2_demand_id_20121009094740689_sub">
* 						<input type="checkbox" name="inmulsel[2_demand_id_20121009094740689]" value="1">
* 						<input type="hidden" name="select[where][2_demand_id_20121009094740689][t]" value="mil_d_demand_2_service">
* 					</div>
* 				</span>
* 				<span id="2_french_20121009094740693" class="cell_class">
* 					<div id="2_french_20121009094740693_sub">
* 						<div class="element_into_display_mode">A second checkbox label</div>
* 					</div>
* 				</span>
* 			</div>
* 			<div id="row_3" class="row_class">...</div>
* 			<div id="row_4" class="row_class">...</div>
* 		</div>
* 	</div>
* 
* 	<div class="optgroup_2" id="optgroup_2_2">
* 		<div class="optgroup_title_2">Second level of optgroup, item 2</div>
* 		<div id="grid_20121009094741234" class="grid_class">
* 			<div id="row_5" class="row_class">...</div>
* 			<div id="row_6" class="row_class">...</div>
* 			<div id="row_7" class="row_class">...</div>
* 			<div id="row_8" class="row_class">...</div>
* 			<div id="row_9" class="row_class">...</div>
* 		</div>
* 	</div>
* </div>
* 
* <div class="optgroup_1" id="optgroup_1_2">
* 	<div class="optgroup_title_1">First level of optgroup, item 2</div>
* 
* 	<div class="optgroup_2" id="optgroup_2_3">
* 		<div class="optgroup_title_2">Second level of optgroup, item 3</div>
* 		<div id="grid_20121009094740859" class="grid_class">
* 			<div id="row_10" class="row_class">...</div>
* 			<div id="row_11" class="row_class">...</div>
* 		</div>
* 	</div>
* 
* 	<div class="optgroup_2" id="optgroup_2_4">
* 		<div class="optgroup_title_2">Second level of optgroup, item 4</div>
* 		<div id="grid_20121009094740859" class="grid_class">
* 			<div id="row_12" class="row_class">...</div>
* 			<div id="row_13" class="row_class">...</div>
* 		</div>
* 	</div>
* </div>
* 
* <div class="optgroup_1" id="optgroup_1_3">
* 	<div class="optgroup_title_1">First level of optgroup, item 3</div>
* 
* 	<div class="optgroup_2" id="optgroup_2_5">
* 		<div class="optgroup_title_2">Second level of optgroup, item 5</div>
* 		<div id="grid_20121009094740938" class="grid_class">
* 			<div id="row_14" class="row_class">...</div>
* 			<div id="row_15" class="row_class">...</div>
* 			<div id="row_16" class="row_class">...</div>
* 		</div>
* 	</div>
* </div> 
* @endcode
*
* @todo Make possible the fact of requiring a grid with header on the left (nice for tables with only one record or new object form).
* 	The solution would be to create the grid first (depending on line numbers and cell numbers), after that you populate the content 
* 	of cells using .eq() in order to find the nth line and nth col you need to populate.
* 	Such a system can work for header on top or bottom, header on left or right.
* 	Before beginning, think that there is work : you must manage columns_order, optgroup...
*/
datamalico_client.prototype.display_datagrid = function(display)
{
	//this.html_container_obj.dco_display_datagrid ({ajaxReturn: this.ajaxReturn, display: display});

	// ############################
	// Params and config
	if ($jq1001.type(this.ajaxReturn) === "undefined") return;

	// ###########################
	// work
	var id = this.html_container_obj.attr("id");
	// if no id for this element:
	if (!isset_notempty_notnull (id))
	{
		if ($jq1001.type(display.html_container) === "string")	id = display.html_container; 	// get the one from an optional param.html_container
		if (!isset_notempty_notnull (id)) id = this.html_container_obj.set_unique_id_generator ();	// or create and set a unique_id
	}

	display.html_container = id;

	dco_display_datagrid_procedural ({ajaxReturn: this.ajaxReturn, display: display});
	return this;
};

/**
* \memberof datamalico
*
* Public method of the datamalico object.
*
* @code
* $jq1001(".pagination").datamalico(ajaxReturn).paginate(display);
* @endcode
*
*
* It uses dco_paginate_procedural(), but serves as a jquery extension. So you can note that the following lines do the same:
* 	- $jq1001(".any_class_dedicated_to_pagination").datamalico(ajaxReturn).paginate ({...})
* 	- dco_paginate_procedural (... display: {html_container: "any_class_dedicated_to_pagination"}})
*
*
* @param params
* 	- ajaxReturn: {object} The response of the server. The 3 following metadata are required:
* 		- metadata:
* 			- affected_rows: {integer} Total number of results.
* 			- page: {integer} Current page to be displayed.
* 			- perpage: {integer} Number of results per page.
* 	- display: {object} Configuration for the display
* 		- pages_className: {string} Class selector for containers where to display pages links.
* 			Required if called via the dco_paginate_procedural(), but optional if called as the dco_paginate(), jquery extension.
* 		- report_ctnr: (optional) {string} Id of the HTML element where to write report informations. Default is "report".
* 		- render_this_inner_page: (optional) {function} Defines how to display the page. Default is function () {}
* 		- require_another_page:	 (optional) {function} Defines what to do when a page link is clicked.
* 			- In order to have a standard click on a link, set this to null. Default is null.
* 		- page_link_format: (optional, default is "?page={page}&perpage={perpage}") {string} This is the suffix that a url need to take into consideration 
* 			the page number and the perpage number (number of displayed results per page).
* 			For example:
* 			- the default string is "?page=[page]&perpage=[perpage]" and will append this string (with good page and perpage values) to call the url.
* 				The result could be: "this-present-page?page=3&perpage=10"
* 			- use "-[page]-[perpage]" to get this kind of result: "this-present-page-3-10". Thus, don't forget to make necessary work in your .htaccess
* 				file for an apache server.
*
*
* @warning See the function the dco_paginate_procedural() function. This funciton was written before being transposed to an object code. This is partially done.
*
* @todo Delete the use of dco_paginate_procedural() and rewrite its core into the datamalico::paginate() method.
*
* @note A new page can be displayed using AJAX or a standard web link.
* 	- Default AJAX behavior: If you want a custom action (like an ajax behavior), then, define the function params.display.require_another_page.
* 	- HTML href link: If you want to get a standard click on a link, set params.display.require_another_page to null.
* 		ATTENTION, in this case, the target link will be the same present url, with as suffix a suffix with as format: page_link_format (See the parameters).
* 		Thus do the necessary changes in your .htaccess for example in order to redirect "/myresults-3-25" to something like: "myresults?page=3&perpage=25"
*
*
*
* Example of use:
* @code
* // HTML and CSS code:
* <style type="text/css">
* 	#page_content .cell_class a { color:#575757; text-decoration:none; }
* </style>
*
* // Javascript code:
* function results_refresh ()
* {
* 	mil_ajax ({
* 		form_id: "research_demand_form"
* 		, data: {pagination: pagination}
* 		, url: "[+this_relative_file_path+]/server.get_results.ajax.php"
* 		, method: "POST" // Here I choose the POST method, because the GET one often answers that the request URI is too large.
* 		, success: on_success
* 	});
* 
* 	function on_success (data, textStatus, jqXHR)
* 	{
* 		var ajaxReturn = data;
* 		data = null;
* 
* 		if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;	
* 
* 		// ####################
* 		// WORK
* 		if (
* 			ajaxReturn.metadata.returnCode === "1_RESULT_DISPLAYED" ||
* 			ajaxReturn.metadata.returnCode === "X_RESULTS_DISPLAYED"
* 		)
* 		{
* 			// ##############################################################
* 			// CAUTION: Because of Firefox, functions render_this_inner_page() and require_another_page() must be defined before they are called.
* 			function render_this_inner_page (page_num) // This page_num, is necessary for the jquery paging
* 			//   extension. This is the number of the rendered page.
* 			{
* 				$jq1001("#results_book").find("#page_content").datamalico(ajaxReturn).display_datagrid({
* 					template: {
* 						grid: '<table></table>'
* 						, row: '<tr></tr>'
* 						, cell: '<td></td>'
* 						, header_cell: '<th></th>'
* 					}
* 				});
*
* 				makeup_rows ('#results_book #page_content tr.row_class'); // put a make-up to rows
* 				
* 				// ##########################################################################################
* 				// ##########################################################################################
* 				// ##########################################################################################
* 				//
* 				// 2 different ways in order to make the grid clickable:
* 				// The second way seems to be better for referencing, because of a real HTML link using herf and a url,
*				// 	while the 1st way is just a javascript action, which could be ignored by a crawler.
* 				//
* 
* 				// Transform text of a row into a clickable element (not good for crawler indexations):
* 				$jq1001("#results_book").find('#page_content table tr').click (function ()
* 				{
* 					// Retrieve the object_id displayed in the the first column of the row:
* 					var object_id = $jq1001(this).find('.cell_class').eq(0).find('.element_into_display_mode').html();
* 
* 					go2_TABS_ACTION_DATA (object_id);
* 				});
* 
* 
* 				// Transform text of a row into a real hypertext link and add a button to open the demand details:
* 				$jq1001("#results_book").find('#page_content table tr').each (function ()
* 				{
* 					var open_link_text = "[+open_link_text+]";	// HTML placeholder
* 
* 					var row_id = $jq1001(this).find('.cell_class').first().find('.element_into_display_mode').html();
* 					var type_rapido = $jq1001(this).find('.cell_class').eq(1).find('.element_into_display_mode').html();
* 
* 					$jq1001(this).data('identifier', {row_id: row_id, type_rapido: type_rapido});
* 
* 					var cellTag = $jq1001(this).find(':nth-child(1)').tagname();
* 					var href = "demande-" + row_id;
* 
* 					// Transform text of a row into a clickable link
* 					if (cellTag === "td")
* 					{
* 						$jq1001(this)
* 						.find(cellTag)
* 						.wrapInner(
* 							$jq1001('<a></a>')
* 							.attr('href', href)
* 							.attr('target', "_BLANK")
* 						);
* 					}
* 
* 					// add a button to open the demand details
* 					var open_link = ''; 
* 					if ($jq1001.type(row_id) !== "undefined")
* 					{
* 						open_link = $jq1001('<a></a>')
* 						.attr('href', href)
* 						.attr('target', "_BLANK")
* 						.html(open_link_text)
* 						.button();
* 					}
* 
* 					$jq1001('<'+cellTag+'></'+cellTag+'>')
* 					.append(open_link)
* 					.appendTo($jq1001(this));
* 				});
* 				// Remove cols with ids if necessary:
* 				$jq1001("#results_book").find('#page_content table tr').each (function ()
* 				{
* 					$jq1001(this).find('.header_cell_class').first().remove();	// remove the 1st cell of the header line
* 					$jq1001(this).find('.cell_class').first().remove();		// remove all first cells
* 				});
* 				// ##########################################################################################
* 				// ##########################################################################################
* 				// ##########################################################################################
* 
* 
* 			}
* 
* 			function require_another_page (page_num) // This page_num, is necessary for the jquery paging
* 			//     extension. This is the number of the clicked page.
* 			{
* 				// $jq1001("#page").val(page_num);    // only if this hidden form elem exists in the HTML page.
* 				pagination.page = page_num;     // Think also that the following datamalico_server_dbquery::
* 				//      select(), must specify the pagination param
* 				results_refresh ();             // recall the function where all this chunk takes place, so 
* 				//      that a this paginate() can be run again.
* 			}
* 
* 			$jq1001("#results_book").find(".pagination").datamalico(ajaxReturn).paginate({
* 				report_ctnr: "report"
* 				, render_this_inner_page: render_this_inner_page
* 				, require_another_page: require_another_page // null // use null for a normal HTML link (Read the whole documentation of this method).
* 						// See also the pagination variable (to be sent as GET or POST) in this Javascript client page, in the target PHP server page as a parameter of
* 						// datamalico_server_dbquery::select();
* 			});
* 		}
* 		else
* 		{
* 			alert (ajaxReturn.metadata.returnMessage);
* 		}
* 	}
* }
* @endcode
*/
datamalico_client.prototype.paginate = function(display)
{
	//this.html_container_obj.dco_paginate ({ajaxReturn: this.ajaxReturn, display: display});
	//return this;

	var params = {ajaxReturn: this.ajaxReturn, display: display};

	// ############################
	// Params and config
	config = check_params(params);
	function check_params(params)
	{
		if ($jq1001.type(params.ajaxReturn) === "undefined") return;
		if ($jq1001.type(params.display) === "undefined") params.display = {};
		return params;
	}


	// ###########################
	// work
	//var id = $jq1001(this).attr("id");
	var id = this.html_container_obj.attr("class");
	// if no id for this element:
	if (!isset_notempty_notnull (id))
	{
		if ($jq1001.type(params.display.html_container) === "string")	id = params.display.html_container; // get from an hypotetic param.display.html_container
		if (!isset_notempty_notnull (id))	id = $jq1001(this).set_unique_id_generator ();	// or create and set a unique_id
	}

	config.display.html_container = id;

	//dco_paginate (config, render_this_inner_page, require_another_page);
	dco_paginate_procedural ({ajaxReturn : config.ajaxReturn
		, display: {
			pages_className : config.display.html_container
			, report_ctnr : config.display.report_ctnr
			//, pages_className : config.display.pages_className
			//, page_ctnr : config.display.page_ctnr
			, render_this_inner_page : config.display.render_this_inner_page
			, require_another_page : config.display.require_another_page
			, page_link_format: config.display.page_link_format
		}
	});

	return this;
};


/**
* \memberof datamalico
*
* Public method of the datamalico object. This method displays errors in the html_container specified, and at the beginning or at the end of this 
* 	container depending on the paremeter display_err_msg.
*
* See also the are_there_invalid_data() of the datamalico_server_ajax class in datamalico_server_ajax.lib.php
* See also the datamalico_server_ajax::output when datamalico_server_ajax::are_there_invalid_data() is TRUE.
*
* @param ajaxReturn
* 	- display_error_msg: (optional, default is "before") {string} ["before"|"after"] Specify if the error message must be displayed displayed before or after 
* 		the wrong form field
*
* Example of use:
* @code
* if (ajaxReturn.metadata.returnCode === "API_HAS_BEEN_CALLED")
* {
* 	alert("Save is OK");
* }
* else if (ajaxReturn.metadata.returnCode === "THERE_ARE_INVALID_DATA")
* {
* 	//$jq1001(document).datamalico(ajaxReturn).display_errors (ajaxReturn);
* 	
* 	$jq1001("body")		// body, in order to find all potential error message zones within it.
* 	.datamalico(ajaxReturn)	// datamalico object creation.
* 	.display_errors({
* 		display_error_msg: "before" // displays the error message before the field.
* 	});
*
* 	alert("[+bad_form_input+]");
* }
* @endcode
*/
datamalico_client.prototype.display_errors = function(params)
{
	if (!isset_notempty_notnull (params))
	{
		params = {};
	}

	if (!isset_notempty_notnull (params.display_error_msg))
	{
		params.display_error_msg = "before";
	}

	this.ajaxReturn.display_error_msg = params.display_error_msg;	// "before", "after"
	dco_display_errors (this.ajaxReturn);
	return this;
};


// }}}


// {{{ jqyery general extension put at the end of the file, otherwise the documentor doxygen bugs.
// ################################################################################
// ################################################################################

/**
* $jq1001("#kryzaBloc").tagname()
*/
$jq1001.fn.tagname = function()
{
	//console.log($jq1001(this));
	//console.log($jq1001(this).get(0));
	//console.log($jq1001(this)[0]);
	return $jq1001(this).get(0).tagName.toLowerCase();
};


// is my old automatic_select
/**
* Execute a selection in a HTML select element.
*
* @param option_value: {string} (mandatory) Is the option value to select.
*
* @return It returns the jquery select element, so that it is still chainable.
*/
$jq1001.fn.do_selection = function(option_value)
{
	if ($jq1001(this).tagname() === "select")
	{
		$jq1001(this).find('option[value="' + option_value + '"]').attr("selected", "selected");
	}
	return $jq1001(this);
};

/**
* Deselect a selection in a HTML select element.
*
* @param option_value: {string} (mandatory) Is the option value to select.
*
* @return It returns the jquery select element, so that it is still chainable.
*/
$jq1001.fn.do_deselection = function(option_value)
{
	if ($jq1001(this).tagname() === "select")
	{
		$jq1001(this).find(' option[value="' + option_value + '"]').attr("selected", false);
	}
	return $jq1001(this);
};


// http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area
// $jq1001("texteare").selectRange(3,5);
// has a bug so far I remember.
$jq1001.fn.selectRange = function(start, end)
{
	return this.each(function() {
		if (this.setSelectionRange) {
			this.focus();
			this.setSelectionRange(start, end);
		} else if (this.createTextRange) {
			var range = this.createTextRange();
			range.collapse(true);
			range.moveEnd('character', end);
			range.moveStart('character', start);
			range.select();
		}
	});
};

/**
* Generates and set an id for an html element with the $jq1001 version.
*
* fjdksql fjdkslm
*/
$jq1001.fn.set_unique_id_generator = function ()
{
	$jq1001(this).each (function( index ) {
		var unique_id = get_unique_id_generator ($jq1001(this));
		$jq1001(this).attr("id", unique_id);
	});
	return $jq1001(this);
};


/**
* I think that The code below creates a class extending ithe autocomplete class.
* Thus, instead of invoking the autocomplete class, you must invoke mil_autocomplete.
*/
$.widget( "custom.mil_autocomplete", $.ui.autocomplete, {
	_renderMenu: function( ul, items ) {
		opts = this.options;

		var self = this,
		currentCategory = "";
		$.each( items, function( index, item ) {
			if (opts.mil_ac_categories === true)
			{
				if ( item.category != currentCategory ) {
					ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
					currentCategory = item.category;
				}
			}
			self._renderItem( ul, item );
		});
	}
});

// }}}


// {{{ Methods in order to work with jQuery UI (because datamalico $jq1001 uses a controled version of jquery, whereas the jQuery object in the general scope can be the one of your own page)
// This avoid jquery version conflicts.
// ################################################################################
// ################################################################################

/**
* Generates and set an id for an html element with the standard jQuery version.
*
* fjdksql fjdkslm
*/
jQuery.fn.set_unique_id_generator = function ()
{
	jQuery(this).each (function( index ) {
		var unique_id = get_unique_id_generator (jQuery(this));
		jQuery(this).attr("id", unique_id);
	});
	return jQuery(this);
};

/**
* jQuery("#kryzaBloc").tagname()
*/
jQuery.fn.tagname = function()
{
	//console.log(jQuery(this));
	//console.log(jQuery(this).get(0));
	//console.log(jQuery(this)[0]);
	return jQuery(this).get(0).tagName.toLowerCase();
};

// }}}


// {{{ Utilities
// ################################################################################
// ################################################################################

function now ()
{
	var d = new Date();

	var year = d.getFullYear();
	var month = d.getMonth() + 1; if (month < 10) month = "0"+month;
	var day = d.getDate(); if (day < 10) day = "0"+day;

	var hour = d.getHours(); if (hour < 10) hour = "0"+hour;
	var minute = d.getMinutes(); if (minute < 10) minute = "0"+minute;
	var second = d.getSeconds(); if (second < 10) second = "0"+second;

	var iso_now = year + "-" + month + "-" + day + " " + hour + ":" + minute + ":" + second;

	return iso_now;
}

function nowCET ()
{
	var d = new Date();

	var year = d.getFullYear();
	var month = d.getMonth() + 1; if (month < 10) month = "0"+month;
	var day = d.getDate(); if (day < 10) day = "0"+day;

	var hour = d.getHours(); if (hour < 10) hour = "0"+hour;
	var minute = d.getMinutes(); if (minute < 10) minute = "0"+minute;
	var second = d.getSeconds(); if (second < 10) second = "0"+second;

	var iso_now = year + "-" + month + "-" + day + "T" + hour + ":" + minute + ":" + second;

	return iso_now;
}

/**
* Written by Sachin Khosla: http://www.digimantra.com/tutorials/sleep-or-wait-function-in-javascript/
*/
function sleep(ms)
{
	var dt = new Date();
	dt.setTime(dt.getTime() + ms);
	while (new Date().getTime() < dt.getTime());
}


/**
* Returns a temp_insert_id. See datamalico_server_dbquery::select_empty() in the datamalico_server_dbquery.lib.php file.
*
* This function uses get_unique_time_id()
* @return {string} TEMPINSERTID_ {year} + "" + {month} + "" + {day} + "" + {hour} + "" + {minute} + "" + {second} + "" + {millisec}
* @code
* - ex: TEMPINSERTID_20120806085027531
* @endcode
*/
function dco_get_temp_insert_id ()
{
	return "TEMPINSERTID_" + get_unique_time_id ();
}

/**
* Generates a unique_id for an element.
* @param elem {HTML element}
* @return {string} elem.tagname() + "_" + get_unique_time_id()
*/
function get_unique_id_generator (elem)
{
	var unique_id = elem.tagname() + "_" + get_unique_time_id ();
	return unique_id;
}

// }}}


// {{{ Section for the mil_ HTML handling
// ################################################################################
// ################################################################################

/**
* Attach a "click" and/or "dblclick" event handler function for one or more events to the selected elements.
*
* Most of the time, libraries (jquery as well) allow to bind either a click event or a double click event. But both on the same element can become quite problematic
* because any dblclick begins by a simple click, and thus this simple click is triggered 2 times, instead of one double click.
*
* This event listener makes a real difference between both.
*
* Use off_click_or_dblclick() in order to remove these handlers.
*
* @param params {object} Params of the listener.
* 	- click: (optional) {function} Define the handler for the click event. Default is an empty function.
* 	- dblclick : (optional) {function} Define the handler for the dblclick event. Default is an empty function.
* 	- dblclick_time: (optional) {integer} number of miliseconds in which a double click must be recognized. Default is 250.
*
* Example of use:
* @code
* $jq1001('any_selector').on_click_or_dblclick ({click: row_selection
* 		, dblclick: row_dblclick
* 		});
* function row_selection ()
* {
* 	alert("row_click");
* 	selected_row = "selected_row";
* 	$jq1001('.' + selected_row).removeClass (selected_row);
* 	$jq1001(this).addClass(selected_row);
* }
* function row_dblclick ()
* {
* 	alert("row_dblclick");
* }
* @endcode
*/
$jq1001.fn.on_click_or_dblclick = function (params)
{	
	//$jq1001(this).off_click_or_dblclick ({event_namespace: params.event_namespace});
	$jq1001(this).off_click_or_dblclick ();
	params.clicks = 0;
	//$jq1001(this).on ("click." + params.event_namespace, params, _click_or_dblclick);
	$jq1001(this).on ("click", params, _click_or_dblclick);
};

/**
* Remove on_click_or_dblclick.
*
* Example of use:
* @code
* $jq1001('any_selector').off_click_or_dblclick ();
* @endcode
*/
$jq1001.fn.off_click_or_dblclick = function ()
{
	//$jq1001(this).off ("click." + params.event_namespace, _click_or_dblclick);
	$jq1001(this).off ("click", _click_or_dblclick);
};

/**
* Private method, don't use directly, but use on_click_or_dblclick() as a jquery extension.
*/
function _click_or_dblclick (event)
{
	//console.log(event);
	//event.stopImmediatePropagation();
	//event.stopPropagation();

	// ############################
	// Params and config
	var params = event.data;
	var config = {dblclick_time: 250	// max time for a double click (in millisec)
		, click: function () {}
		, dblclick: function () {}
	};

	if ($jq1001.type(params.dblclick_time) !== "undefined") config.dblclick_time = params.dblclick_time;

	if ($jq1001.type(params.click) === "function") config.click = params.click;
	//if ($jq1001.type(params.click.params) === "object") config.click.params = params.click.params;

	if ($jq1001.type(params.dblclick) === "function") config.dblclick = params.dblclick;
	//if ($jq1001.type(params.dblclick.params) === "object") config.dblclick.params = params.dblclick.params;

	config.clickss = params.clickss;


	// ############################
	// work
	//node = $jq1001(this);
	//eval(params.event_namespace).clicks++; //clicks++;
	//console.log("global: " + params.event_namespace + ".clicks = " + eval(params.event_namespace).clicks);
	params.clicks++;
	//console.log("params: " + params.event_namespace + " params.clicks = " + params.clicks);

	//if (eval(params.event_namespace).clicks == 1)
		if (params.clicks == 1)
		{
			/*setTimeout(function() {
				if(eval(params.event_namespace).clicks == 1) { single_click ();}
				else { dblclick ();}
				eval(params.event_namespace).clicks = 0;
				}, config.dblclick_time);*/
				setTimeout(function() {
					if(params.clicks == 1) { single_click ();}
					else { dblclick ();}
					params.clicks = 0;
				}, config.dblclick_time);
		}

		// Single Click ############
		function single_click ()
		{
			//console.log('single click!');
			//alert("single click!");
			//config.click();
			config.click.call(event.delegateTarget); // call helps to call the function, and within the called function, 'this' is actually : event.delegateTarget
		}

		// double_click    ############
		function dblclick ()
		{

			//console.log('double click!');
			//alert("2bl click!");
			//config.dblclick();
			config.dblclick.call(event.delegateTarget); // call helps to call the function, and within the called function, 'this' is actually : event.delegateTarget
		}
}


// }}}


// {{{ dco means "datamalico" meaning "data managing library for coders".
// This all in one (php, javascript, html) library is written by Christophe Delcourte in order to ease interactions between frontend and backend.
// This all in one (php, javascript, html) library is written by Christophe DELCOURTE in order to ease data handling between front-end and back-end
// ################################################################################
// ################################################################################


/**
* See the display() method of the Javascript class: datamalico.
* 
* Example of use:
* @code
* dco_display_procedural ({ajaxReturn: config.ajaxReturn
* 			, display: {
* 				field_name : field_name
* 				, row_num: row_num
* 				, html_container: table_cell_element_id
* 			}
* 		});
* @endcode
*/
function dco_display_procedural (params)
{
	// ############################
	// Params and config
	var config = {};

	if ($jq1001.type(params.ajaxReturn) === "undefined") return;
	else config.ajaxReturn = params.ajaxReturn;

	if (isset_notempty_notnull(params.display)) config.display = params.display;

	if ($jq1001.type(params.display.field_name) === "undefined") return;
	else config.display.field_name = params.display.field_name;

	if ($jq1001.type(params.display.row_num) === "undefined") config.display.row_num = 1;
	else config.display.row_num = params.display.row_num;

	if ($jq1001.type(params.display.html_container) === "undefined") return;
	else config.display.html_container = params.display.html_container;

	if (!isset_notempty_notnull(config.ajaxReturn.results.field_structure[config.display.field_name]))
	{
		config.ajaxReturn.results.field_structure[config.display.field_name] = {frontend_access: {form_field_type: "text"}};
	}

	if (!isset_notempty_notnull(config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type))
	{
		config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type = "text";
	}
	else if (
		config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "text"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "textarea"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "datepicker"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "select"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "autocomplete"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "checkbox_multiselist"
		&& config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toLowerCase() !== "radio_singleselist"
	)
	{
		config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type = "text";
	}

	// This selected_in_multiselist_in_db field is a convention. This selected_in_multiselist_in_db must say if the item belongs or not to the selection.
	// 	This selected_in_multiselist_in_db field must be specified in the select clauses of the UNION query for any join table.
	if (
		isset(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db)
		&& !isitnull(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db)
	)
	{
		//console.log("--> "+ config.display.html_container);
		//console.log("config.display.row_num: " + config.display.row_num);
		//console.log($jq1001.type(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db));
		//console.log(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);

		// selected_in_multiselist_in_db is the memory to help to know further, if one will do an insert (without conditions) or an update (with conditions)
		if (
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === "1"	// just after getting server ajax answer
			|| config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true	// after a change event on the HTML elem
		)
		{
			//console.log("is true");
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db = true;
		}
		else
		{
			//console.log("is not true");
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db = false;
		}

		// selected_in_multiselist_in_interface is to help to check or not the checkbox_multiselist and reveal or not other attributes related
		if (!isset_notempty_notnull(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface))
		{
			//console.log("selected_in_multiselist_in_interface has just been set");
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface =
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db;
		}
		//console.log($jq1001.type(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db));
		//console.log(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);
	}
	else
		// selected_in_multiselist_in_db is null if the field is not belonging to a multiple selection list
	{
		config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db = null;
	}
	//console.log(config.ajaxReturn.results.records);


	//$jq1001("#div_ajax_serverScript2").append(debugDisplayTable (params, "params"));
	//$jq1001("#div_ajax_serverScript2").append(debugDisplayTable (config, "params-config"));

	//console.log("--> " + params.display.field_name);
	//console.log("params: " + params.ajaxReturn.results.records[params.display.row_num][params.display.field_name]);
	//console.log("params-config: " + config.ajaxReturn.results.records[config.display.row_num][config.display.field_name]);


	if ($jq1001.type(params.display.manipulation) === "undefined") config.display.manipulation = "delupsert";
	else if (
		params.display.manipulation === "delupsert"
		|| params.display.manipulation === "research"
		//|| params.display.manipulation === "research_advanced"
	)
	{
		config.display.manipulation = params.display.manipulation;
	}
	else config.display.manipulation = "delupsert";

	//console.log(config.display.html_container + " = " + config.display.manipulation);


	$jq1001('#'+config.display.html_container).data('display', config.display);



	// ###########################
	// work

	//console.log(config.display.field_name);
	var rights = config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.rights;
	rights = rights.toString().toLowerCase();
	config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.rights = rights;
	if (rights === "hidden") return;

	// display :
	config.edition = {};

	config.org_field_name = config.ajaxReturn.results.field_structure[config.display.field_name].field_direct.orgname;
	config.table_name = config.ajaxReturn.results.field_structure[config.display.field_name].field_direct.table;
	config.org_table_name = config.ajaxReturn.results.field_structure[config.display.field_name].field_direct.orgtable;

	// Naming of the container containing the element in read or write mode. Takes place into another element (html_container) defined in the html template.
	//config.display.container = config.display.row_num + '_' + config.display.field_name;
	config.display.container = config.display.html_container + "_sub";

	var db_stored_value = config.ajaxReturn.results.records[config.display.row_num][config.display.field_name];

	$jq1001('#' + config.display.html_container).empty();
	//$jq1001('#' + config.display.html_container).append('<div id="' + config.display.container + '"></div>');
	$jq1001('<div></div>')
	.attr('id', config.display.container)
	.appendTo('#' + config.display.html_container);

	// if there is NO valuelist:
	if (!isset_notempty_notnull(config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.valuelist))
	{
		config.edition.old_db_val = db_stored_value;
		config.edition.old_value_to_be_displayed = db_stored_value;

		//console.log('valuelist is NOT set: ' + config.display.html_container + " - " + config.display.field_name + " - " + config.edition.old_value_to_be_displayed);

		if ($jq1001.type(config.edition.old_value_to_be_displayed) === "null") config.edition.old_value_to_be_displayed = "";


		// For 
		if (
			config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toString().toLowerCase() === "checkbox_multiselist"
			|| config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toString().toLowerCase() === "radio_singleselist"
		)
		{
			if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true)
			{
				//console.log(config.ajaxReturn.results.field_structure[config.display.field_name]);
				$jq1001('<div></div>')
				.addClass("checkBox")
				.html(" ")
				.appendTo('#' + config.display.container);
			}
			else
			{
				//console.log(config.ajaxReturn.results.field_structure[config.display.field_name]);
				$jq1001('<div></div>')
				.addClass("checkBoxClear")
				.html(" ")
				.appendTo('#' + config.display.container);
			}
		}
		else
		{
			//$jq1001('#' + config.display.container).append('<div class="element_into_display_mode">' + config.edition.old_value_to_be_displayed + '</div>');
			$jq1001('<div></div>')
			.addClass("element_into_display_mode")
			.html(config.edition.old_value_to_be_displayed)
			.appendTo('#' + config.display.container);
		}
	}
	else // if there is a valuelist:
	{
		var valuelist = config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.valuelist;
		var isnull;
		if (!isset_notempty_notnull(db_stored_value))
		{
			isnull = true;
			db_stored_value = 0;
		}

		config.edition.old_db_val = db_stored_value;
		if (isnull === true)
		{
			config.edition.old_value_to_be_displayed = "";
		}
		else
		{
			config.edition.old_value_to_be_displayed = valuelist[db_stored_value];
		}

		//console.log('valuelist is set: ' + config.display.html_container + " - " + config.display.field_name + " - " + config.edition.old_value_to_be_displayed);

		//$jq1001('#' + config.display.container).append('<div class="element_into_display_mode">' + config.edition.old_value_to_be_displayed + '</div>');
		$jq1001('<div></div>')
		.addClass("element_into_display_mode")
		.html(config.edition.old_value_to_be_displayed)
		.appendTo('#' + config.display.container);
	}


	// #####################################################
	// Editable or not:
	if (config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.rights === "read")
	{
		//console.log("frontend_access.accesses.rights: read");
	}
	else if (config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.rights === "write")
	{
		//console.log("frontend_access.accesses.rights: write");
		//console.log(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);
		//console.log(config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface);
		// never make editable an element that is not selected_in_multiselist_in_interface
		if (
			config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface !== false
			|| config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toString().toLowerCase() === "checkbox_multiselist"
			|| config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.form_field_type.toString().toLowerCase() === "radio_singleselist"
		)
		{
			if (isset_notempty_notnull (config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.behavior))
			{
				var behavior = config.ajaxReturn.results.field_structure[config.display.field_name].frontend_access.accesses.behavior;
				behavior = behavior.toString().toLowerCase();

				if (behavior === "onready")
				{
					config.edition.onevent = "ready";
					dco_edition_event ({data : config});
				}
				if (behavior === "onmouseenter")
				{
					config.edition.onevent = "mouseenter";
					$jq1001('#' + config.display.container).on("mouseenter", config, dco_edition_event);
					$jq1001('#' + config.display.container).on("mouseleave", config, dco_focusout_and_save_event);
				}
				if (behavior === "onclick")
				{
					//$jq1001(document).on("click", "#" + config.display.container, {config : config}, dco_on_cell_click);
					//$jq1001(document).on("click", "#" + config.display.container, {config : config, edition_event : "click"}, dco_edition_event);
					config.edition.onevent = "click";
					//$jq1001('#' + config.display.container).on_click_or_dblclick ({event_namespace: "dco_edition_namespace"
						//		, click: container_onclick
					//		});

					$jq1001('#' + config.display.container).on_click_or_dblclick ({click: container_onclick});
					function container_onclick ()
					{
						dco_edition_event ({data : config});
					}
				}
				if (behavior === "ondblclick")
				{
					//$jq1001(document).on("dblclick", "#" + config.display.container, {config : config, edition_event : "dblclick"}, dco_edition_event);
					config.edition.onevent = "dblclick";
					//$jq1001('#' + config.display.container).on_click_or_dblclick ({event_namespace: "dco_edition_namespace"
						//		, dblclick: container_ondblclick
					//		});
					$jq1001('#' + config.display.container).on_click_or_dblclick ({dblclick: container_ondblclick});
					function container_ondblclick ()
					{
						dco_edition_event ({data : config});
					}
				}
			}
		}
	}
}

function dco_display_datagrid_procedural (params)
{
	// ajaxReturn.results.records[row_num][field_name]
	// ajaxReturn.results.field_structure[field_name].frontend_access.access_rights

	// ############################
	// Params and config
	var config = {};

	if ($jq1001.type(params.ajaxReturn) === "undefined") return;
	else config.ajaxReturn = params.ajaxReturn;

	if (isset_notempty_notnull(params.display)) config.display = params.display;
	else config.display = {};

	if (isset_notempty_notnull(params.display.template)) config.display.template = params.display.template;
	else config.display.template = {
		grid: '<table></table>'
		, row: '<tr></tr>' // '<div></div>'
		, cell: '<td></td>' //'<span></span>'
		, header_cell: '<th></th>' //'<i></i>'		
	};

	if ($jq1001.type(params.display.html_container) === "undefined") return;
	else config.display.html_container = params.display.html_container;

	if ($jq1001.type(params.display.manipulation) === "undefined") config.display.manipulation = "delupsert";
	else if (
		params.display.manipulation === "delupsert"
		|| params.display.manipulation === "research"
		//|| params.display.manipulation === "research_advanced"
	)
	{
		config.display.manipulation = params.display.manipulation;
	}
	else config.display.manipulation = "delupsert";


	// ###########################
	// work
	$jq1001('#' + config.display.html_container).empty();
	//var table_element_id = "grid_" + get_unique_time_id ();

	var grid = "";
	//console.log(config.display);
	if (isset_notempty_notnull(config.display.template.grid))
	{
		grid = config.display.template.grid;
	}
	else
	{
		grid = "<div></div>";
	}

	//$jq1001(grid).attr('id', table_element_id).appendTo($jq1001('#' + config.display.html_container));
	//var table_element = $jq1001('#' + table_element_id);
	//console.log('Grid:');
	//console.log(table_element);
	//console.log($jq1001('#' + config.display.html_container));

	// columns_order, the goal of the following part is to reorder columns according to the parameter: columns_order sent to this function.
	if (
		isset_notempty_notnull(config.ajaxReturn.results.records)
		&& isset_notempty_notnull(config.display.columns_order)
	)
	{
		for (i in config.ajaxReturn.results.records)
		{
			config.ajaxReturn.results.records[i] = columns_reordering (config.ajaxReturn.results.records[i], config.display.columns_order);
		}
	}
	//$jq1001("#div_debug_display").append(debugDisplayTable (config.ajaxReturn.results.records, "config.ajaxReturn.results.records"));



	var optgroup_containers = {};

	// tbody:
	if (isset_notempty_notnull(config.ajaxReturn.results.records))
	{
		// ###################################
		// For each record of the datagrid:
		//$jq1001('#' + config.display.html_container + " div").append("");

		for (row_num in config.ajaxReturn.results.records)
		{
			//var optgroup_current_hierarchy_selector = "#" + config.display.html_container + " ";
			// build row:
			var table_row_element_id = "row_" + row_num;
			//$jq1001('#' + config.display.html_container + " table tbody").append('<tr id="' + table_row_element_id + '"></tr>\n\n');

			$jq1001(config.display.template.row+'\n\n')
			.attr('id', table_row_element_id)
			.addClass('row_class')
			.appendTo('#'+config.display.html_container);

			var table_row_element = $jq1001('#' + config.display.html_container + " #" + table_row_element_id);

			//table_row_element.html(table_row_element_id);


			// ############################
			// Check if there are optgroups and if the record is in one or several optgroups
			if (isset_notempty_notnull(config.display.optgroup))
			{
				for (optgroup_index in config.display.optgroup)
				{
					var optgroup_name = config.display.optgroup[optgroup_index].field;
					var db_stored_value = config.ajaxReturn.results.records[row_num][optgroup_name];

					if (isset_notempty_notnull(db_stored_value))
					{
						var optgroup_level = parseInt(optgroup_index) + 1;
						var optgroup_id = "optgroup_" + optgroup_level + "_" + db_stored_value;
						var value_to_be_displayed;
						if (isset_notempty_notnull(params.ajaxReturn.results.field_structure[optgroup_name].frontend_access.valuelist))
						{
							value_to_be_displayed = params.ajaxReturn.results.field_structure[optgroup_name].frontend_access.valuelist[db_stored_value];
						}
						else
						{
							value_to_be_displayed = db_stored_value;
						}
						var key_container = optgroup_name + "_" + db_stored_value;
						optgroup_containers[key_container] = {
							optgroup_id: optgroup_id
							, optgroup_name: optgroup_name
							, optgroup_level: optgroup_level
							, db_stored_value: db_stored_value
							, value_to_be_displayed: value_to_be_displayed
						};
					}

					//console.log("--------------------------");
					//console.log(config.ajaxReturn.results.records[row_num].french);
					//console.log(optgroup_name);
					//console.log(config.ajaxReturn.results.records[row_num][optgroup_name]);
					//console.log(isset_notempty_notnull(config.ajaxReturn.results.records[row_num][optgroup_name]));
				}
			}


			// build cell:
			for (field_name in config.ajaxReturn.results.records[row_num])
			{
				var optgroup_current_hierarchy_selector = "#" + config.display.html_container + " ";
				//console.log(config.ajaxReturn.results.records[row_num].french + " : " + field_name);

				// if field_name is one of the optgroup, then, don't display it.
				var is_one_of_the_optgroup = false;
				for (optgroup_index in config.display.optgroup)
				{
					var optgroup_name = config.display.optgroup[optgroup_index].field;
					if (field_name === optgroup_name)
					{
						is_one_of_the_optgroup = true;
						break;
					}
				}

				// Display the field only if the field is not displayed as an optgroup
				if (is_one_of_the_optgroup === false)
				{
					// Get selectors where to display the field
					for (optgroup_index in config.display.optgroup)
					{
						var optgroup_name = config.display.optgroup[optgroup_index].field;
						var db_stored_value = config.ajaxReturn.results.records[row_num][optgroup_name];

						if (isset_notempty_notnull(db_stored_value))
						{
							var key_container = optgroup_name + "_" + db_stored_value;

							// if the optgroup container doesn't exist, then create it 
							if ($jq1001(optgroup_current_hierarchy_selector + ' #'+optgroup_containers[key_container].optgroup_id).length === 0)
							{
								$jq1001("<div></div>")
								.addClass("optgroup_" + optgroup_containers[key_container].optgroup_level)
								.attr("id", optgroup_containers[key_container].optgroup_id)
								.appendTo(optgroup_current_hierarchy_selector);

								optgroup_current_hierarchy_selector += ' #'+optgroup_containers[key_container].optgroup_id + ' ';

								$jq1001("<div>" + optgroup_containers[key_container].value_to_be_displayed + "</div>")
								.addClass("optgroup_title_" + optgroup_containers[key_container].optgroup_level)
								.appendTo(optgroup_current_hierarchy_selector);
							}
							else
							{
								optgroup_current_hierarchy_selector += ' #'+optgroup_containers[key_container].optgroup_id + ' ';
							}
						}
					}


					// wrap in the grid
					var existing_grids = $jq1001(optgroup_current_hierarchy_selector + ' [id^="grid_"]').length;
					if (existing_grids === 0) // grid creation
					{
						var grid_element_id = "grid_" + get_unique_time_id ();

						$jq1001(grid)
						.attr('id', grid_element_id)
						.addClass('grid_class')
						.appendTo(optgroup_current_hierarchy_selector);
					}

					//console.log(table_row_element.attr("id"));
					//console.log(optgroup_current_hierarchy_selector + ' [id^="grid_"]');
					table_row_element.appendTo(optgroup_current_hierarchy_selector + ' [id^="grid_"]');

					if (isset_notempty_notnull(config.ajaxReturn.results.field_structure[field_name]))
					{
						var rights = config.ajaxReturn.results.field_structure[field_name].frontend_access.accesses.rights;
						rights = rights.toString().toLowerCase();
						config.ajaxReturn.results.field_structure[field_name].frontend_access.accesses.rights = rights;

						if (rights !== "hidden")
						{
							var table_cell_element_id = row_num + '_' + field_name + "_" + get_unique_time_id ();
							//table_row_element.append('<td id="' + table_cell_element_id + '"></td>\n');

							$jq1001(config.display.template.cell+'\n\n')
							.attr('id', table_cell_element_id)
							.addClass('cell_class')
							.appendTo(table_row_element);
							var table_cell_element = $jq1001('#' + table_cell_element_id);

							//console.log("Cell:");
							//console.log(table_cell_element);
							//console.log(table_cell_element_id);
							//table_cell_element.html(table_cell_element_id);

							dco_display_procedural ({ajaxReturn: config.ajaxReturn
								, display: {
									field_name : field_name
									, row_num: row_num
									, html_container: table_cell_element_id
									, manipulation: config.display.manipulation
								}
							});
						}
					}
				}
			}

			//console.log('Line:');
			//console.log(table_row_element);
		}
	}

	// thead:
	if (isset_notempty_notnull(config.display.template.header_cell))
	{
		//console.log(config.display.template.header_cell);
		$jq1001(config.display.template.row+'\n\n')
		.addClass("row_header_class")
		.prependTo('#' + config.display.html_container + ' [id^="grid_"]');

		//console.log($jq1001('#' + config.display.html_container + ' [id^="grid_"]'));

		// Here we parse the first record in order to get all its field_name
		var record = 1;
		for (record_num in config.ajaxReturn.results.records)
		{
			if (record === 1)
			{
				// Here there are some case, where the first record of the config.ajaxReturn.results.records is the number X (not always 1).
				// 	So we make a loop just in order to take the first one (whatever the number it is).
				for (field_name in config.ajaxReturn.results.records[record_num])
				{
					// if field_name is one of the optgroup, then, don't display it.
					var is_one_of_the_optgroup = false;
					for (optgroup_index in config.display.optgroup)
					{
						var optgroup_name = config.display.optgroup[optgroup_index].field;
						if (field_name === optgroup_name)
						{
							is_one_of_the_optgroup = true;
							break;
						}
					}

					// Display the field only the field is not displayed as an optgroup
					if (is_one_of_the_optgroup === false)
					{
						if (isset_notempty_notnull(config.ajaxReturn.results.field_structure[field_name]))
						{
							var rights = config.ajaxReturn.results.field_structure[field_name].frontend_access.accesses.rights;
							rights = rights.toString().toLowerCase();
							config.ajaxReturn.results.field_structure[field_name].frontend_access.accesses.rights = rights;
							if (rights !== "hidden")
							{
								var column_label = field_name;
								if (isset_notempty_notnull(config.ajaxReturn.results.field_structure[field_name].frontend_access.field_label))
									column_label = config.ajaxReturn.results.field_structure[field_name].frontend_access.field_label;

								//$jq1001('#' + config.display.html_container + " table thead tr").append('<th id="th_"' + field_name + '>' + column_label + '</th>');
								$jq1001(config.display.template.header_cell+'\n\n')
								.text(column_label)
								.addClass('header_cell_class')
								.appendTo('#' + config.display.html_container + ' [id^="grid_"] [class*="row_header_class"]');
							}
						}
					}
				}
			}
			else
			{
				break;
			}

			record += 1;
		}
	}
}

/**
* Reorder a record according to columns_order (a list of field). Fields not mentionned in the columns_order param, will be put at the end of the reordered record.
*
* @param record {object} the record to be reordered ex: {field_name_1: "value_1", field_name_2: "value_2", field_name_3: "value_3"}
* @param columns_order {numerical array} the list of first fields ex: [field_name_2, field_name_3]
* @return {object} The reordered record, ex: {field_name_2: "value_2", field_name_3: "value_3", field_name_1: "value_1"}
*/
function columns_reordering (record, columns_order)
{
	//$jq1001("#div_debug_display").append(debugDisplayTable (record, "record"));
	var reordered_record = {};

	// put columns specified in the columns_order array:
	for (i in columns_order)
	{
		var field = columns_order[i];
		reordered_record[field] = record[field];
		record[field] = undefined;
	}

	// put all other columns that are not specified:
	for (field in record)
	{
		if (record[field] !== undefined)
		{
			reordered_record[field] = record[field];
		}
	}

	//$jq1001("#div_debug_display").append(debugDisplayTable (reordered_record, "reordered_record"));
	return reordered_record;
}

function get_function_name (function_as_string)
{

	var lines = function_as_string.split("\n");
	var definition_line;
	for (i in lines)
	{
		var current_line = $jq1001.trim(lines[i]);

		var pattern = /^function/gi;
		var is_definition_line = pattern.test(current_line);

		//console.log(i + ":[" + is_definition_line + "]" + current_line);

		if (is_definition_line === true)
		{
			definition_line = current_line;
			break;
		}
	}

	definition_line = definition_line.replace(/function/gi, "");
	definition_line = definition_line.replace(/\(.*/gi, "");
	handler_name = $jq1001.trim(definition_line);

	return handler_name;
}


//var dco_edition_namespace = {clicks:0}; // this var must be global, in order to be known by the on_click_or_dblclick custom event.
//function dco_on_cell_click (event)
function dco_edition_event (event)
{
	//console.log("dco_edition_event");
	if (isset_notempty_notnull(event.type)) event.stopImmediatePropagation(); // this event can be launched by a real event or also like a simple function call.

	var config = event.data;
	//console.log(config.display.field_name);
	//console.log(config.ajaxReturn.results.field_structure[config.display.field_name]);

	$jq1001('#' + config.display.container).empty();
	config.edition.onevent = config.edition.onevent.toString().toLowerCase();
	var this_field_structure = config.ajaxReturn.results.field_structure[config.display.field_name];
	//var table_name = config.ajaxReturn.results.field_structure[config.display.field_name].field_direct.orgtable;
	var html_ctnr = config.display.html_container;
	var manip = config.display.manipulation;
	//if (manip === "research" || manip === "research_where") manip = "select[where]";
	if (manip === "research") manip = "select[where]";
	//console.log(config.display.manipulation + " - " + manip);
	//console.log(this_field_structure.frontend_access.form_field_type.toString().toLowerCase());



	// #######################################
	// creating the new config.edition.elem_in_edit_mode

	//$jq1001('#' + config.display.container).append('<span></span>'); // helps to wrap with a form on atomic upsert


	// ##############################################################################
	// with an HTML input text element :
	if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "text")		
	{
		//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
		//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + config.org_field_name + '" />');
		//$jq1001('#' + config.display.container).append('<input type="text" name="'+manip+'[' + html_ctnr + '][fv][]" value="' + config.edition.old_value_to_be_displayed + '" />');
		$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

		if (manip === "delupsert")
		{
			//$jq1001('#' + config.display.container)
			//.append('<input type="text" name="'+manip+'[' + html_ctnr + '][f]['+
				//config.org_field_name+']" value="' + config.edition.old_value_to_be_displayed + '" />');

			$jq1001('<input type="text" />')
			.attr('name', manip + '[' + html_ctnr + '][f][' + config.org_field_name + ']')
			.attr('value', config.edition.old_value_to_be_displayed)
			.appendTo('#' + config.display.container);

			// condition on primary keys
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			//$jq1001.each(primary_keys, text_iterate_through_primkeys);
			//function text_iterate_through_primkeys (index, primkey)
			for (index in primary_keys)
			{
				var condition_name = primary_keys[index]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
				//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
				$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
			}
		}
		else if (manip === "select[where]")
		{
			var html_op = display_search_operator (config);
			manip = "select[where]";

			$jq1001('<input type="text" />')
			.attr('name', manip + '[' + html_ctnr + '][c][' + config.org_field_name + ']')
			.attr('value', config.edition.old_value_to_be_displayed)
			.appendTo('#' + config.display.container);

			html_op.trigger("change");

			// WHERE Grouping info:
			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][name]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][parent]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
			.appendTo('#' + config.display.container);

			// add the client data validator
			//$jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][c]['+config.org_field_name+']"]')
			//.dco_add_DVIC (this_field_structure);

		}

		//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');

		//config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]');
		config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name^="'+manip+'[' + html_ctnr + ']["]').filter('[name$="]['+config.org_field_name+']"]');
		config.edition.elem_in_edit_mode.addClass("ui-corner-all");

		if (isset_notempty_notnull (this_field_structure.frontend_access.maxlength))
			config.edition.elem_in_edit_mode.attr("maxlength", this_field_structure.frontend_access.maxlength);

		if (isset_notempty_notnull (this_field_structure.frontend_access.max_display_length))
			config.edition.elem_in_edit_mode.attr("size", this_field_structure.frontend_access.max_display_length);

		// ########################
		// Behaviors
		if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
		{
			config.edition.elem_in_edit_mode.focus();
			//config.edition.elem_in_edit_mode.selectRange(0,0); 	// set the cursor to the beginig of the input and with no selection
		}

		//is( ":focus" );
		config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
		config.edition.elem_in_edit_mode.on("focusout", config, dco_focusout_and_save_event);

		// ########################
		// add the client data validator
		config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
	}


	// ##############################################################################
	// with an HTML input textarea element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "textarea")
{

	//$jq1001('#' + config.display.container).append('<textarea name="' + config.org_field_name + '">' + config.edition.old_value_to_be_displayed + '</textarea>');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + config.org_field_name + '" />');
	//$jq1001('#' + config.display.container).append('<textarea name="'+manip+'[' + html_ctnr + '][fv][]">' + config.edition.old_value_to_be_displayed + '</textarea>');

	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	if (manip === "delupsert")
	{
		//$jq1001('#' + config.display.container)
		//.append('<textarea name="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']">' 
			//+ config.edition.old_value_to_be_displayed + '</textarea>');

			$jq1001('<textarea></textarea>')
			.attr('name', manip + '[' + html_ctnr + '][f][' + config.org_field_name + ']')
			.html(config.edition.old_value_to_be_displayed)
			.appendTo('#' + config.display.container);

			// condition on primary keys
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			//$jq1001.each(primary_keys, textarea_iterate_through_primkeys);
			//function textarea_iterate_through_primkeys (index, primkey)
			for (index in primary_keys)
			{
				var condition_name = primary_keys[index]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
				//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
				$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
			}
	}
	else if (manip === "select[where]")
	{
		var html_op = display_search_operator (config);
		manip = "select[where]";

		$jq1001('<textarea></textarea>')
		.attr('name', manip + '[' + html_ctnr + '][c][' + config.org_field_name + ']')
		.html(config.edition.old_value_to_be_displayed)
		.appendTo('#' + config.display.container);

		html_op.trigger("change");

		// WHERE Grouping info:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][name]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][parent]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
		.appendTo('#' + config.display.container);
	}

	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');


	config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + " textarea");
	config.edition.elem_in_edit_mode.addClass("ui-corner-all");

	if (isset_notempty_notnull (this_field_structure.frontend_access.maxlength))
	{
		config.edition.elem_in_edit_mode.attr("maxlength", this_field_structure.frontend_access.maxlength);
		//config.edition.elem_in_edit_mode.after('<div id="charStillPossible"></div>');
		//config.edition.elem_in_edit_mode.on("keyup", {maxlength: this_field_structure.frontend_access.maxlength, container: config.display.container}, dco_maxlength_of_a_textarea_event);
	}

	if (isset_notempty_notnull (this_field_structure.frontend_access.max_display_length))
		config.edition.elem_in_edit_mode.attr("rows", this_field_structure.frontend_access.max_display_length);

	// ########################
	// Behaviors
	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		config.edition.elem_in_edit_mode.focus();
		//config.edition.elem_in_edit_mode.selectRange(0,0); 	// set the cursor to the beginig of the input and with no selection
	}

	//is( ":focus" );
	config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
	config.edition.elem_in_edit_mode.on("focusout", config, dco_focusout_and_save_event);

	// ########################
	// add the client data validator
	config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
}


// ##############################################################################
// with a datepicker element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "datepicker")
{
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + config.org_field_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="text" name="'+manip+'[' + html_ctnr + '][fv][]" value="' + config.edition.old_value_to_be_displayed + '" />');
	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	if (manip === "delupsert")
	{
		//$jq1001('#' + config.display.container)
		//.append('<input type="text" name="'+manip+'[' + html_ctnr + '][f]['+
			//config.org_field_name+']" value="' + config.edition.old_value_to_be_displayed + '" />');

		$jq1001('<input type="text" />')
		.attr('name', manip + '[' + html_ctnr + '][f][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.set_unique_id_generator()
		.appendTo('#' + config.display.container);

		// condition on primary keys
		var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
		//$jq1001.each(primary_keys, text_iterate_through_primkeys);
		//function text_iterate_through_primkeys (index, primkey)
		for (index in primary_keys)
		{
			var condition_name = primary_keys[index]; //primkey;
			var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

			//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
			//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
			$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
		}

		//var datepicker_id = $jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]').attr('id');

		//console.log('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]');
		//console.log($jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]').attr('id'));
		//alert("Before datepicker creation");
		jQuery('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]')
		//.get(0)
		//$jq1001('#' + datepicker_id)
		.datepicker({
			dateFormat: "yy-mm-dd"
			// For menus
			, changeMonth: true
			, changeYear: true
			// For icon
			, showOn: "button"
			, buttonImage: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/templates/common/img/calendar.gif"
			, buttonImageOnly: true
		});
		//console.log($jq1001('#' + datepicker_id));
		//alert("After datepicker creation");
	}
	else if (manip === "select[where]")
	{
		var html_op = display_search_operator (config);
		manip = "select[where]";

		$jq1001('<input type="text" />')
		.attr('name', manip + '[' + html_ctnr + '][c][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.appendTo('#' + config.display.container);

		html_op.trigger("change");

		// add the client data validator
		//$jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][c]['+config.org_field_name+']"]')
		//.dco_add_DVIC (this_field_structure);

		// init the jqueryui datepicker
		jQuery('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][c]['+config.org_field_name+']"]')
		.datepicker({
			dateFormat: "yy-mm-dd"
			// For menus
			, changeMonth: true
			, changeYear: true
			// For icon
			, showOn: "button"
			, buttonImage: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/templates/common/img/calendar.gif"
			, buttonImageOnly: true
		});

		// WHERE Grouping info:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][name]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][parent]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
		.appendTo('#' + config.display.container);
	}

	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');

	//config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]');
	config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name^="'+manip+'[' + html_ctnr + ']["]').filter('[name$="]['+config.org_field_name+']"]');
	config.edition.elem_in_edit_mode.addClass("ui-corner-all");

	if (isset_notempty_notnull (this_field_structure.frontend_access.maxlength))
		config.edition.elem_in_edit_mode.attr("maxlength", this_field_structure.frontend_access.maxlength);

	if (isset_notempty_notnull (this_field_structure.frontend_access.max_display_length))
		config.edition.elem_in_edit_mode.attr("size", this_field_structure.frontend_access.max_display_length);

	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		config.edition.elem_in_edit_mode.focus();
		//config.edition.elem_in_edit_mode.selectRange(0,0); 	// set the cursor to the beginig of the input and with no selection
	}

	//is( ":focus" );
	config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
	//config.edition.elem_in_edit_mode.on("focusout", config, dco_focusout_and_save_event);		// it bugs with this

	// ########################
	// add the client data validator
	config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
}


// ##############################################################################
// with a HTML input select element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "select")
{
	// add select element and its options
	//$jq1001('#' + config.display.container).append('<select name="' + config.org_field_name + '"></select>');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + config.org_field_name + '" />');
	//$jq1001('#' + config.display.container).append('<select name="'+manip+'[' + html_ctnr + '][fv][]">' + config.edition.old_value_to_be_displayed + '</select>');

	//console.log("---------------------------");
	//console.log(config.display.container);
	//console.log(config);
	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	var html_op;
	var field_or_criteria;

	if (manip === "delupsert")
	{
		field_or_criteria = "f";
		//$jq1001('#' + config.display.container).append('<select name="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']" />');

		$jq1001('<select></select>')
		.attr('name', manip +'[' + html_ctnr + '][' + field_or_criteria + '][' + config.org_field_name + ']')
		.appendTo('#' + config.display.container);

		// condition on primary keys
		var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
		//$jq1001.each(primary_keys, iterate_through_primkeys);
		//function iterate_through_primkeys (index, primkey)
		for (index in primary_keys)
		{
			var condition_name = primary_keys[index]; //primkey;
			var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
			$jq1001('#' + config.display.container)
			.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
		}
	}
	else if (manip === "select[where]")
	{
		field_or_criteria = "c";
		html_op = display_search_operator (config);
		manip = "select[where]";

		//$jq1001('#' + config.display.container).append('<select name="'+manip+'[' + html_ctnr + '][c]['+config.org_field_name+']" />');

		$jq1001('<select></select>')
		.attr('name', manip +'[' + html_ctnr + '][' + field_or_criteria + '][' + config.org_field_name + ']')
		.appendTo('#' + config.display.container);

		// WHERE Grouping info:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][name]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][parent]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
		.appendTo('#' + config.display.container);
	}

	$jq1001.each(this_field_structure.frontend_access.valuelist, select_iterate_through_valuelist);
	function select_iterate_through_valuelist (db_stored_value, value_to_be_displayed)
	//for (db_stored_value in this_field_structure.frontend_access.valuelist)	// using this for loop adds problems: $family... javascript elements.
	{
		//console.log("db_stored_value: " + db_stored_value);
		var value_to_be_displayed = this_field_structure.frontend_access.valuelist[db_stored_value];
		var selected = "";
		//console.log("db_stored_value type: " + $jq1001.type(db_stored_value) + " - db_stored_value: " + db_stored_value);
		//console.log("config.edition.old_db_val type: " + $jq1001.type(config.edition.old_db_val) + " - config.edition.old_db_val: " + config.edition.old_db_val);
		//console.log(db_stored_value+" === "+config.edition.old_db_val);
		if (db_stored_value === config.edition.old_db_val.toString()) selected = 'selected="selected" ';
		//console.log("selected: " + selected);

		$jq1001('#' + config.display.container + " select")
		.filter('[name="' + manip +'[' + html_ctnr + '][' + field_or_criteria + '][' + config.org_field_name + ']' + '"]')
		.append('<option value="' + db_stored_value + '" ' + selected + '>' + value_to_be_displayed + '</option>');
	}

	if (isset_notempty_notnull(html_op))
	{
		html_op.trigger("change");
	}

	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');

	config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' select');

	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		config.edition.elem_in_edit_mode.focus();
	}
	config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
	config.edition.elem_in_edit_mode.on("change", config, dco_focusout_and_save_event);		// it bugs with this

	// ########################
	// add the client data validator
	config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
}


// ##############################################################################
// with an autocomplete element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "autocomplete")
{
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + config.org_field_name + '" />');
	//$jq1001('#' + config.display.container).append('<input type="text" name="'+manip+'[' + html_ctnr + '][fv][]" value="' + config.edition.old_value_to_be_displayed + '" />');
	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	if (manip === "delupsert")
	{
		//$jq1001('#' + config.display.container)
		//.append('<input type="text" name="'+manip+'[' + html_ctnr + '][f]['+
			//config.org_field_name+']" value="' + config.edition.old_value_to_be_displayed + '" />');

		// hidden field with db_store value:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][f][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.addClass("db_store")
		.appendTo('#' + config.display.container);

		// Menu autocomplete field:
		$jq1001('<input type="text" />')
		.attr('name', manip + '[' + html_ctnr + '][ac_box][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.mil_autocomplete({
			autoFocus: true
			, minLength: this_field_structure.frontend_access.autocomplete_vars.minlength	// number of char to input before starting autocomplete.
			//, source: window.location.protocol + "//" + window.location.hostname + "/1001_addon/library/datamalico/frontend_access/autocomplete/server.find_someone.ajax.php"
			//, source: this_field_structure.frontend_access.valuelist
			, source: function( request, response ) {
				//console.log ("source");
				//console.log (request); // {term: "fran"}
				$jq1001.ajax({
					dataType: "json"
					, url: this_field_structure.frontend_access.autocomplete_vars.source
					, data: request
					, success: function (data, textStatus, jqXHR) {
						//console.log ("ON_SUCCESS");
						response( data );
						//console.log (data);
						//if (data !== null) console.log (data.length);
						//else console.log("no proposition");
						//console.log (status);
					}
					, beforeSend: $jq1001.noop()
					, complete : $jq1001.noop()
				});
			}
			, select: function ( event, ui ) {
				//console.log("select");
				//console.log(ui.item);
				//console.log(ui);
				$jq1001(this).mil_autocomplete("option", "mil_ac_selected_value", ui.item.label);
				//$jq1001('#field_display').val(ui.item.label);
				$jq1001(this).siblings('.db_store').val(ui.item.db_store);
			}
			, change: function( event, ui ){
				//console.log("change");
				ac_box_force_to_select($jq1001(this))
			}
			, search: function( event, ui ) {
				//console.log("search");

				// reset the field as long as  it is not set by a select event:
				$jq1001(this).mil_autocomplete("option", "mil_ac_selected_value", "");
				$jq1001(this).siblings('.db_store').val("");
			}

			// ##############
			// Public mil_autocoplete special vars:
			, mil_ac_force_to_select: this_field_structure.frontend_access.autocomplete_vars.force_to_select	// forces the user to select a value from the list (or nothing) but not anything else of the list.
			, mil_ac_categories: this_field_structure.frontend_access.autocomplete_vars.categories	// Specifies if you want categories to be displayed.
			// Private mil_autocoplete special vars:
			, mil_ac_selected_value: ""	// force to select a vlaue from the the menu
		})
		//.on("focusout", ac_box_force_to_select)
		.appendTo('#' + config.display.container);

		// condition on primary keys
		var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
		//$jq1001.each(primary_keys, text_iterate_through_primkeys);
		//function text_iterate_through_primkeys (index, primkey)
		for (index in primary_keys)
		{
			var condition_name = primary_keys[index]; //primkey;
			var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

			//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
			//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
			$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
		}
	}
	else if (manip === "select[where]")
	{
		var html_op = display_search_operator (config);
		manip = "select[where]";

		// hidden field with db_store value:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][c][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.addClass("db_store")
		.appendTo('#' + config.display.container);

		// Menu autocomplete field:
		$jq1001('<input type="text" />')
		.attr('name', manip + '[' + html_ctnr + '][ac_box][' + config.org_field_name + ']')
		.attr('value', config.edition.old_value_to_be_displayed)
		.mil_autocomplete({
			autoFocus: true
			, minLength: this_field_structure.frontend_access.autocomplete_vars.minlength	// number of char to input before starting autocomplete.
			//, source: window.location.protocol + "//" + window.location.hostname + "/1001_addon/library/datamalico/frontend_access/autocomplete/server.find_someone.ajax.php"
			//, source: this_field_structure.frontend_access.valuelist
			, source: function( request, response ) {
				//console.log ("source");
				//console.log (request); // {term: "fran"}
				$jq1001.ajax({
					dataType: "json"
					, url: this_field_structure.frontend_access.autocomplete_vars.source
					, data: request
					, success: function (data, textStatus, jqXHR) {
						//console.log ("ON_SUCCESS");
						response( data );
						//console.log (data);
						//if (data !== null) console.log (data.length);
						//else console.log("no proposition");
						//console.log (status);
					}
					, beforeSend: $jq1001.noop()
					, complete : $jq1001.noop()
				});
			}
			, select: function ( event, ui ) {
				//console.log("select");
				//console.log(ui.item);
				//console.log(ui);
				$jq1001(this).mil_autocomplete("option", "mil_ac_selected_value", ui.item.label);
				//$jq1001('#field_display').val(ui.item.label);
				$jq1001(this).siblings('.db_store').val(ui.item.db_store);
			}
			, change: function( event, ui ){
				//console.log("change");
				ac_box_force_to_select($jq1001(this))
			}
			, search: function( event, ui ) {
				//console.log("search");

				// reset the field as long as  it is not set by a select event:
				$jq1001(this).mil_autocomplete("option", "mil_ac_selected_value", "");
				$jq1001(this).siblings('.db_store').val("");
			}

			// ##############
			// Public mil_autocoplete special vars:
			, mil_ac_force_to_select: this_field_structure.frontend_access.autocomplete_vars.force_to_select	// forces the user to select a value from the list (or nothing) but not anything else of the list.
			, mil_ac_categories: this_field_structure.frontend_access.autocomplete_vars.categories	// Specifies if you want categories to be displayed.
			// Private mil_autocoplete special vars:
			, mil_ac_selected_value: ""	// force to select a vlaue from the the menu
		})
		//.on("focusout", ac_box_force_to_select)
		.appendTo('#' + config.display.container);

		html_op.trigger("change");

		// WHERE Grouping info:
		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][name]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][parent]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
		.appendTo('#' + config.display.container);

		$jq1001('<input type="hidden" />')
		.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
		.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
		.appendTo('#' + config.display.container);

		// add the client data validator
		//$jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][c]['+config.org_field_name+']"]')
		//.dco_add_DVIC (this_field_structure);

	}

	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');

	//config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name|="'+manip+'[' + html_ctnr + '][f]['+config.org_field_name+']"]');
	config.edition.elem_in_edit_mode = $jq1001('#' + html_ctnr + ' input[name^="'+manip+'[' + html_ctnr + ']["]').filter('[name$="]['+config.org_field_name+']"]');
	config.edition.elem_in_edit_mode.addClass("ui-corner-all");

	if (isset_notempty_notnull (this_field_structure.frontend_access.maxlength))
		config.edition.elem_in_edit_mode.attr("maxlength", 9999);

	if (isset_notempty_notnull (this_field_structure.frontend_access.max_display_length))
		config.edition.elem_in_edit_mode.attr("size", this_field_structure.frontend_access.max_display_length);

	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		//config.edition.elem_in_edit_mode.focus();
		//config.edition.elem_in_edit_mode.selectRange(0,0); 	// set the cursor to the beginig of the input and with no selection
	}

	//is( ":focus" );
	//config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
	//config.edition.elem_in_edit_mode.on("focusout", config, dco_focusout_and_save_event); // Not possible with this autocomplete because, there is alread y another focusout event.

	// ########################
	// add the client data validator
	//config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
}


// ##############################################################################
// with an checkbox_multiselist element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "checkbox_multiselist")
{
	//console.log("checkbox_multiselist");
	// if global_save, then put a fields in order to delete all records related to the object_id before upserting rows input by this global_save
	var checked;

	$jq1001('#' + config.display.container).append('<input type="checkbox" name="inmulsel[' + html_ctnr + ']" value="1" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	if (manip === "delupsert")
	{
		//console.log("manip is delupsert");
		// if selected in interface then, order an insert or update
		//console.log("selected_in_multiselist_in_interface: " + config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface);
		if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface === true)
		{
			checked = true;
			$jq1001('#' + config.display.container + ' input[type="checkbox"]').attr('checked', true);

			// in order to insert, fields, and no condition
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				//$jq1001('#' + config.display.container)
				//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + condition_name + '" />');
				//$jq1001('#' + config.display.container)
				//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fv][]" value="' + condition_value + '" />');
				$jq1001('#' + config.display.container)
				.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][f]['+condition_name+']" value="' + condition_value + '" />');

				// update, so let's add conditions
				//console.log("selected_in_multiselist_in_db: " + config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);
				if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true)
				{
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
					$jq1001('#' + config.display.container)
					.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
				}
				else
					// insert, then, no conditions
				{
				}
			}
			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');
		}

		else
			// if not selected in interface then, order a delete (if already exists in db) or order nothing
		{
			checked = false;
			$jq1001('#' + config.display.container + ' input[type="checkbox"]').attr('checked', false);

			// in order to delete, conditions but no fields
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				// order a delete only if the record already exist in db
				//console.log("selected_in_multiselist_in_db: " + config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);
				if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true)
				{
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
					$jq1001('#' + config.display.container)
					.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
				}
			}
			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');
		}
	}
	else if (manip === "select[where]")	
	{
		//console.log("manip is select");

		// if selected in interface then add this as a criteria
		//console.log("selected_in_multiselist_in_interface: " + config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface);
		if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface === true)
		{
			//html_op = display_search_operator (config);
			manip = "select[where]";
			$jq1001('#' + config.display.container + ' input[type="checkbox"]').attr('checked', true);

			// in order to insert, fields, and no condition
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				$jq1001('<input type="hidden" />')
				.attr('name', manip + '[' + html_ctnr + '][c][' + condition_name + ']')
				.attr('value', condition_value)
				.appendTo('#' + config.display.container);					
			}

			// WHERE Grouping info:
			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][name]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][parent]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
			.appendTo('#' + config.display.container);
		}
	}

	config.edition.elem_in_edit_mode = $jq1001('#' + config.display.container + ' input[name="inmulsel[' + html_ctnr + ']"]');
	//config.edition.elem_in_edit_mode = $jq1001('#' + config.display.container + ' input[type="checkbox"]');

	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		config.edition.elem_in_edit_mode.focus();
	}
	config.edition.elem_in_edit_mode.on("change", config, dco_checkbox_multiselist_change);

	// ########################
	// add the client data validator
	config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);

	//console.log("selected_in_multiselist_in_db: " + config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db);

}


// ##############################################################################
// with an radio_singleselist element:
else if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "radio_singleselist")
{
	// if global_save, then put a fields in order to delete all records related to the object_id before upserting rows input by this global_save
	var checked;

	$jq1001('#' + config.display.container).append('<input type="radio" name="insinglesel[' + config.display.field_name + ']" value="'+config.display.row_num+'" />');
	//$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][tn]" value="' + config.org_table_name + '" />');
	$jq1001('#' + config.display.container).append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][t]" value="' + config.org_table_name + '" />');

	if (manip === "delupsert")
	{
		// if selected in interface then, order an insert or update
		if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface === true)
		{
			checked = true;
			$jq1001('#' + config.display.container + ' input[type="radio"]').attr('checked', true);

			// in order to insert, fields, and no condition
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				//$jq1001('#' + config.display.container)
				//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fn][]" value="' + condition_name + '" />');
				//$jq1001('#' + config.display.container)
				//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][fv][]" value="' + condition_value + '" />');
				$jq1001('#' + config.display.container)
				.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][f]['+condition_name+']" value="' + condition_value + '" />');

				// update, so let's add conditions
				if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true)
				{
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
					$jq1001('#' + config.display.container)
					.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
				}
				else
					// insert, then, no conditions
				{
				}
			}
			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');
		}

		else
			// if not selected in interface then, order a delete (if already exists in db) or order nothing
		{
			checked = false;
			$jq1001('#' + config.display.container + ' input[type="radio"]').attr('checked', false);

			// in order to delete, conditions but no fields
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				// order a delete only if the record already exist in db
				if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_db === true)
				{
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdn][]" value="' + condition_name + '" />');
					//$jq1001('#' + config.display.container)
					//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][cdv][]" value="' + condition_value + '" />');
					$jq1001('#' + config.display.container)
					.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][c]['+condition_name+']" value="' + condition_value + '" />');
				}
			}
			//$jq1001('#' + config.display.container)
			//.append('<input type="hidden" name="'+manip+'[' + html_ctnr + '][ctnr]" value="' + config.display.container + '" />');
		}
	}
	else if (manip === "select[where]")
	{
		//manip = "select[where]";
		// if selected in interface then add this as a criteria
		if (config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface === true)
		{
			//html_op = display_search_operator (config);

			$jq1001('#' + config.display.container + ' input[type="radio"]').attr('checked', true);

			// in order to insert, fields, and no condition
			var primary_keys = config.ajaxReturn.results.primary_keys[config.org_table_name];
			for (i in primary_keys)
			{
				var condition_name = primary_keys[i]; //primkey;
				var condition_value = config.ajaxReturn.results.records[config.display.row_num][condition_name];

				//console.log(condition_name);
				//console.log(config.display.row_num);
				//console.log(config.ajaxReturn.results.records[config.display.row_num]);
				//console.log(config.ajaxReturn.results.records[config.display.row_num][condition_name]);

				$jq1001('<input type="hidden" />')
				.attr('name', manip + '[' + html_ctnr + '][c][' + condition_name + ']')
				.attr('value', condition_value)
				.appendTo('#' + config.display.container);	
			}

			// WHERE Grouping info:
			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][name]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.name)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][parent]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.parent)
			.appendTo('#' + config.display.container);

			$jq1001('<input type="hidden" />')
			.attr('name', manip + '[' + html_ctnr + '][g][join_op]')
			.attr('value', this_field_structure.frontend_access.research_operators.cond_group.join_op)
			.appendTo('#' + config.display.container);
		}
	}

	config.edition.elem_in_edit_mode = $jq1001('#' + config.display.container + ' input[name="insinglesel[' + config.display.field_name + ']"]');
	//config.edition.elem_in_edit_mode = $jq1001('#' + config.display.container + ' input[type="radio"]');

	// ########################
	// Behaviors
	if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
	{
		config.edition.elem_in_edit_mode.focus();
	}
	config.edition.elem_in_edit_mode.on("change", config, dco_radio_singleselist_change);

	//console.log("dco_edition_event: ");
	//console.log(config.display);

	// ########################
	// add the client data validator
	config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
}
//alert($jq1001('#' + config.display.container).html());
}


/**
* Display the operator when the display_datagrid (display.manipulation === "research")
* @todo Each operator menu list should depend on the type of  the data, and also on the content.
*/
function display_search_operator (config)
{
	return display_search_operator_debug (config);
}
function display_search_operator_debug (config)
{
	//console.log("display_search_operator_debug");
	var this_field_structure = config.ajaxReturn.results.field_structure[config.display.field_name];
	//var table_name = config.ajaxReturn.results.field_structure[config.display.field_name].field_direct.orgtable;
	var html_ctnr = config.display.html_container;
	var manip = "select[where]";

	var html_operators;
	var operator_class;

	var display_operator = true;

	var operator_name = manip + '[' + html_ctnr + '][o][' + config.org_field_name + ']'; 

	//console.log(config.display.manipulation);
	//console.log(this_field_structure.frontend_access.research_operators.advanced + " - " + $jq1001.type(this_field_structure.frontend_access.research_operators.advanced));
	if (/*config.display.manipulation === "research" || */this_field_structure.frontend_access.research_operators.display.advanced === false )
	{    
		html_operators = $jq1001('#html_simple_operators').html();
		operator_class = "research_simple_operators";
	}    
	else if (/*config.display.manipulation === "research_advanced" || */this_field_structure.frontend_access.research_operators.display.advanced === true )
	{    
		html_operators = $jq1001('#html_advanced_operators').html();
		operator_class = "research_advanced_operators";
	}    

	// ##############################################
	// Do we display the operator ?
	// always display the operator, but if the field is text:
	if ( 
		// String fields
		new RegExp("^varchar", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^text", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^mediumtext", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^longtext", "gi").test( this_field_structure.show_columns.Type )
	)    
	{ 
		if (
			this_field_structure.frontend_access.research_operators.display.show === "default"
			|| this_field_structure.frontend_access.research_operators.display.show === false
		)
		{    
			display_operator = false;
		}    
		else if (this_field_structure.frontend_access.research_operators.display.show === true)
		{    
			display_operator = true;
		}    
	}
	else if ( 
		// Time fields
		new RegExp("^datetime", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^timestamp", "gi").test( this_field_structure.show_columns.Type )

		// Numeric fields
		|| new RegExp("^tinyint", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^smallint", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^mediumint", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^int", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^bigint", "gi").test( this_field_structure.show_columns.Type )
		|| new RegExp("^decimal", "gi").test( this_field_structure.show_columns.Type )
	)
	// always display the operator, but if the field is forced not to be shown:
	{ 
		if (
			this_field_structure.frontend_access.research_operators.display.show === "default"
			|| this_field_structure.frontend_access.research_operators.display.show === true
		)
		{    
			display_operator = true;
		}    
		else if (this_field_structure.frontend_access.research_operators.display.show === false)
		{    
			display_operator = false;
		}    
	}



	// ##############################################
	// Display the operator:
	if (display_operator === true)
	{
		// Operator choice: null, LIKE, =, <, >, <>, .. (between)
		$jq1001('<select></select>')
		.attr('name', operator_name)
		.append(html_operators)
		.addClass(operator_class)
		.change(operator_changed)
		.appendTo('#' + config.display.container);

		//console.log($jq1001('[name="' + manip + '[' + html_ctnr + '][o][' + config.org_field_name + ']' + '"]'));
		//console.log(this_field_structure.frontend_access.research_operators.default);
		//console.log("BEFORE: " + $jq1001('[name="' + manip + '[' + html_ctnr + '][o][' + config.org_field_name + ']' + '"]').val());

		$jq1001('[name="' + operator_name + '"]')
		.do_selection(this_field_structure.frontend_access.research_operators.default);

		for (index in this_field_structure.frontend_access.research_operators.forbid_op)
		{
			var value = this_field_structure.frontend_access.research_operators.forbid_op[index];
			$jq1001('[name="' + operator_name + '"]').find('[value="' + value + '"]').remove();
		}

		//console.log("AFTER: " + $jq1001('[name="' + manip + '[' + html_ctnr + '][o][' + config.org_field_name + ']' + '"]').val());
	}


	/*
	* This function is called when, for a research (with config.display.manipulation = "research"), an operator is changed.
	*/
	function operator_changed (event)
	{
		//console.log("operator_changed");
		//console.log(event);
		var j_operator = jQuery(event.target);
		var j_MIN_field = j_operator.next();
		var j_MIN_field_name = j_MIN_field.attr("name");
		var j_MAX_field_name = j_MIN_field_name.replace(/\]$/g,"_MAX\]");

		// if between operator, add the MAX value input field:
		if ($jq1001(event.target).val() === "betw")
		{
			if ($jq1001('[name="' + j_MAX_field_name + '"]').length === 0)
			{
				if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "datepicker")
				{
					// Neutralize the first datepicker before cloning:
					jQuery('[name="' + j_MIN_field_name + '"]').datepicker("destroy");
					//console.log("betw");


					// get today's date
					var d = new Date();
					var year = d.getFullYear();
					var month = d.getMonth() + 1; if (month < 10) month = "0"+month;
					var day = d.getDate(); if (day < 10) day = "0"+day;
					var today = year + "-" + month + "-" + day;


					// Create the 2nd datepicker:
					var clone_id = j_MIN_field.clone(false)
					.hide()
					.insertAfter(j_MIN_field)
					.show("slow")	//.slideDown("slow")
					.attr("name",j_MAX_field_name)
					.removeAttr( "id" )
					.set_unique_id_generator()
					.val(today)	// fix a bug with the jquery-ui-1.8.23
					.datepicker({
						dateFormat: "yy-mm-dd"
						// For menus
						, changeMonth: true
						, changeYear: true
						// For icon
						, showOn: "button"
						, buttonImage: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/templates/common/img/calendar.gif"
						, buttonImageOnly: true
					})
					.on("keydown", config, dco_on_keydown_on_edition_elem)
					.on("focusout", config, dco_focusout_and_save_event)
					// .dco_add_DVIC (this_field_structure);
					.attr("id");

					$jq1001("#" + clone_id).dco_add_DVIC (this_field_structure);

					//jQuery('[name="' + j_MIN_field_name + '"]').datepicker("destroy");



					// Restore the first datepicker after cloning:
					//j_MIN_field
					jQuery('[name="' + j_MIN_field_name + '"]')
					.datepicker({
						dateFormat: "yy-mm-dd"
						// For menus
						, changeMonth: true
						, changeYear: true
						// For icon
						, showOn: "button"
						, buttonImage: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/templates/common/img/calendar.gif"
						, buttonImageOnly: true
					});


					//is( ":focus" );
					//config.edition.elem_in_edit_mode.on("keydown", config, dco_on_keydown_on_edition_elem);
					//config.edition.elem_in_edit_mode.on("focusout", config, dco_focusout_and_save_event);

					// ########################
					// add the client data validator
					//config.edition.elem_in_edit_mode.dco_add_DVIC (this_field_structure);
				}
				else
				{
					j_MIN_field.clone(false)
					.hide()
					.insertAfter(j_MIN_field)
					.show("slow")	//.slideDown("slow")
					.attr("name",j_MAX_field_name);
				}
			}
		}
		else
		{
			if (this_field_structure.frontend_access.form_field_type.toString().toLowerCase() === "datepicker")
			{
				jQuery('[name="' + j_MAX_field_name + '"]').datepicker("destroy");
			}
			$jq1001('[name="' + j_MAX_field_name + '"]').remove();

			//$jq1001('[name="' + j_MAX_field_name + '"]')
			//.hide(function () 	// slideUp
			//{
				//console.log($jq1001(this));
				//	($jq1001(this).remove();	// This calback should be executed... but is not.
			//});
		}
	}

	return $jq1001('[name="' + operator_name + '"]');
}

$jq1001.fn.dco_add_DVIC = function (this_field_structure)
{
	//console.log("dco_add_DVIC");
	//console.log(this_field_structure.field_direct.name);
	if (isset_notempty_notnull (this_field_structure.frontend_access.data_validator.input.client))
	{
		//console.log("IF dco_add_DVIC");
		for (event_name in this_field_structure.frontend_access.data_validator.input.client)
		{
			//console.log("FOR dco_add_DVIC");

			// The only way to transform the string containing the function is the use of eval().
			// But if you put as handler, an eval statement with an anonymus function, it does not work, because, 
			// 	this is not supported by Chrome at least and answers :
			// 	Unexpected token (
				// 	eg: config.edition.elem_in_edit_mode.on(event_name, eval("function () {alert('from JS');}"));
				// The other way is to use a named function, and tadaaaaa !!! it finally works

				//console.log(this_field_structure.frontend_access.data_validator.input.client[event_name]);
				var handler_name = get_function_name (this_field_structure.frontend_access.data_validator.input.client[event_name]);
				eval(this_field_structure.frontend_access.data_validator.input.client[event_name]);	// declare the function
				$jq1001(this).on(event_name, eval(handler_name));

				//console.log(event_name + ", " + handler_name);
		}
	}
};

/**
* For any fieldtype 'autocomplete', this is the function related to the param frontend_access.autocomplete_vars.force_to_select.
* If ever the param is set to true, then the user must select a value from the menu (or nothing) but not any other value.
* Or if the para mis set to false, then the menu is only an input help but not a constraint.
*/
function ac_box_force_to_select (ac_box) {
	//console.log("focusout");
	//var ac_box = $jq1001(this);

	if (ac_box.mil_autocomplete("option", "mil_ac_force_to_select") === true)
	{
		var fts = setInterval( function() { // This force_to_select function is done afterward, so setInterval().
			//console.log("force_to_select");
			//console.log(ac_box);
			//console.log(ac_box.mil_autocomplete( "option" ));

			// Correction of a side effect of focusing out via a tab:
			var delete_lonely_tab_value = ac_box.val().replace(/\t/g, '');
			ac_box.val(delete_lonely_tab_value);

			// If you focus out without having choosed the value by select:
			if (
				ac_box.val() !== ""
				&& ac_box.mil_autocomplete("option", "mil_ac_selected_value") !== ac_box.val() 
			)
			{
				ac_box.val("");
				ac_box.focus();
				alert ("Stop ! Vous devez selectionner une valeur de la liste de saisie, ou vider le champ.");
			}

			clearInterval(fts); // Call this present function only once, so clearInterval()
			return;
		}, 100);
	}
}

// #######################################
// defining behaviors for edit cells

/**
* This is the function that allows a checkbox of a multiselist, to display or hide other criteria related to this item.
*/
function dco_checkbox_multiselist_change (event)
{
	//console.log("BEGIN:	dco_checkbox_multiselist_change");
	//event.stopPropagation(); // causes problems with another change listener, so comment it.
	//console.log("dco_focusout_and_save_event");
	//this_field_structure; config; config.edition.elem_in_edit_mode
	var config = event.data;
	//console.log(config.ajaxReturn.results.records);

	var joinTable_fields = new Array();
	var i = 0;
	for (fieldname in config.ajaxReturn.results.field_structure)
	{
		if (
			config.ajaxReturn.results.field_structure[fieldname].field_direct.orgtable === config.org_table_name
			&& config.display.field_name !== fieldname
		)
		{
			//console.log(fieldname + ", " + config.ajaxReturn.results.field_structure[fieldname].field_direct.orgname);
			joinTable_fields[i] = config.ajaxReturn.results.field_structure[fieldname].field_direct.orgname;
			i++;
		}
	}


	if($jq1001(this).is(':checked'))
	{
		// it is now selected
		config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface = true;

		// has checked, so upsert (insert acutally : field but no conditions)
		// if generic_atomic_save

		// activate and reveal fields that must be filled


		// Presence of 1 row in mil_d_demand_service ==> Primkeys : demand_histo_id:2 , serviceCommRule_id:7 ==> upsert avec champs sans conditions
		// service_quantity_100th : upsert avec champs service_quantity_100th et avec conditions demand_histo_id:2 , serviceCommRule_id:7
		// service_quality_id : upsert avec champs service_quality_id et avec conditions demand_histo_id:2 , serviceCommRule_id:7
	}
	else
	{
		// it is now deselected
		config.ajaxReturn.results.records[config.display.row_num].selected_in_multiselist_in_interface = false;

		// has uncheked, so delete from the reference listing
		// if generic_atomic_save

		// inactivate fields that must be filled
	}


	// ########################################
	// Work on this field itselft (the checkbox_multiselist) and then rerender it checked or not with all related good hidden fields
	dco_display_procedural ({
		ajaxReturn: config.ajaxReturn
		, display: {
			field_name : config.display.field_name
			, row_num: config.display.row_num
			, html_container: config.display.html_container
			, manipulation: config.display.manipulation
		}
	});

	// ########################################
	// Other fields related to the listing 
	for (index in joinTable_fields)
	{
		//$jq1001('#' + config.display.row_num + "_" + joinTable_field).html("Aller !!! On rempli !!!");
		var joinTable_field = joinTable_fields[index];
		//$jq1001('#' + config.display.row_num + "_" + joinTable_field).parent().attr('id');

		var grid_jelem = $jq1001('#'+config.display.html_container).parents('[id^="grid_"]');
		var joinTable_field_jelem = grid_jelem.find('[id^="' + config.display.row_num + "_" + joinTable_field + '"]');
		var html_container = joinTable_field_jelem.attr("id");

		//console.log(html_container);
		//$jq1001('#' + html_container).empty();
		dco_display_procedural ({
			ajaxReturn: config.ajaxReturn
			, display: {
				field_name : joinTable_field
				, row_num: config.display.row_num
				, html_container: html_container
				, manipulation: config.display.manipulation
			}
		});

		if($jq1001(this).is(':checked'))
		{
			$jq1001('#'+html_container).removeClass("will_be_deleted_from_DB");
		}
		else
		{
			$jq1001('#'+html_container).addClass("will_be_deleted_from_DB");
		}
	}
	//console.log("END:	dco_checkbox_multiselist_change");
}

function dco_radio_singleselist_change  (event)
{
	//console.log("BEGIN:	dco_radio_singleselist_change");
	//event.stopPropagation(); // causes problems with another change listener, so comment it.
	//console.log("dco_focusout_and_save_event");
	//this_field_structure; config; config.edition.elem_in_edit_mode
	var config = event.data;

	var radio_set_selector = $jq1001(this).attr("name");
	var form_selector = $jq1001( $jq1001(this).parents('form')[0] ).attr('id');	// get the id of the closest parent form
	var total_radio_set_selector;
	if (isset_notempty_notnull(form_selector)) total_radio_set_selector = '#' + form_selector + ' input[name="'+radio_set_selector+'"]';
	else total_radio_set_selector = 'input[name="'+radio_set_selector+'"]';

	//console.log("total_radio_set_selector: " + total_radio_set_selector);
	//console.log(config.ajaxReturn);

	$jq1001(this).off("change", dco_radio_singleselist_change);

	//console.log('Form: ' + form_selector);
	//console.log('input[name="'+radio_set_selector+'"]');
	$jq1001(total_radio_set_selector).each(function (index, Element)
	{
		//console.log($jq1001(this));
		//console.log($jq1001(this).is(':checked'));

		var joinTable_fields = new Array();
		var i = 0;
		for (fieldname in config.ajaxReturn.results.field_structure)
		{
			if (
				config.ajaxReturn.results.field_structure[fieldname].field_direct.orgtable === config.org_table_name
				&& config.display.field_name !== fieldname
			)
			{
				joinTable_fields[i] = config.ajaxReturn.results.field_structure[fieldname].field_direct.orgname;
				i++;
			}
		}

		//console.log("dco_radio_singleselist_change: ");
		//console.log(config.display);
		// ########################################
		// Work on this field itself (the checkbox_multiselist) and then rerender it checked or not with all related good hidden fields	
		var html_container = $jq1001(this).parent().parent().attr("id");
		var row_num = $jq1001('#'+html_container).parent().attr("id"); //config.display.row_num; //parseInt(index) + parseInt(1);
		row_num = row_num.replace("row_", "");

		//console.log(config.display.field_name + ", " + row_num + ", " + html_container);

		config.ajaxReturn.results.records[row_num].selected_in_multiselist_in_interface = false;

		if($jq1001(this).is(':checked'))
		{
			//console.log($jq1001(this).attr('name') + ': ' + $jq1001(this).attr('value'));
			// it is now selected
			config.ajaxReturn.results.records[row_num].selected_in_multiselist_in_interface = true;
		}

		dco_display_procedural ({
			ajaxReturn: config.ajaxReturn
			, display: {
				field_name : config.display.field_name
				, row_num: row_num
				, html_container: html_container
				, manipulation: config.display.manipulation
			}
		});


		// ########################################
		// Other fields on the same row, related to the listing:
		for (index in joinTable_fields)
		{
			//$jq1001('#' + row_num + "_" + joinTable_field).html("Aller !!! On rempli !!!");
			var joinTable_field = joinTable_fields[index];
			//$jq1001('#' + row_num + "_" + joinTable_field).parent().attr('id');

			var grid_jelem = $jq1001('#'+config.display.html_container).parents('[id^="grid_"]');
			var joinTable_field_jelem = grid_jelem.find('[id^="' + row_num + "_" + joinTable_field + '"]');
			var html_container = joinTable_field_jelem.attr("id");

			//$jq1001('#' + html_container).empty();
			dco_display_procedural ({
				ajaxReturn: config.ajaxReturn
				, display: {
					field_name : joinTable_field
					, row_num: row_num
					, html_container: html_container
					, manipulation: config.display.manipulation
				}
			});
		}
	});

	//console.log("END: 	dco_radio_singleselist_change");
}

function dco_on_keydown_on_edition_elem (event) 
{
	event.stopImmediatePropagation();
	//console.log("dco_on_keydown_on_edition_elem");
	//console.log(event);

	var config = event.data;

	if (event.which === 27)	// ESC
	{
		//console.log("keydown on ESC");
		dco_focusout_and_undo (event);

		/*if (config.edition.onevent == "mouseenter")
		{
			$jq1001('#' + config.display.container).trigger("mouseleave");
			}*/
	}
	if (event.which === 9)	// TAB
	{
		//console.log("keydown on TAB");
		$jq1001('#' + config.display.container).focus();
		//dco_focusout_and_undo (event);
	}
}

function dco_focusout_and_undo (event)
{
	//console.log("dco_focusout_and_undo");

	var config = event.data;
	//console.log(config);

	// repopulate with old value :
	if (config.edition.elem_in_edit_mode.tagname() === "select")
	{
		//console.log("select");
		config.edition.elem_in_edit_mode.do_selection(config.edition.old_db_val);
	}
	else
	{
		//console.log("NOT select");
		config.edition.elem_in_edit_mode.val(config.edition.old_db_val);
	}

	config.edition.elem_in_edit_mode.trigger("focusout");
}

/**
* This function must determinie if the new value is diffetrent of the new one, and if so, then, the new value is recorded depending on the setting:
* 	- action.save_mode (called at datamaliso_server_dbquery::select() or datamaliso_server_dbquery::select_empty()), which can be:
* 		- generic_atomic_save
* 		- custom_atomic_save
*/
function dco_focusout_and_save_event (event) 
{
	event.stopPropagation();
	//console.log("dco_focusout_and_save_event");
	//this_field_structure; config; config.edition.elem_in_edit_mode
	var config = event.data;
	var new_db_val = $jq1001(this).val();

	if (config.edition.old_db_val === null) config.edition.old_db_val = "NULL"; // fix

	//if (
		//	isset_notempty_notnull (config.ajaxReturn.action.save_mode)
		//	&& config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save"
	//)
	//{
		//	//console.log($jq1001(this).tagname() + "#" + $jq1001(this).id);
		//	var this_field_structure = config.ajaxReturn.results.field_structure[config.display.field_name];
		//	
		//	// if called from a form element:
		//	if (
			//		$jq1001(this).tagname() === "input"
			//		|| $jq1001(this).tagname() === "textarea"
			//		|| $jq1001(this).tagname() === "button"
			//		|| $jq1001(this).tagname() === "select"
			//		|| $jq1001(this).tagname() === "option"
			//		|| $jq1001(this).tagname() === "optgroup"
			//		|| $jq1001(this).tagname() === "fieldset"
			//		|| $jq1001(this).tagname() === "label"
	//	)
	//	{
		//		new_db_val = $jq1001(this).val();
		//	}
		//
		//	// if called by the mouseleave from the container
		//}
	//else
	//{
		//	//new_db_val = config.edition.old_db_val;
		//	new_db_val = $jq1001(this).val();
		//}

		//console.log("config.edition.old_db_val:" + config.edition.old_db_val.toString() + " - " + $jq1001.type(config.edition.old_db_val.toString()));
		//console.log("new_db_val:" + new_db_val + " - " + $jq1001.type(new_db_val));


		//console.log(config.edition.old_db_val + " vs " + new_db_val);
		if (config.edition.old_db_val.toString() !== new_db_val.toString())
		{
			if (isset_notempty_notnull (config.ajaxReturn.action.save_mode))
			{
				if (config.ajaxReturn.action.save_mode.toString().toLowerCase() === "generic_atomic_save")
				{
					//console.log("generic_atomic_save - ajax save and rebuild new");

					//var table_name = this_field_structure.field_direct.orgtable;
					//var field_name = this_field_structure.field_direct.orgname;

					//$jq1001(this).after('<form id="updateForm" accept-charset="utf-8"></form>');
					//var content = $jq1001('#' + config.display.container).html();

					$jq1001('#' + config.display.container).wrap('<form id="dco_ajax_atomic_update_form" accept-charset="utf-8"></form>');
					//$jq1001('#' + config.display.container).html('bonjour');

					config.ajaxReturn.results.records[config.display.row_num][config.display.field_name] = new_db_val;

					dco_ajax_atomic_update (config);
					//dco_remove_edition (config);

					/*dco_display_procedural ({field_name : config.display.field_name
						, row_num : config.display.row_num
						, ajaxReturn : config.ajaxReturn
						, html_container : config.display.html_container
						});*/
				}
				else if (
					config.ajaxReturn.action.save_mode.toString().toLowerCase() === "custom_atomic_save"
					&& isset_notempty_notnull (config.ajaxReturn.action.custom_atomic_save_fn)
				)
				{
					//console.log("custom_atomic_save - ajax save and rebuild new");
					$jq1001('#' + config.display.container).wrap('<form id="dco_ajax_atomic_update_form" accept-charset="utf-8"></form>');
					config.ajaxReturn.results.records[config.display.row_num][config.display.field_name] = new_db_val;
					eval(config.ajaxReturn.action.custom_atomic_save_fn + "(config)");
				}
			}
		}
		else 
		{
			//console.log("rebuild old");
			//console.log(config.display.manipulation);
			dco_display_procedural ({ajaxReturn: config.ajaxReturn
				, display: {
					field_name : config.display.field_name
					, row_num: config.display.row_num
					, html_container: config.display.html_container
					, manipulation: config.display.manipulation
				}
			});
			/*dco_display_procedural ({field_name : config.display.field_name
				, row_num : config.display.row_num
				, ajaxReturn : config.ajaxReturn
				, html_container : config.display.html_container
				});*/
		}
}


// textarea
function dco_maxlength_of_a_textarea_event (event)
{
	event.stopImmediatePropagation();

	var maxlength = event.data.maxlength;
	var container = event.data.container;
	//var length = 140;
	var myComment = $jq1001('#' + container + " textarea").val();
	var myTruncatedComment = myComment.substring(0, maxlength);
	$jq1001('#' + container + " textarea").val(myTruncatedComment);
}

function dco_remove_edition (config)
{
	$jq1001('#' + config.display.container).empty();
	//$jq1001('#' + config.display.container).off_click_or_dblclick ({event_namespace: "dco_edition_namespace"});
	$jq1001('#' + config.display.container).off_click_or_dblclick ();
	//$jq1001('#' + config.display.container).off ("dco_edition_namespace");
}

function dco_ajax_atomic_update (config)
{
	var div_debug_display = "div_debug_display";

	//console.log("launch_ajax()");
	mil_ajax ({
		form_id: "dco_ajax_atomic_update_form"
		, success: on_success									// execute as callback success
		, url: config.ajaxReturn.action.url	
		, method: "post"	
	});

	function on_success (data, textStatus, jqXHR)
	{
		var ajaxReturn = data;
		data = null;

		mil_ajax_debug_and_see_raw_server_results (ajaxReturn, textStatus, jqXHR, "div_debug_display"); return;
		$jq1001('#div_debug_display').html(""); mil_ajax_debug_and_see_object_server_results (ajaxReturn, textStatus, jqXHR, "div_debug_display");


		// ####################
		// WORK
		config.ajaxReturn.results.records[config.display.row_num][config.display.field_name] = ajaxReturn.update[config.display.field_name].update_api.update_set[0].metadata.value_just_inserted;
		dco_display_procedural ({ajaxReturn: config.ajaxReturn
			, display: {field_name : config.display.field_name, row_num: config.display.row_num, html_container: config.display.html_container}
		});
		/*dco_display_procedural ({field_name : config.display.field_name
			, row_num : config.display.row_num
			, ajaxReturn : config.ajaxReturn
			, html_container : config.display.html_container
			});*/

			// METATDATA INFO
			//var metadata = debugDisplayTable (ajaxReturn, "ajaxReturn");
			//metadata += print_r (ajaxReturn[0].metadata.sql_query);
			//$jq1001('#'+div_debug_display).html(metadata);


			// ACTION RESULTS
			if (ajaxReturn.metadata.returnCode == "API_HAS_BEEN_CALLED")
			{
				alert (ajaxReturn[0].update[config.display.field_name].update_api.update_set[0].metadata.returnMessage);
			}
	}
}

/*
* Example of call:
* @code
* //ajaxReturn.display_error_msg = "before";	// "before", "after"
* //dco_display_errors (ajaxReturn);
* @endcode
*/
function dco_display_errors (ajaxReturn)
{
	for (manipulation in ajaxReturn)
	{
		if (manipulation === "insert" || manipulation === "update" || manipulation === "custom_data_validation")
		{
			//console.log("ajaxReturn["+manipulation+"]: "+ajaxReturn[manipulation] + " - " +Object.keys(ajaxReturn[manipulation]).length);
			if (Object.keys(ajaxReturn[manipulation]).length > 0)	// avoid errors
			{
				for (html_container in ajaxReturn[manipulation])
				{
					//console.log("html_container:[" + html_container + "] - " + $jq1001.type(html_container));
					if ($jq1001.type(html_container) !== "undefined") // if the html_container is existing (may not exist if you have added some additional data at the server page level.)
					{
						if ( $jq1001("#" + html_container).length !== 0) // if the HTML element $jq1001("#" + html_container) is existing:
						{
							//$jq1001('#' + html_container + " .ui-state-highlight").remove();
							//$jq1001('#' + html_container + " .ui-state-error").remove();
							//console.log('REMOVE	#' + html_container + " .dco_error .remove()");
							//console.log($jq1001('#' + html_container).find(".datamalicoerror").remove());

							//console.log('#' + html_container + " .ui-state-highlight");
							//console.log('#' + html_container + " .ui-state-error");

							//delete the previous attention_msg if exists:
							$jq1001('#' + html_container + ' div.datamalicoerror').remove();

							// and display a new attention message if necessary:
							if ($jq1001.type(ajaxReturn[manipulation][html_container].metadata) !== "undefined")	// in case of no more errors
							{
								if (
									ajaxReturn[manipulation][html_container].metadata.horizontal_access === false
									|| ajaxReturn[manipulation][html_container].metadata.valid === false
								)
								{
									// delete the form dco_ajax_atomic_update_form if necessary (when any standard or custom atomic_update)
									var container = $jq1001('#'+html_container).find('#'+html_container+'_sub').attr('id');
									if ($jq1001('#'+container).parent().attr('id') === "dco_ajax_atomic_update_form") $jq1001('#'+container).unwrap();

									//var ctnr = ajaxReturn[manipulation][html_container].metadata.ctnr;
									var returnMessage = ajaxReturn[manipulation][html_container].metadata.returnMessage;

									if (!isset_notempty_notnull (ajaxReturn.display_error_msg)) ajaxReturn.display_error_msg = "before";

									//console.log(html_container + " - " + ajaxReturn.display_error_msg + " - " + returnMessage);
									//console.log('DISPLAY #' + html_container + " - " + ajaxReturn.display_error_msg + " - " + returnMessage);
									$jq1001('#' + html_container).add_attention_msg({
										type: "error"
										, msg: returnMessage
										, display_error_msg: ajaxReturn.display_error_msg
									});
								}
							}
						}
					}
				}
			}
		}
	}
}

/**
* @param params (mandatory) {object}
* 	- type: (optional, default is "highlight") {string} "highlight"|"error" Specify if it is a highlight or error field
* 	- msg: (mandatory) {string} Message to display.
* 	- display_error_msg: ()
*
* @return Returns the HTML code of the field.
*/
$jq1001.fn.add_attention_msg = function(params)
{	
	if (!isset_notempty_notnull(params.type)) params.type = "highlight";

	if (params.type.toLowerCase() === "highlight")
	{
		params.warning_word = "Please:";
		params.style_class = "ui-state-highlight"; // Do not call this params.class, because, it bugs on IE
		params.icon_class = "ui-icon-info";
	}
	else if (params.type.toLowerCase() === "error")
	{
		params.warning_word = "Hey!";
		params.style_class = "ui-state-error"; // Do not call this params.class, because, it bugs on IE
		params.icon_class = "ui-icon-alert";
	}

	var attention_msg = $jq1001('<div />')
	.addClass("datamalicoerror")
	.addClass('ui-corner-all')
	//.addClass(params.style_class)	// causes a problem
	.css("border", "1px solid #CD0A0A")	// Is the remedy to the above problem
	.css("background",  "#FFF")		// Is the remedy to the above problem
	.css("color", "#CD0A0A")		// Is the remedy to the above problem
	.css("padding", '0 .7em')
	.wrapInner(
		$jq1001('<p />')
		.wrapInner(
			$jq1001('<span />')
			//.addClass('ui-icon')	// causes a problem
			.addClass(params.icon_class)
			.css('float', 'left; margin-right: .3em')
			//.css("background", "#FFF")	// Is the remedy to the above problem
			.html('<strong>' + params.warning_word + '</strong> ' + params.msg)
		)
	)
	;

	if (params.display_error_msg === "after")
	{
		$jq1001(this).append(attention_msg);
	}
	else
	{
		$jq1001(this).prepend(attention_msg);
	}

	//console.log($jq1001(this));
	return $jq1001(this);
};


/**
* Creates a pagination for results sent via the ajax answer of the server.
* This function actually does 2 things:
* - Fill the content of elements having the params.display.pages_className with links towards pages of results.
* - Fill the element with the id params.display.report_ctnr with the params.ajaxReturn.metadata.returnMessage.
*
* This function is requires the jquery paging extension: infusion-jQuery-Paging-1121b46.zip taken 
* 	at http://www.xarg.org/2011/09/jquery-pagination-revised/
*
* 
*/
function  dco_paginate_procedural (params)
{
	// ############################
	// Params and config
	config = check_params(params);
	function check_params(params)
	{
		if (!isset_notempty_notnull (params.ajaxReturn)) return;
		if (!isset_notempty_notnull (params.display.pages_className)) 
		{
			mil_Exception ({adminMessage: "In dco_paginate_procedural() params.display.pages_className has a bad value"
				, errorLevel: "WARN"
			});
			return;
		}
		if (!isset_notempty_notnull (params.display.report_ctnr)) params.display.report_ctnr = "report";
		//if (!isset_notempty_notnull (params.display.pages_className)) params.display.pages_className = "pagination";
		//if (!isset_notempty_notnull (params.display.page_ctnr)) params.display.page_ctnr = "page_content";
		if (!isset_notempty_notnull (params.display.render_this_inner_page)) params.display.render_this_inner_page = function () {};
		if (!isset_notempty_notnull (params.display.require_another_page)) params.display.require_another_page = null;
		return params;
	}
	//$jq1001("#div_ajax_serverScript").html(debugDisplayTable (config, "config"));


	// ###########################
	// work

	//console.log(config);

	// report:
	$jq1001('#' + config.display.report_ctnr).empty();
	$jq1001('#' + config.display.report_ctnr).append(config.ajaxReturn.metadata.returnMessage);


	// create pagination:
	var class_selector = "." + config.display.pages_className;
	jQuery(class_selector).paging(config.ajaxReturn.metadata.pagination.nbRes
		, {page: config.ajaxReturn.metadata.pagination.page
			, perpage: config.ajaxReturn.metadata.pagination.perpage
			, onSelect: config.display.render_this_inner_page
			, require_another_page: config.display.require_another_page		// null --> will follow the natural link or any function_name in order to describe behavior on link click
			, format: '[ < . (qq -) nnncnnn (- pp) . > ]' //'[ < . (qq -) nnncnnn (- pp) . > ]' 	// '[ < . nnncnnn . > ]'
			, onFormat: onFormat
		});








		function onSelect_example (page, obj_this)
		{
			//console.log("onSelect_example");
			//$jq1001("#datatable").css('display', 'none');

			//console.log(obj_this);
			var num_of_first_elem_on_page = obj_this.slice[0] + 1;	// + 1 because the first elem of the slice array is 0
			var num_of_last_elem_on_page = obj_this.slice[1] + 1;	// + 1 because the first elem of the slice array is 0
			var html = "";

			for (i=num_of_first_elem_on_page; i<num_of_last_elem_on_page; i++)
			{
				//console.log("i:"+i);
				html += "Demonstration item " + i + "<br />";
			}

			$jq1001("#datatable").html(html);
			//$jq1001("#datatable").fadeIn("slow");
		}

		function onFormat (type)
		{
			var perpage_get_attr = "";
			//if (isset_notempty_notnull ($jq1001("#perpage").val())) perpage_get_attr = "&perpage=" + $jq1001("#perpage").val();
			//perpage_get_attr = pagination.perpage; //$jq1001("#page").val(page_num);
			perpage_get_attr = config.ajaxReturn.metadata.pagination.perpage; //$jq1001("#page").val(page_num);

			var request_uri = location.pathname; // "pagename-2-15" is rewritten by htaccess to pagename?page=2&perpage=15;
			var pattern = new RegExp("^(.+)-([0-9]+)-([0-9]+)$", "gi");
			//console.log("------------------------");
			request_uri = request_uri.replace(pattern, function(match, $1, $2, $3, offset, original)
			{
				//console.log(match);
				//console.log($1);
				//console.log($2);
				//console.log($3);
				//console.log(offset);
				//console.log(original);

				return $1;
			});

			//console.log("request_uri:" + request_uri);

			//console.log("onFormat:" + type);
			var pattern1 = /\{page\}/gi;
			var pattern2 = /\{perpage\}/gi;
			if (
				pattern1.exec(config.display.page_link_format) === null
				|| pattern2.exec(config.display.page_link_format) === null
			)
			{
				config.display.page_link_format = "?page=[page]&perpage=[perpage]";
			}

			switch (type)
			{
				case 'block': // n and c

					if (!this.active)
						return '<span class="disabled">' + this.value + '</span>';
					else if (this.value != this.page)
					{
						//return '<em><a href="' + request_uri + '-' + this.value + "-" + perpage_get_attr + '">' + this.value + '</a></em>';
						var href_page_link = get_href_page_link (config.display.page_link_format, this.value, perpage_get_attr);
						var total_link = '<em><a ' + href_page_link + '">' + this.value + '</a></em>';
						return total_link;
					}
					return '<span class="current">' + this.value + '</span>';

				case 'next': // >
					var text_btn = "Suiv &gt;";
					if (this.active)
					{
						//return '<a href="' + request_uri + '-' + this.value + "-" + perpage_get_attr + '" class="next">' + text_btn + '</a>';
						var href_page_link = get_href_page_link (config.display.page_link_format, this.value, perpage_get_attr);
						var total_link = '<em><a ' + href_page_link + ' class="next">' + text_btn + '</a></em>';
						return total_link;
					}
					return '<span class="disabled">' + text_btn + '</span>';

				case 'prev': // <

					var text_btn = "&lt; Pr&eacute;c";
					if (this.active)
					{
						//return '<a href="' + request_uri + '-' + this.value + "-" + perpage_get_attr + '" class="prev">' + text_btn + '</a>';
						var href_page_link = get_href_page_link (config.display.page_link_format, this.value, perpage_get_attr);
						var total_link = '<a ' + href_page_link + ' class="prev">' + text_btn + '</a>';
						return total_link;
					}
					return '<span class="disabled">' + text_btn + '</span>';

				case 'first': // [

					var text_btn = "&lt;&lt; D&eacute;but";
					if (this.active)
					{
						//return '<a href="' + request_uri + '-' + this.value + "-" + perpage_get_attr + '" class="first">' + text_btn + '</a>';
						var href_page_link = get_href_page_link (config.display.page_link_format, this.value, perpage_get_attr);
						var total_link = '<em><a ' + href_page_link + ' class="first">' + text_btn + '</a></em>';
						return total_link;
					}
					return '<span class="disabled">' + text_btn + '</span>';

			case 'last': // ]

				var text_btn = "Fin &gt;&gt;";
				if (this.active)
				{
					//return '<a href="' + request_uri + '-' + this.value + "-" + perpage_get_attr + '" class="last">' + text_btn + '</a>';
					var href_page_link = get_href_page_link (config.display.page_link_format, this.value, perpage_get_attr);
					var total_link = '<em><a ' + href_page_link + ' class="last">' + text_btn + '</a></em>';
					return total_link;
				}
				return '<span class="disabled">' + text_btn + '</span>';

			case "leap": // .

				if (this.active)
					return '<span class="empty_space"></span>';
				return "B";

			case 'fill': // -

				if (this.active)
					return "&nbsp;...&nbsp;";
				return "";

			case 'left':

				//var first_page = 1;
				//if (this.page <= first_page + 1)
					//	return "";
				//return '<em><a href="?page=' + this.value + '" class="next">' + this.value + '</a></em>';
				return "";

			case 'right':

				//var lastpage = this.pages;
				//if (this.page >= lastpage - 1)
					//	return "";
				//return '<em><a href="?page=' + this.value + '" class="next">' + this.value + '</a></em>';
				return "";

			}



			function get_href_page_link (format, page_num, perpage_num)
			{
				var local_page_link = format;
				var pattern1 = /\{page\}/gi;
				var pattern2 = /\{perpage\}/gi;
				local_page_link = local_page_link.replace (pattern1, page_num);
				local_page_link = local_page_link.replace (pattern2, perpage_num);

				//var href_page_link = 'href="' + request_uri + '-' + link_page + "-" + perpage_get_attr + 'titi"';
				var href_page_link = 'href="' + request_uri + local_page_link + '"';

				return href_page_link;
			}
		}
}


// }}}



