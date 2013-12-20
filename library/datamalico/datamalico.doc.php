<?php
/** @mainpage Datamalico Main Concepts
 *
 * @tableofcontents
 *
 * Don't miss our presentation website: http://datamalico.org/
 *
 *
 *
 *
 * @section introduction Introduction (backend-server, frontend-client, owning to RIA ajax and a config)
 *
 * @subsection ajax_ria Ajax and RIA
 * In order to create RIA (Rich Internet Applications) Datamalico is fully compatbible with the AJAX web use. You can use the AJAX method you want (jQuery, prototype, mootools, YUI, ExtJS...)
 * 
 * @subsection simple_example A simple example:
 * See tutorials on http://datamalico.org/tutorials/73-tuto-1-dreamy-db-data-display
 *
 *
 *
 *
 *
 *
 * @section advantages Advantages of datamalico
 * 	- You keep your use of the web coding: AJAX, CMS...
 * 	- Learning curve is fast thantks to only several methods to learn. (See http://datamalico.org/home#minimum-method-count)
 * 	- Display of any data of your Database is very easy (one data, or a grid of data or multiple pages of data). (See http://datamalico.org/tutorials/73-tuto-1-dreamy-db-data-display)
 * 	- INSERT, UPDATE or DELTE are commanded by only form structure called 'delupsert'. See:
 * 		- http://datamalico.org/tutorials/78-tuto-3-dreamy-data-insertion-change-delete
 * 		- \link datamalico_server_dbquery::select link text ... \endlink
 * 		- http://datamalico.org/1001_addon/documentation/datamalico/html/index.html#delupsert
 *	- Security is controled on the server side, totally driven by your own configuration and function override.
 *	- Data validation, and data integrity, on form insert or modification is highly configurable and can be on the client or server side.
 *	- Once the main configuration is done, the remaining is automatic or you can still override behaviors.
 *	- Research form and result pages are highly configurable and easy to do.
 *	- This is a library not a software, so it can be used for any purpose and any business.
 *	-- > A real direct bridge between HTML and JS and your Database.
 *
 * @section install Installation
 * See the tutorial: http://datamalico.org/tutorials/76-tuto-0-datamalico-installation
 *
 *
 * @section main_classes Datamalico library, 3 main classes
 * You must make the difference between the datamalico classes:
 * - datamalico (this is actually the alias of datamalico_client), a javascript class for the client side handling: (See datamalico.lib.js). This is usefull for the
 *   	- display of data
 *   	- saving of data.
 * - datamalico_server_dbquery: This is the core class, the closest to the database, a php class for server side pages. (See datamalico_server_dbquery.lib.php)
 * - datamalico_server_ajax: This is a php class making the interface between javascript client side pages and php server-side pages. (See datamalico_server_ajax.lib.php) 
 * 
 *
 *
 *
 *
 *
 *
 *
 * @section security Security
 * @subsection security_in_backend What is the backend access?
 * 	- This is the access of your datasource in the backend, and thus the security defined for your database tables.
 * 	- You secure according to these actions:
 * 		- INSERT
 * 		- DELETE
 * 		- UPDATE
 * 		- SELECT
 * 			- you manage the security of the elements returned in the SELECT clause
 * 			- and you manage the security of the elements in the WHERE clause too.
 * 			- so that no data can be returned to the user unless you authorize it.
 * 		- You can also specify if a group or person has the right to process one of these above actions.
 *
 * @subsection vertical_security What is vertical security
 * 	Datamalico uses a vertical security.
 * 	- This is named 'vertical', because for UPDATE and SELECT, you can grant accesses by column (so vertically) to users or groups of your organization.
 * 		(See the custom function can_vertically_access_field())
 * 	- Regarding 'INSERT' and 'DELETE' manipulations, you set at the table level, if the action is possible. (See can_access_table()).
 * 		Thus for INSERT and DELETE the vertical security doesn't exist.
 *
 * @subsection horizontal_security What is horizontal security
 * 	Datamalico uses an horizontal security.
 * 	- Horizontal security allows you to grant, or not, 'UPDATE' and 'DELETE' SQL actions on particular rows (that's why this is called 'horizontal').
 * 	- For more information about horizontal security, please see the method datamalico_server_ajax::set_horizontal_access() and
 *  		FAKE_my_set_horizontal_access_FAKE() in datamalico_server_ajax.lib.php
 *
 * @subsection users_groups_security What about users and groups?
 * - Datamalico is very flexible thus it can take place in any of the CMS or web application you use.
 * 	<strong>Actually, data accesses given to users and groups must be defined by yourself</strong>, according to the specific needs you have. So, no matter
 * 	what chart organisation or security convention you use in your application, this is possible to adapt datamalico backend access to any applications, just by
 * 	adapting the 2 functions of this file: can_access_table() and can_vertically_access_field()
 *
 * @subsection your_custom_security What code do you have to check, adapt or write, in order to optimize your security?
 * 	- You have to define 2 functions which check accesses. These functions will be then adapted to your organization chart or security conventions:
 * 		- can_access_table()
 * 		- can_vertically_access_field()
 *	- You also need to check the $GLOBALS['security']['backend_access'] structure.
 *	- Every security elements are in backend_access.conf.php 
 *
 * @warning By default and security, all accesses are forbidden. If you don't specify TRUE as access (for any table in can_access_table() or field in can_vertically_access_field() ),
 * 	then the access is defined as forbidden. That means that, by default, if a table or field access is not populated with any access right, then this 
 * 	is FALSE (that is to say: forbidden).
 *
 *
 * @section pagination Pagination
 * Very handy in datamalico, the pagination is taken in charge. No matter the number of records you want to display. For more information see:
 * 	- the tutorial http://datamalico.org/tutorials/73-tuto-1-dreamy-db-data-display using the paginate() method.
 * 	- the class pagination.
 * 	- it uses the jquery paging extension: infusion-jQuery-Paging-1121b46.zip taken at http://www.xarg.org/2011/09/jquery-pagination-revised/
 *
 *
 *
 *
 *
 *
 *
 * 
 * @section delupsert Upsert and Delupsert
 * 	- in the datamalico_server_dbquery, the method upsert() allows you in one method to INSERT or UPDATE depending on the case.
 * 		(This is the contraction of UPdate and inSERT. You may know that in some database languages or other application).
 * 	- in the datamalico_server_ajax, the method delupsert() allows you to execute INSERT, UPDATE and DELETE actions depending on the form parameters received by the your PHP server page.
 *
 * The DELUPSERT concept in datamalico_server_ajax:
 *
 * In SQL statements, you can notice that components are present or absent as follow:
 * |	        	|	Table Name	|	Fields	|	Conditions	|	Example							|
 * |	---:		|	:---:		|	:---:	|	:---:		|	:----							|
 * |	INSERT signing	|	Yes		|	Yes	|	No		|	INSERT INTO table (id, name) VALUES ('',  'test name');	|
 * |	UPDATE siging	|	Yes		|	Yes	|	Yes		|	UPDATE table SET name = 'new value' WHERE id = 2;	|
 * |	DELETE signing	|	Yes		|	No	|	Yes		|	DELETE FROM table WHERE id = 8				|
 *
 * Then it is easy to specify by parameters the action you want to operate on the datasource: (and these parameters can be sent via a web form)
 * |                            |       Table Name      |       Fields  |       Conditions						|       Action done by delupsert() | 
 * | -----------------:         | :----:                |       :----:  |       :----:							|       :----:  |
 * |    delupsert elements      |       Yes             |       Yes     |       No or containing a value begining with TEMPINSERTID_	| 	INSERT  |
 * |    delupsert elements      |       Yes             |       Yes     |       Yes							|       UPDATE  |
 * |    delupsert elements      |       Yes             |       No      |       Yes							|       DELETE  |
 *
 * The UPSERT concept in datamalico_server_dbquery: 
 * 	- There is no INSERT nor UPDATE commands, there are only UPSERT (which is the contraction of UPdate and inSERT).
 * 	- But when there is an UPSERT without conditions, then the first thing done is an empty insertion, 
 * 	getting the primary key and followed by updates on this new inserted record.
 *
 * 	- INSERT : upsert WITHOUT conditions
 * 	- UPDATE : upsert WITH conditions.
 *
 * @warning ==> Conclusion : don't do changes on what is taken as conditions = (most o fthe time : primary keys)
 *
 *
 * delupsert means that you can do any of the 3 SQL command: INSERT, UPDATE or DELETE.
 * How to specify which one?
 * - For an INSERT: do not specify any [c] (conditions) paramater.
 * - For a DELETE: do not specify any [f] (fields) paramater.
 * - For an UPDATE: you must specify both: [f] (fields) AND [c] (conditions).
 * - For the three of them, you must absolutelly specify the [t] (table name) to indicate to the server what table must be impacted.
 * @remark Security is taken in charge via set_horizontal_access() (and its param my_set_horizontal_access (See FAKE_my_set_horizontal_access_FAKE().)
 * 	- For more information about security, have a look on backend_access.conf.php and search on the word 'security'.
 *
 *
 * @subsection atomic Atomic action
 * The datamalico_server_dbquery::upsert() method actually operates atomic actions. In order to respect the security definied in backend_access.conf.php, each UPDATE on a row is done field by field.
 *
 * 
 * @section multi-selection-list Multi selection lists:
 * Explain better entity table, list table, join table...
 *
 *
 *
 *
 *
 * @section good2know Good to know:
 *
 * @subsection Special thing in the database:
 *
 * @subsubsection bools Booleans (datamalico_server_dbquery.lib.php)
 * @attention
 * Remark on BOOLEANS into the datamalico system:
 * Boolean values stored in the DB, such as my_table.enabled... is stored as follow: this is a mysql tinyint(1)
 * 	- 0: is often associated to "Please choose a value"
 * 	- 1: is true
 * 	- 2: is false
 * When you retreive a boolean value with a query, most of the time this value is interpreted in PHP as a string. Thus, if you do:
 * @code
 * $sql = "SELECT mil_d_registered.enabled FROM mil_d_registered WHERE mil_d_registered.reg_id = '$reg_id'";
 * @endcode
 * ... then you must cast the result into integer in order to check if it's true or false:
 * @code
 * if ((int) $query['enabled'] === 1) $enabled = true;
 * @endcode
 *
 * @subsubsection Special fields to add to list tables in the multiselist context.
 * Add enabled and sort_index (See datamalico_server_dbquery::select_multiselist() ).
 *
 * @subsection mil_features Nices features belonging to the mil_ help library.
 *
 *
 *
 *
 *
 *
 * @section mil_library Relation with the mil_ help library (general functions, lang, separate html, php, js).
 *
 * @subsection mil_translation Translation
 * @subsection mil_params Parameters
 * Pagination
 *
 * @section configuration Configuration
 * @subsection config-files Congiguration files
 * Here are all the configuration files:
 * 	- library/datamalico/backend_access/backend_access.conf.php
 * 	- library/datamalico/data_validator/data_validator.conf.php
 * 	- library/datamalico/frontend_access/frontend_access.conf.php
 * 	- library/datamalico/relationship/relationship.conf.php
 * 	- library/datamalico/datamalico.conf.php
 *
 * All these files are loaded in library/datamalico/datamalico_server_dbquery.lib.php
 *
 *
 *
 *
 *
 *
 * @section dependencies Dependencies
 * @subsection mil_-dependency mil_ help library (embedded)
 * Datamalico uses an embedded library called "mil_". This library is separated of datamalico in order to keep datamalico easily readable.
 *
 * Anyway, just keep the mil_ folder (provided with datamalico), and this will be transparent for you.
 *
 * See the official documentation of the mil_ help library.
 *
 * @subsection jquery-dependency jQuery (external)
 * In its beta earlier version, datamalico uses the jQuery library.
 *
 * Is it relevant to you? Would you like to remove this dependency, feel free to open a request and to come and contribute by coding.
 *
 * See the official jQuery website.
 *
 * @subsubsection jquery-version Important remark regarding the jQuery version
 * You may have noticed that datamalico uses jQuery not only with the $ sign, but with the $jq1001 variable (jq = jquery, 1001 = datamalico logo.)
 * 
 * This $jq1001 variable is defined and added in library/mil_/mil_.conf.php via $GLOBALS['config_ini']['JS']['link_to_functional_JS_libs_publicsite']
 *
 * This is to make no conflict with any other jquery version you may load in your HTML template.
 *
 * @warning the jQuery-UI don't still use the $ sign or th jQuery variable in order to use jQuery.
 *
 * 
 *
 * @todo
 * 	- Optimize code.
 * 	- Clean the code in order to make a lightweight version.
 * 	- Make a minimized version.
 * 	- Make clearer what version of jQuery to use with the jQuery-UI
 * 		- jQuery UI uses:
 * 			- datepicker
 * 			- button
 * 			- accordion
 */
?>
