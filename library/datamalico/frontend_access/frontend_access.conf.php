<?php
/** 
 * @file
 * CUSTOM FILE: File that you have to customize !!!
 *
 * File where to store the general frontend_access definition.
 *
 * What is $frontend_access? 
 *
 * This is a list of a default configuration for any field of your database, that can be displayed into a web page.
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2012
 *
 * Theses settings are default settings you define for the frontend_access of your datamodel.
 *
 * You must know that these settings have 
 * 	- defaults set in this file as the variable: $GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS'] (check these defaults for your own purposes)
 * 	- can have generic values for all your application set as $GLOBALS['security']['frontend_access']['mil_d_registered'][{tablename}] (it's up to you)
 * 	- can be overrided by custom values in the configuration of the call of datamalico_server_dbquery methods: 
 * 		select(), select_emtpy(), select_multiselist() (See the datamalico_server_dbquery::select() to learn more about it)
 *
 * Overriding:
 * 	- custom values ($frontend_access param sent as config argument of a method such as 
 * 		- datamalico_server_dbquery::select()
 * 		- datamalico_server_dbquery::select_empty()
 * 		- datamalico_server_dbquery::select_multiselist()
 * 		overrides
 * 	- ( data_validator.conf.php only for DVIC and DVO (see data_validator.conf.php) ) overrides
 * 	- values in frontend_access.conf.php overrides (CAUTION, no data_validator must be defined in frontend_access.conf.php, use data_validator.conf.php instead)
 * 	- DEFAULT_FRONTEND_SETTINGS
 *
 * How to set frontend_access?
 * - $GLOBALS['security']['frontend_access']['mil_d_registered'][{tablename}]: (optional) {associative array}
 * 	- {field_name or alias of a field in the select clause of the sql query}: (optional) {associative array}
 * 		- field_label: (optional, default is null) {string} is the label you wish. ex: $GLOBALS['mil_lang']['label_translation']
 * 			- TIP: make a variable $mil_lang_common['mytable.myfield'] = "My label"; in library/datamalico/lang/{mytongue}.lang.php
 * 		- accesses: (optional, see each sub-param for defaults) {associative array} Frontend accesses are definied here
 * 			- rights: (optional default is 'read') {string} According to this right, the frontend can have a certain behavior:
 * 				(Keep in mind that fields set as not accessible in the backend_access.conf.php are not returned at all by the datamalico_server_dbquery::select() method.)
 * 				- 'read' (default) In this case, the field is not editable
 * 				- 'write' In this case, the field is editable
 * 				- 'hidden' In this case the field is hidden (accessible only through js scripting and for the api needs)
 * 			- behavior: (optional, default is 'onready') {string} Only if write access
 * 				- 'onready' (default) Means that the field is directly accessible in edit mode
 * 					'onready' is the default setting, and also a forced setting if:
 * 					- forced setting if: form_field_type is 'checkbox_multiselist'|'radio_singleselist'
 * 				- 'onmouseenter' Means that the field is editable onmouseover
 * 				- 'onclick' Means that the field is editable onclick
 * 				- 'ondblclick' Means that the field is editable ondblclick
 * 		- form_field_type: (optional, default is 'text') {string} You can choose here what aspect to give to the editable field
 * 			- 'text' (default) Will be an HTML input type'text'
 * 			- 'textarea' Will be an HTML textarea
 * 			- 'wysiwyg' NOT IMPLEMENTED YET. A WYSIWYG HTML editor area.
 * 			- 'datepicker' If you use jqueryui, then you can use this in order to easily input dates.
 * 			- 'select' Use it for single selection list. Will be an HTML select and option. If you use it, then you must also use the 
 * 				following parameters: valuelist and research_operators. You must add a 'valuelist' (following param) with 'select'.
 *			- 'autocomplete' A menu made of a div or an autocomplete, good for custom menu.
 * 			- 'custom_menu' NOT IMPLEMENTED YET. A menu made of a div or an autocomplete, good for custom menu.
 * 			- 'checkbox_multiselist' Use it for multiple selection list. Will be an intelligent combination of HTML checkboxes and other fields
 * 				- @b Attention! We advise you to set this "checkbox_multiselist" to the jointable.entity_id in order to display the checkbox.
 * 					- eg: set it to the field starwars_data_character2attribute.char_id
 * 			- 'radio_singleselist' Use it for a single selection list (radio buttons). Will be an intelligent combination of HTML radio buttons
 * 				and other fields
 * 				- @b Attention! We advise you to set this "radio_singleselist" to the jointable.entity_id in order to display the radiobutton.
 * 					- eg: set it to the field starwars_data_character2attribute.char_id
 * 			- 'helptext' NOT IMPLEMENTED YET. 
 * 				- aspect: (optional, default is pophelp) {string} Aspect the help text must have. You can choose among:
 * 					- 'pophelp' (default) A small popup text opening when clicking on the helpbutton.
 * 				- text: (optional, default is "") {html string} A help text to display in order to inform the user of what is this field.
 * 				- helpbutton:
 * 					- img: Path to the image
 * 					- opening:
 * 						- 'onready'
 * 						- 'onmouseenter'
 * 						- 'onclick' (default)
 * 						- 'ondblclick'
 * 		- valuelist: (optional except if the form_field_type is 'select' or 'autocomplete', default is an empty array) {numerical array} Is the value list to be displayed
 * 			- For a 'select' form_field_type:
 * 				- For example a Person (table) has a field born_country refering to the country_id of the config table : country
 * 				Then when displaying the content of the field born_country, you will see the country_name (stored in the country table) instead of the id
 * 				@code
 * 				$valuelist['db_stored_value'] => value_to_be_displayed
 * 				@endcode
 * 				- TIP: We advise you to use valuelist to generate this numerical array through get_the_valuelist() in library/datamalico/datamalico_server_dbquery.lib.php.
 * 				@code
 * 				get_the_valuelist ("SELECT country_id, label FROM  `Country` WHERE enabled = 1 ORDER BY sort_index");
 * 				@endcode
 * 			- For a 'autocomplete' form_field_type:
 * 				- This must be the same as if it would be a select, because, then, fields in read access are filled anyway.
 * 		- autocomplete_vars: {associative array} (optional, only if 'form_field_type' = "autocomplete")
 * 			- source: {string} (mandatory) Is the source of data: either a javascript object or a JSON string or even a url returning such an object.
 * 				- Anyway, this source must be such a structure (beginning by 0n not 1):
 * 					- 0:
 * 						- label: My first label
 * 						- db_store: 18
 * 						(- category: America)
 *					- 1:
 * 						- label: My second label
 * 						- db_store: 20
 *						(- category: America)
 *					- 2:
 * 						- label: My thrid label
 * 						- db_store: 23
 *						(- category: Europe)
 * 			- minlength: {int} (optional, default is 3) Is the number of chars that the field must contain before beginning the ajax research.
 * 			- force_to_select: {bool} (optional, default is true) Specifies if the user must select a value from the menu (or nothing) but not any other value or
 * 				if the menu is only an input help but not a constraint.
 *  			- categories: {bool} (optional, default is false) Specifies if categories must be written in the menu. In this case, your json array returned by the server
 *  				must contain a column called 'category'.
 * 		- maxlength: (optional, default will be the $field_structure[$field_name]['field_direct']['length']) 
 * 			{string or integer} Maxlength of the field itself in the db so that this value prevent more chars to be typed 
 * 			during edition.
 * 			- Use it if you use as form_field_type "text" or "textarea"
 * 		- max_display_length: (optional, default is null) {string or integer}
 * 			- Use it if you use as form_field_type "text" or "textarea"
 * 			- Equals to size of an input text field or to rows of textarea
 * 			- Tip: prefer using css style in order to visually size this.	
 *		- data_validator: {associative array} (optional)
 * 			- input: {associative array} (optional)
 * 				- client: {associative array} (optional)
 * 					- keypress: {string} (optional, default is null) Javascript function to be used as DVIC, for this field (see data_validator.conf.php).
 * 						Eg: 'function DVIC_tablename_fieldname_keyup () { event.stopPropagation(); console.log ($(this).val()); return $(this); }'
 * 			- output: {string} (optional, default is null) Name of the PHP function to use as DVO, for this field (see data_validator.conf.php).
 * 				Eg: "DVO_any_custom_global_function_name"
 * 		- research_operators: (optional, see each sub-param for defaults) {associative array} Define operators for researches. Regarding the implementation
 * 			of operator select menus, see the datamalico.lib.js file. Your HTML page must contain a select element with the id: 'html_simple_operators'
 * 			and another one, with the id: 'html_advanced_operators'. These select elements must be in a container with style="display:none;"
 * 			- default: (optional, default is 'eq') {string} Specify the default operator value. You can choose between:
 * 				- 'eq' for equals (default operator) (SIMPLE operator)
 * 				- 'gt_or_eq' for greater than or equals (SIMPLE operator)
 * 				- 'lt_or_eq' for less than or equals (SIMPLE operator)
 * 				- 'betw' for between (SIMPLE operator)
 * 				- 'like' (ADVANCED operator)
 * 				- 'notlike' for not like (ADVANCED operator)
 * 				- 'noteq' for not equals (ADVANCED operator)
 * 				- 'gt' for greater than (ADVANCED operator)
 * 				- 'lt' for less than (ADVANCED operator)
 * 				- 'regexp' for regular expressions (ADVANCED operator)
 * 				- 'notregexp' for regular expressions (ADVANCED operator)
 * 				- 'begins' (ADVANCED operator)
 * 				- 'notbegins' (ADVANCED operator)
 *				- 'ends' (ADVANCED operator)
 * 				- 'notends' (ADVANCED operator)
 * 			- forbid_op: (optional, default is an empty array) {numerical array} Specify what operators are forbidden. For example, you can specify to 
 * 				prohibit the operators ("gt_or_eq", "lt_or_eq", "betw") for the country_id field. Thus this field, will display its value list, but
 * 				there will be no unconsistant operators. You can prevent all operators to be displayed:
 * 				- 'eq' for equals (default operator) (SIMPLE operator)
 * 				- 'gt_or_eq' for greater than or equals (SIMPLE operator)
 * 				- 'lt_or_eq' for less than or equals (SIMPLE operator)
 * 				- 'betw' for between (SIMPLE operator)
 * 				- 'like' (ADVANCED operator)
 * 				- 'notlike' for not like (ADVANCED operator)
 * 				- 'noteq' for not equals (ADVANCED operator)
 * 				- 'gt' for greater than (ADVANCED operator)
 * 				- 'lt' for less than (ADVANCED operator)
 * 				- 'regexp' for regular expressions (ADVANCED operator)
 * 				- 'notregexp' for regular expressions (ADVANCED operator)
 * 				- 'begins' (ADVANCED operator)
 * 				- 'notbegins' (ADVANCED operator)
 *				- 'ends' (ADVANCED operator)
 * 				- 'notends' (ADVANCED operator)
 * 			- display: {associative array} (optional) Display options on the research operators:
 * 				- show: {bools|string} (optional, default is "default") Specifies if the operator must be shown. Possible values are:
 * 					- "default": means default showing, that's to say: 
 * 						- no operator for text fields (sql operator will be LIKE '%pattern%')
 * 						- operator is shown for any other field type.
 * 					- true: force the operator menu to be shown
 * 					- false: force the operator menu to be hidden
 * 				- advanced: {bools} (optional, defaul is false) Specify if the advanced operators must be shown instead of the simple ones.
 * 			- cond_group: {associative array} (optional) Are options about the field and its grouping:
 * 				(see also datamalico_server_ajax::research_build_select_query() and get_one_intelligent_condition in the datamalico_server_ajax.lib.php)
 * 				- name: {string} (optional, default is 'default', the main group into a WHERE clause) eg:
 * 					@code	
 *					SELECT ...
 *					WHERE
 *					# Condition Group: default
 *					(
 *					tablename.fieldname = 'value'
 *					)
 * 				- parent: {string} (optional, default is 'none') This is the name of the parent group (because groups can be cascading)
 * 				- join_op: {string} (optional, default is 'AND') This is the join operator into the group itself
 * 				- oper_opt: {associative array} Are operator options:
 * 					- exact_word: {bool} (optional, default is false) If true, it searches the exact word: 'word' and not a part of word: '%word%'
 * 					- exact_expr: {bool} (optional, default is false) If true, it searches the exact expression: 'hello world' and not several expression: 'hello' + 'world'
 * 					- all_words: {bool} (optional, default is true) If true, it searches if ALL expressions are present in a field, instead of only one expression.
 *
 *
 * @todo Implement the missing form_field_type parameters:
 * 	- wysiwyg
 * 	- custom_menu: a menu which would be an HTML div...
 * 	- alignment: could be the alignment desired for such a field.
 *
 * Tip: We advise you to set the main config for your table fileds (in the frontend_access.conf.php file) the closest to DEFAULT_FRONTEND_SETTINGS, and then specialize
 * the configuration on calling specific methods such as datamalico_server_dbquery::select()
 *
 * Synthax
 * @code
 * $GLOBALS['security']['frontend_access']['{table}'] = array (
 * 	'{field_name}' => array (
 * 		'field_label' => $GLOBALS['mil_lang_common']['country_id']
 * 		, 'accesses' => array (
 * 			'rights' => "write"
 * 			, 'behavior' => "onready"
 * 		)
 * 		, 'form_field_type' => "select"
 * 		, 'valuelist' => get_the_valuelist ("SELECT country_id, $lang FROM `mil_c_country` WHERE enabled = 1 ORDER BY $lang")
 * 		, 'maxlength' => 255
 * 		, 'data_validator' => array (
 * 			, 'input' => array (
 * 				'client' => array (
 * 					'keypress' => 'function DVIC_tablename_fieldname_keyup () { event.stopPropagation(); console.log ($(this).val()); return $(this); }'
 * 				)
 * 			)
 * 			, 'output' => "DVO_any_custom_global_function_name"
 * 		)
 * 		, 'research_operators' => array (
 * 			'default' => 'eq'
 * 			, 'forbid_op' => array (
 * 				'like', 'gt_or_eq', 'lt_or_eq', 'betw', 'notlike', 'noteq', 'gt', 'lt', 'regexp', 'notregexp', 'begins', 'notbegins', 'ends', 'notends'
 * 			)
 * 		)
 * 	)
 * );
 * @endcode
 *
 * Synthax, when overriding locally by sending a $frontend_access to methods like 
 * 	- datamalico_server_dbquery::select()
 * 	- datamalico_server_dbquery::select_empty()
 * 	- datamalico_server_dbquery::select_multiselist()
 * 	- datamalico_server_ajax::research_get_form_structure()
 *
 * @code
 * $frontend_access = array (
 * 	'{field_name_of_the_select_clause}' => array (
 * 		'field_label' => ...
 * 		, 'accesses' => ...
 * 		, ...
 * 	)
 * );
 * @endcode
 *
 *
 *
 * @warning When you set the valuelist parameter, we advise you not to use the array_merge() php function. IDs could be swapped:
 * 	For example, it would have be handy to do so, but there is a swap between IDs:
 * @code
 * $GLOBALS['security']['frontend_access']['registered'] = array (
 * 	'gender' => array (
 * 		'field_label' => $GLOBALS['mil_lang_common']['registered.gender']
 * 		, 'accesses' => array (
 * 			'rights' => "write"
 * 			, 'behavior' => "onready"
 * 		)
 * 		, 'form_field_type' => "select"
 * 		, 'valuelist' => get_the_valuelist (
 * 			"SELECT gender_id, label FROM `gender` WHERE enabled = 1 ORDER BY sort_index"
 * 			, $GLOBALS['lang_common']['gender_listing_please_choose']
 * 		)
 * 	)
 * );
 *
 * // Even if the table 'gender' stores:
 * // [1] => Male
 * // [2] => Female
 * // the results will be:
 * // [0] => -- Please choose --
 * // [1] => Female
 * // [2] => Male
 * // Why? Because here is what the array_merge() php function says: "Values in the input array with numeric 
 * // keys will be renumbered with incrementing keys starting from zero in the result array."
 * @endcode
 * So Here is the practice we advise:
 * @code
 * $gender_valuelist = get_the_valuelist (
 * 	"SELECT gender_id, label FROM `gender` WHERE enabled = 1 ORDER BY sort_index"
 * 	, $GLOBALS['mil_lang_common']['gender_listing_please_choose']
 * );
 * $GLOBALS['security']['frontend_access']['registered'] = array (
 * 	'gender' => array (
 * 		'field_label' => $GLOBALS['lang_common']['registered.gender']
 * 		, 'accesses' => array (
 * 			'rights' => "write"
 * 			, 'behavior' => "onready"
 * 		)
 * 		, 'form_field_type' => "select"
 * 		, 'valuelist' => $gender_valuelist
 * 		, 'research_operators' => array (
 * 			'default' => 'eq'
 * 			, 'forbid_op' => array (
 * 				'like', 'gt_or_eq', 'lt_or_eq', 'betw', 'notlike', 'noteq', 'gt', 'lt', 'regexp', 'notregexp', 'begins', 'notbegins', 'ends', 'notends'
 * 			)
 * 		)
 * 	)
 * );
 * @endcode
 *
 * @warning
 * Remark on valuelists: most of the time, the 0 value, is used to insert the item, requiring the user to populate the field, eg: "Please populate".
 * Thus in case of yes/no select field, the no value won't be 0.
 */

