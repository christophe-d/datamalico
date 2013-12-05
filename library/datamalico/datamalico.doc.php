<?php
/** @mainpage Datamalico Main Concepts
 *
 * Blabla
 *
 * @tableofcontents
 *
 * @section introduction Introduction (backend-server, frontend-client, owning to RIA ajax and a config)
 * @subsection ajax_ria Ajax and RIA
 * @subsection simple_example A simple example:
 * See tutorials on http://datamalico.org
 *
 * @section advantages Advantages of datamalico (config once, then 13 methods to know. You keep control, because you manage ajax as usually, you can override any standard configuration... functions...
 * 	- Can present information stored in the DB like you want.
 * 	- Can input information in your database (INSERT, UPDATE, DELETE).
 * 	- Can pilot data security and integrity (security and validation).
 * 	- Can make easy researches.) A real direct bridge between HTML and JS and your Database.
 *
 *
 * @subsection only_13_functions Only 13 functions to know.
 *
 * @section install Installation
 * @subsection How to customize to your needs and database.
 *
 * @section main_classes Datamalico library, 3 main classes
 * You must make the difference between the datamalico classes:
 * - datamalico_server_dbquery, a php class for server side pages. (See datamalico_server_dbquery.lib.php)
 * - datamalico_server_ajax, a php class making the interface between javascript client side pages and php server-side pages. (See datamalico_server_ajax.lib.php) 
 * - datamalico_client (or its better alias datamalico), a javascript class for client side handling: (See datamalico.lib.js)
 *   	- display of data
 *   	- saving of data.
 *
 * 
 *
 * @section security Security
 * @subsection vertical_security Vertical security
 * @subsection horizontal_security Horizontal security
 * @subsection security_in_backend Security in the backend
 * @subsection security_in_frontend Security in the frontend
 * What is the backend access?
 * 	- This is the security defined for database tables. You secure according to 4 actions: INSERT, DELETE, UPDATE and SELECT.
 * 		So you can specify if a group or person has the right to process one of these 4 actions.
 *
 * What is the vertical security?
 * 	- This is named 'vertical', because for UPDATE and SELECT, you can grant accesses by column (so vertically) to users or groups of your organization.
 * 		(See can_vertically_access_field())
 * 	- Regarding 'INSERT' and 'DELETE' manipulations, you set it at the table level, if the action is possible. (See can_access_table())
 *
 * What is the horizontal security?
 * 	- Horizontal security allows you to allow, or not, 'UPDATE' and 'DELETE' SQL actions on particular rows (that's why this is called 'horizontal').
 * 	- For more information about horizontal security, please see datamalico_server_ajax::set_horizontal_access() and
 *  		FAKE_my_set_horizontal_access_FAKE() in datamalico_server_ajax.lib.php
 *
 * What about users and groups?
 * - Datamalico is very flexible thus data accesses given to users and groups must be defined by yourself, according to the specific needs you have. So, no matter
 * 	what chart organisation or security convention you use in your application, this is possible to adapt datamalico backend access to any applications, just by
 * 	adapting the 2 functions of this file: can_access_table() and can_vertically_access_field()
 *
 * What code do I have to check, adapt or write?
 * 	- You have to write or check 2 functions which check accesses. These functions will be then adapted to your organization chart or security conventions:
 * 		- can_access_table()
 * 		- can_vertically_access_field()
 *
 * @warning By default and security, all accesses are forbidden. If you don't specify TRUE as access (for any table in can_access_table() or field in can_vertically_access_field() ),
 * 	then the access is defined as forbidden. That means that, by default, if a table or field access is not populated with any access right, then this 
 * 	is FALSE (that is to say: forbidden).
 *
 *
 * @section pagination Pagination
 *
 *
 *
 * 
 * @section delupsert Upsert and Delupsert
 * The DELUPSERT concept in datamalico_server_ajax:
 * 	- There is no INSERT nor UPDATE commands, there are only UPSERT (which is the contraction of UPdate and inSERT).
 * 	- But when there is an UPSERT without conditions, then the first thing done is an empty insertion, 
 * 	getting the primary key and followed by updates on this new inserted record.
 *
 * 	- DELETE --> table_name (present), fields (absent), conditions (present)
 * 	- INSERT --> table_name (present), fields (present), conditions (absent or containing a value begining with TEMPINSERTID_)
 * 	- UPDATE --> table_name (present), fields (present), conditions (present)
 *		- For updates, the condition must be a fixed value (id) or the last field (of the array config['fields'])
 * 		- In other words, if you change first, a value, that is taken later as condition, then the condition is obsolete.
 * 		- eg: Pour data_registered_2_profession il est impossible de changer en mìme temps reg_id et profession_id car le 
 * 		premier update va se faire, mais pas le deuxiÿme. Parce que les conditions renvoyées par la page de saisie sont : 
 * 		reg_id = 'old_val_1' AND profession_id = 'old_val_2' mais aprÿs le premier update, l'une de ces deux valeures a changé, 
 * 		et donc le deuxiÿmÿe update fait avec cette condition ne fonctionnera pas.
 *
 * @warning ==> Conclusion : don't do changes on what is taken as conditions = (most of the time : primary keys)
 *
 *- UPSERT : 
 * 	- There is no INSERT nor UPDATE commands, there are only UPSERT (which is the contraction of UPdate and inSERT).
 * 	- But when there is an UPSERT without conditions, then the first thing done is an empty insertion, 
 * 	getting the primary key and followed by updates on this new inserted record.
 *
 * 	- INSERT : upsert WITHOUT conditions
 * 	- UPDATE : upsert WITH conditions. For updates, the condition must be a fixed value (id) or the last field (of the array config['fields'])
 * 		- In other words, if you change first, a value, that is taken later as condition, then the condition is obsolete.
 * 		- eg: Pour mil_d_registered_2_profession il est impossible de changer en mìme temps reg_id et profession_id car le 
 * 		premier update va se faire, mais pas le deuxiÿme. Parce que les conditions renvoyées par la page de saisie sont : 
 * 		reg_id = 'old_val_1' AND profession_id = 'old_val_2' mais aprÿs le premier update, l'une de ces deux valeures a changé, 
 * 		et donc le deuxiÿmÿe update fait avec cette condition ne fonctionnera pas.
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
 * 	- For more information about security, have a look on backend_access.conf.php and serch on the word 'security'.
 *
 *
 * @subsection atomic Atomic action
 *
 * 
 * @section multi-selection-list Multi selection lists:
 * Explain better entity table, list table, join table...
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
 * @section mil_library Relation with the mil_ help library (general functions, lang, separate html, php, js).
 *
 * @subsection mil_translation Translation
 * @subsection mil_params Parameters
 * Pagination
 *
 */
?>