$GLOBALS['security']['frontend_access']['DEFAULT_FRONTEND_SETTINGS'] = array (
	'field_label' => null		// if null datamalico.lib.js shows the name of the DB field.
	, 'accesses' => array (
		'rights' => "read"
		, 'behavior' => "onready"
	)
	, 'form_field_type' => "text"
	, 'valuelist' => array ()
	, 'autocomplete_vars' => array (
		'source' => ""
		, 'minlength' => 3
		, 'force_to_select' => true
		, 'categories' => false
	)
	, 'maxlength' => null
	, 'max_display_length' => null
	//, 'DVIC' => null
	, 'data_validator' => array (
		'input' => array (
			'client' => null
		)
		, 'output' => null
	)
	, 'research_operators' => array (
		'default' => "eq"
		, 'forbid_op' => array ()
		, 'display' => array (
			'show' => "default"	// "default", false, true
			, 'advanced' => false
		)
		, 'cond_group' => array (
			'name' => "default"	
			, 'parent' => "none"
			, 'join_op' => "AND"
			, 'oper_opt' => array (
				'exact_word' => false
				, 'exact_expr' => false
				, 'all_words' => true
			)
		)
	)
);



// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// valuelists

$lang = $GLOBALS['config_ini']['region']['lang'];
/**<
	@warning In the datamalico project (actually all db stored values), all the boolean data are not really boolean.
	MySQL stores a boolean under the tinyint(1) form.
	- Moreover, there must be a value for all "Please choose a value" (please_choose_value) items. Thus, in the DB a boolean looks like:
	- 0: please_choose_value
	- 1: yes_value (true)
	- 2: no_value (false)
	- Thus for all your your boolean evaluation, don't use any CAST or CONVERT type function, and check if the value is 1 for true, otherwise, this is false (0 or 2).
 */
$yes_no_valuelist = array (
	'0' => $GLOBALS['mil_lang_common']['please_choose']
	, '1' => $GLOBALS['mil_lang_common']['yes']
	, '2' => $GLOBALS['mil_lang_common']['no']
);

$type_valuelist = get_the_valuelist (
	"SELECT type_id, type_name FROM `starwars_config_type` WHERE enabled = 1 ORDER BY sort_index"
	, $GLOBALS['mil_lang_common']['please_choose'] // will be the index 0
);

$attribute_valuelist = get_the_valuelist ("SELECT attr_id, attribute FROM `starwars_config_attribute` WHERE enabled = 1 ORDER BY sort_index");

// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_config tables : config tables

//starwars_config_attribute 
$GLOBALS['security']['frontend_access']['starwars_config_attribute'] = array (
	'attribute' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_config_attribute.attribute']
	)
);

// starwars_config_type
$GLOBALS['security']['frontend_access']['starwars_config_type'] = array (
	'type_name' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_config_type.type_name']
	)
);

// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_data tables : data tables

// starwars_data_character
$GLOBALS['security']['frontend_access']['starwars_data_character'] = array (
	'char_id' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.char_id']
	)
	, 'fullname' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.fullname']
	)
	, 'change_date' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.change_date']
		, 'form_field_type' => "datepicker"
	)
	, 'owner_ip' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.owner_ip']
	)
	, 'description' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.description']
	)
	, 'type_id' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.type_id']
		, 'form_field_type' => "select"
		, 'valuelist' => $type_valuelist
		, 'research_operators' => array (
			'default' => "eq" 
			, 'forbid_op' => array (
				'like', 'gt_or_eq', 'lt_or_eq', 'betw', 'notlike', 'noteq', 'gt', 'lt', 'regexp', 'notregexp', 'begins', 'notbegins', 'ends', 'notends' 
			)
			, 'display' => array (
				'show' => false
			)
		)
	)
);

// starwars_data_character2attribute
$GLOBALS['security']['frontend_access']['starwars_data_character2attribute'] = array (
	'char2attr_id' => array (
		'attr_id' => array ('rights' => "hidden")
	)
	, 'char_id' => array (
		'field_label' => $GLOBALS['mil_lang_common']['starwars_data_character.fullname']
	)
	, 'attr_id' => array (
		'form_field_type' => "select"
		, 'valuelist' => $attribute_valuelist
		, 'research_operators' => array (
			'default' => "eq"
			, 'forbid_op' => array (
				'like', 'gt_or_eq', 'lt_or_eq', 'betw', 'notlike', 'noteq', 'gt', 'lt', 'regexp', 'notregexp', 'begins', 'notbegins', 'ends', 'notends' 
			)
		)
	)
);


?>
