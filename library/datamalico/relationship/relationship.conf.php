<?php

/** 
 * @file
 * CUSTOM FILE: File that you have to customize !!!
 *
 * This file stores reslationships between tables of the system.
 *
 * @author	Christophe DELCOURTE (and took a bit of inspiration from the Xataface project written by Steve HANNAH)
 * @version	1.0
 * @date	2012
 *
 * datamalico relationships is the best way to simplify many-to-many table relations in a database.
 *
 * @warning Before filling these parameters, you must have knowledges about relations between tables.
 * 	- What is a one-to-many relation
 * 	- What is a many-to-one relation
 * 	- What is a many-to-many relation
 *
 * In a database, there are 3 kinds of relationships between tables:
 * 	- (one_to_one) = Should not happen (because is normally in the same table on the same record). 
 * 		- But if ever for specific reason you need it, use a many_to_one and one_to_many relation.
 * 	- many_to_one
 * 		- Many people have one born country. (people to country relationship is many to one)
 *			- Valuelist: That's why, for one people, there is a valuelist relative to his born country. He can choose only one country.
 *			- Thus on the people side, the relation to country is many to one or n-1).
 * 		- Many employees work in one department. (employee to department relationship is many to one)		
 * 		- ex: A person (table) has a field born_country refering to the country_id of the list table : country.
 * 		- Then when displaying the content of the field born_country, you will see the country_name (stored in the country table) instead of the id.
 * 		- If you define a many_to_one relation for a table, the matching table must have a matching one_to_many relation.
 * 	- one_to_many = Is the oposite relation of the many_to_one. See many_to_one.
 * 		- If you define a one_to_many relation for a table, the matching table must have a matching many_to_one relation.
 *		- 1 born country has many people. (country to people is one to many)
 *			- Thus on the country side, the relation to people is one to many or 1-n).
 * 		- 1 department employs many employees. (department to employee is one to many)	
 * 		- An employee can work in only one department; this relationship is single-valued for employees.
 * 			On the other hand, one department can have many employees; this relationship is multi-valued for departments.
 * 			The relationship between employees (single-valued) and departments (multi-valued) is a one-to-many relationship.
 * 	- many_to_many = This is the case of a multi-selection-list = multiselist.
 * 		- Such multi-selection-lists can be found in a data model when there is a many_to_many relationship.
 * 		- when there is a many_to_many relation between, for example, Person and Profession (one person can have many profession and 1 profession can be linked to many persons),
 * 			then it means that there is a join table linking both. 
 * 		Let's call this join table "Person _Profession".
 * 			- In theory: many_to_many, implies that there are 4 links:
 * 				- a one_to_many linking Person to Person_2_Profession
 * 				- a many_to_one linking Person_2_Profession to Person
 * 				- a one_to_many linking Profession to Person_2_Profession
 * 				- a many_to_one linking Person_2_Profession to Profession
 * 			@note - In practice with datamalico:
 * 				- just one many_to_many linking Person to Profession. A many_to_many link defines 3 tables:
 * 					- entity_table: Person (your data)
 * 					- list_table: Multiple attributes that your entity can be linked to (Profession in our case).
 * 					- join_table: The table linking both.
 * 		- ex: Each person can have multiple professions and each profession can be linked to several persons
 * 			- entity_table: Let's say, that our table Person is our main entity.
 * 			- list_table (or list_table): Then our Profession table, would be the list_table
 * 			- join_table: In between, (linking both tables), you need a table (let's say Person_2_Profession) where to store the records listing 
 * 			what are the professions of a person. In such a table you could also store attibutes defining this relation: 
 * 			a date related to the begining of the profession...
 * 		- ex2: Let's consider the entity of a product and the entity of an order. An order contains multiple products, and of course the same product
 * 		can be in different orders. Thus we have a many_to_many relationship. When a user adds a product to an order, where do you store the number of product he wants?
 * 		If I want three copies of Star Wars, where do I store it ? In the order entity? No, it makes no sense. In the product entity? No, it makes no sense too. 
 * 		This quantity is an attribute of the relationship between product and control, not an attribute of the Product or Order.
 * 		@note - Note that a many_to_many relationship on a table has its opposite many_to_many relationship in the linked table.
 *
 *
 * @warning You must know that for a multiple selection list, the join table must have as primary keys, the ids related to other tables. 
 * 	You must anyway have an auto_increment field, that identifies the record, but, this must not be the primary key, but just a UNIQUE key.
 * 	Primary keys are the ids which join tables!!!
 * For example:
 * @code
 * CREATE TABLE `Person_2_Profession` (
 * 	`pers_2_prof_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * 	`person_id` int(10) unsigned NOT NULL,
 * 	`profession_id` tinyint(3) unsigned NOT NULL,
 * 	PRIMARY KEY (`person_id`,`profession_id`),
 * 	UNIQUE KEY `pers_2_prof_id` (`pers_2_prof_id`)
 * ) ENGINE=MyISAM AUTO_INCREMENT=71 DEFAULT CHARSET=utf8 COMMENT='Join table between Person and Profession';
 * @endcode
 * Note also that if you wish to specify the join between 2 Person and Profession, you can add fields to this join table.
 *
 * Synthax of configuration:
 * @code
 * $GLOBALS['relationship']['{table}'] = array (
 * 	'many_to_one' => array (
 * 		'{linked_table_A}' => "{table}.{field_name_to_linked_table_A}"
 * 	)
 * 	, 'one_to_many' => array (
 * 		'{linked_table_B}' => "{table}.{field_name_to_linked_table_B}" // Most of the time, this is the id, primary key of the {table}.
 * 	)
 * 	, 'many_to_many' => array (
 * 		'{linked_table_C}' => array (
 * 			'join_table' => "{join_table_between_table_and_linked_table_C}"
 * 		)
 * 	)
 * );
 * @endcode
 *
 *
 * Example of configuration for an entity table, a list table, their join table, and a config table:
 *
 * @code
 * $GLOBALS['relationship']['mil_c_country'] = array (
 * 	'one_to_many' => array (
 * 		'mil_d_registered' => "mil_c_country.country_id"
 * 		, 'mil_d_demand' => "mil_c_country.country_id"
 * 	)
 * );
 * 
 * $GLOBALS['relationship']['mil_c_service'] = array (
 * 	'one_to_many' => array (
 * 		'mil_d_demand_2_service' => "mil_c_service.service_id"
 * 	)
 * 	, 'many_to_many' => array (
 * 		'mil_d_demand' => array (
 * 			'join_table' => "mil_d_demand_2_service"
 * 		)
 * 	)
 * );
 * 
 * $GLOBALS['relationship']['mil_d_demand'] = array (
 * 	'many_to_one' => array (
 * 		'mil_c_country' => "mil_d_demand.country_id"
 * 	)
 * 	, 'one_to_many' => array (
 * 		'mil_d_demand_2_service' => "mil_d_demand.demand_id"
 * 	)
 * 	, 'many_to_many' => array (
 * 		'mil_c_service' => array (
 * 			'join_table' => "mil_d_demand_2_service"
 * 		)
 * 	)
 * );
 * 
 * $GLOBALS['relationship']['mil_d_demand_2_service'] = array (
 * 	'many_to_one' => array (
 * 		'mil_d_demand' => "mil_d_demand_2_service.demand_id"
 * 		, 'mil_c_service' => "mil_d_demand_2_service.service_id"					
 * 	)
 * );
 * @endcode
 *
 * @todo The $GLOBALS['relationship']['mil_d_registered']['many_to_one'] has several links to the same table : mil_c_country (country_id, country_phone, country_mobile)
 * 	please correct this.
 *
 */



$lang = $GLOBALS['config_ini']['region']['lang'];


// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_config tables : config tables

$GLOBALS['relationship']['starwars_config_attribute'] = array (
	'one_to_many' => array (
		'starwars_data_character2attribute' => "starwars_config_attribute.attr_id" // many_to_many
	)
	, 'many_to_many' => array ( // many_to_many
		'starwars_data_character' => array (
			'join_table' => "starwars_data_character2attribute"
		)
	)
);

$GLOBALS['relationship']['starwars_config_type'] = array (
	'one_to_many' => array (
		'starwars_data_character' => "starwars_config_type.type_id"
	)
);

// ######################################
// How to create a many_to_many relation?
// --------------------------------------
//
// In the entity table: create a many_to_many to the list table.
// In the list table: create a many_to_many to the entity table.
//
// In the join table: create a many_to_one to the list table.
// In the list table: create a one_to_many to the join table.
//
// In the join table: create a many_to_one to the entity table.
// In the entity table: create a one_to_many to the join table.


// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// starwars_data tables : data tables


// character tables:
$GLOBALS['relationship']['starwars_data_character'] = array (
	'many_to_one' => array (
		'starwars_config_type' => "starwars_data_character.type_id"
	)
	, 'one_to_many' => array (
		'starwars_data_character2attribute' => "starwars_data_character.char_id" // many_to_many
	)
	, 'many_to_many' => array ( // many_to_many
		'starwars_config_attribute' => array (
			'join_table' => "starwars_data_character2attribute"
		)
	)
);

$GLOBALS['relationship']['starwars_data_character2attribute'] = array (
	'many_to_one' => array (
		'starwars_config_attribute' => "starwars_data_character2attribute.attr_id" // many_to_many
		, "starwars_data_character" => "starwars_data_character2attribute.char_id" // many_to_many
	)
);









$GLOBALS['relationship']['mil_c_basket_item_status'] = array (
	'one_to_many' => array (
		'mil_d_basket' => "mil_c_basket_item_status.status_id"
		, 'mil_d_basket_invoice' => "mil_c_basket_item_status.status_id"
	)
);

$GLOBALS['relationship']['mil_c_basket_item_type'] = array (
	'one_to_many' => array (
		'mil_d_basket_item' => "mil_c_basket_item_type.type_id"
	)
);

$GLOBALS['relationship']['mil_c_cost_architecture'] = array (
	'one_to_many' => array (
		'mil_d_demand' => "mil_c_cost_architecture.cost_id"
	)
);

$GLOBALS['relationship']['mil_c_cost_garden'] = array (
	'one_to_many' => array (
		'mil_d_demand' => "mil_c_cost_architecture.cost_id"
	)
);

$GLOBALS['relationship']['mil_c_country'] = array (
	'one_to_many' => array (
		'mil_d_registered' => "mil_c_country.country_id"
		, 'mil_d_demand' => "mil_c_country.country_id"
	)
);

$GLOBALS['relationship']['mil_c_profession'] = array (
	'one_to_many' => array (
		'mil_d_registered_2_profession' => "mil_c_profession.profession_id"
	)
	, 'many_to_many' => array (
		'mil_d_registered' => array (
			'join_table' => "mil_d_registered_2_profession"
		)
	)
);

$GLOBALS['relationship']['mil_c_role'] = array (
	'one_to_many' => array (
		'mil_d_registered_2_role' => "mil_c_role.role_id"
		, 'mil_d_demand' => "mil_c_role.role_id"
	)
	, 'many_to_many' => array (
		'mil_d_registered' => array (
			'join_table' => "mil_d_registered_2_role"
		)
	)
);

$GLOBALS['relationship']['mil_c_service'] = array (
	'one_to_many' => array (
		'mil_d_demand_2_service' => "mil_c_service.service_id"					// KEY_list_2_join
	)
	, 'many_to_many' => array (
		'mil_d_demand' => array ( 		// &$GLOBALS['relationship']['mil_d_demand']['many_to_many']['mil_c_service']		// list table
			'join_table' => "mil_d_demand_2_service"
			//, 'join_2_list' => "mil_d_demand_2_service.service_id = mil_c_service.service_id"
			//, 'join_2_entity' => "mil_d_demand_2_service.demand_id = mil_d_demand.demand_id"
			//, 'KEY_join_2_entity' => "mil_d_demand_2_service.demand_id"						// for dco_select_multiselist_api
			//, 'KEY_list_2_join' => "mil_c_service.service_id"						// for dco_select_multiselist_api
		)
	)
);

$GLOBALS['relationship']['mil_c_service_type'] = array (
	'one_to_many' => array (
		'mil_d_demand_2_service_type' => "mil_c_service_type.service_type_id"					// KEY_list_2_join
		, 'mil_c_service_type' => "mil_c_service_type.service_type_id"					// KEY_list_2_join
	)
	, 'many_to_many' => array (
		'mil_d_demand' => array ( 		// &$GLOBALS['relationship']['mil_d_demand']['many_to_many']['mil_c_service']		// list table
			'join_table' => "mil_d_demand_2_service_type"
			//, 'join_2_list' => "mil_d_demand_2_service.service_id = mil_c_service.service_id"
			//, 'join_2_entity' => "mil_d_demand_2_service.demand_id = mil_d_demand.demand_id"
			//, 'KEY_join_2_entity' => "mil_d_demand_2_service.demand_id"						// for dco_select_multiselist_api
			//, 'KEY_list_2_join' => "mil_c_service.service_id"						// for dco_select_multiselist_api
		)
	)
);

$GLOBALS['relationship']['mil_c_service_quality'] = array (
	'one_to_many' => array (
		'mil_d_demand_2_service' => "mil_c_service_quality.service_quality_id"
	)
);


// #############################################################
// #############################################################
// #############################################################
// #############################################################
// #############################################################
// Registereds  entity and related

$GLOBALS['relationship']['modx_web_users'] = array (
	'many_to_one' => array (
		'mil_d_registered' => "modx_web_users.id" // should be one_to_one relationship and vice versa for the oposite.
	)
);

$GLOBALS['relationship']['modx_web_user_attributes'] = array (
	'many_to_one' => array (
		'mil_d_registered' => "modx_web_user_attributes.internalKey" // should be one_to_one relationship and vice versa for the oposite.
	)
);

// mil_d_registered
$GLOBALS['relationship']['mil_d_registered'] = array (
	'many_to_one' => array (
		'mil_c_country' => "mil_d_registered.country_id"
		, 'mil_d_demand' => "mil_d_registered.reg_id"
		/*, 'mil_c_country' => "mil_d_registered.country_phone"
		, 'mil_c_country' => "mil_d_registered.country_mobile"
		, 'mil_c_country' => array (
			'mil_d_registered.country_id' => "Le lien entre l'inscrit et le pays se fait sur le pays de résidence."
			'mil_d_registered.country_phone' => "Le lien entre l'inscrit et le pays se fait sur l'indicatif de téléphonie fixe."
			'mil_d_registered.country_mobile' => "Le lien entre l'inscrit et le pays se fait sur l'indicatif de téléphonie mobile."
		)*/
	)
	, 'one_to_many' => array (
		'mil_d_registered_2_profession' => "mil_d_registered.reg_id"
		, 'mil_d_registered_2_role' => "mil_d_registered.reg_id"
		, 'modx_web_users' => "mil_d_registered.webuser_id" // should be one_to_one relationship and vice versa for the oposite.
		, 'modx_web_user_attributes' => "mil_d_registered.webuser_id" // should be one_to_one relationship and vice versa for the oposite.
		, 'mil_d_basket' => "mil_d_registered.reg_id"
		, 'mil_d_basket_invoice' => "mil_d_registered.reg_id"
		, 'mil_v_gallery' => "mil_d_registered.reg_id"
		, 'mil_d_offer_2_service_type' => "mil_d_registered.reg_id"
	)
	, 'many_to_many' => array (
		'mil_c_profession' => array (
			'join_table' => "mil_d_registered_2_profession"
		)
		, 'mil_c_role' => array (
			'join_table' => "mil_d_registered_2_role"
		)
	)
);

// join between registered and profession
$GLOBALS['relationship']['mil_d_registered_2_profession'] = array (
	'many_to_one' => array (
		'mil_d_registered' => "mil_d_registered_2_profession.reg_id"						// KEY_join_2_entity // pointed table, present field
		, 'mil_c_profession' => "mil_d_registered_2_profession.profession_id"					
	)
);

// join between registered and role
$GLOBALS['relationship']['mil_d_registered_2_role'] = array (
	'many_to_one' => array (
		'mil_d_registered' => "mil_d_registered_2_role.reg_id"						// KEY_join_2_entity // pointed table, present field
		, 'mil_c_role' => "mil_d_registered_2_role.role_id"					
	)
);

// mil_v_gallery 
$GLOBALS['relationship']['mil_v_gallery'] = array (
	'many_to_one' => array (
		'mil_d_registered' => "mil_v_gallery.reg_id"						// KEY_join_2_entity // pointed table, present field
	)
);



// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// demand entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

// demand entity
$GLOBALS['relationship']['mil_d_demand'] = array (
	'many_to_one' => array (
		'mil_c_cost_architecture' => "mil_d_demand.ET_cost_per_area_archi"
		, 'mil_c_cost_garden' => "mil_d_demand.ET_cost_per_area_garden"
		, 'mil_c_country' => "mil_d_demand.country_id"
		, 'mil_d_registered' => "mil_d_demand.owner_id"
		, 'mil_c_role' => "mil_d_demand.role_target"
	)
	, 'one_to_many' => array (
		'mil_v_demand_status' => "mil_d_demand.demand_id"
		, 'mil_d_demand_2_service' => "mil_d_demand.demand_id"
		, 'mil_d_demand_2_service_type' => "mil_d_demand.demand_id"
		, 'mil_d_offer_2_service_type' => "mil_d_demand.demand_id"
	)
	, 'many_to_many' => array (
		'mil_c_service' => array (
			'join_table' => "mil_d_demand_2_service"
		)
		, 'mil_c_service_type' => array (
			'join_table' => "mil_d_demand_2_service_type"
		)
	)
);

$GLOBALS['relationship']['mil_v_demand_status'] = array (
	'many_to_one' => array (
		'mil_d_demand' => "mil_v_demand_status.demand_id"
	)
);

// join between demand and mil_c_service
$GLOBALS['relationship']['mil_d_demand_2_service'] = array (
	'many_to_one' => array (
		'mil_d_demand' => "mil_d_demand_2_service.demand_id"						// KEY_join_2_entity // pointed table, present field
		, 'mil_c_service' => "mil_d_demand_2_service.service_id"
		, 'mil_c_service_quality' => "mil_d_demand_2_service.service_quality_id"		
	)
);

// join between demand and mil_c_service_type
$GLOBALS['relationship']['mil_d_demand_2_service_type'] = array (
	'many_to_one' => array (
		'mil_d_demand' => "mil_d_demand_2_service_type.demand_id"						// KEY_join_2_entity // pointed table, present field
		, 'mil_c_service_type' => "mil_d_demand_2_service_type.service_type_id"					
	)
);


// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// offer entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

// offer entity
$GLOBALS['relationship']['mil_d_offer_2_service_type'] = array (
	'many_to_one' => array (
		'mil_d_demand' => "mil_d_offer_2_service_type.demand_id"
		, 'mil_c_service_type' => "mil_d_offer_2_service_type.service_type_id"
		, 'mil_d_registered' => "mil_d_offer_2_service_type.owner_id"
	)
	, 'one_to_many' => array (
		'mil_v_offer_status' => "mil_d_offer_2_service_type.offer_id" // should be one_to_one relationship and vice versa for the oposite.
		, 'mil_d_signing' => "mil_d_offer_2_service_type.offer_id"
	)
);

$GLOBALS['relationship']['mil_v_offer_status'] = array (
	'many_to_one' => array (
		'mil_d_offer_2_service_type' => "mil_v_offer_status.offer_id" // should be one_to_one relationship and vice versa for the oposite.
	)
);


// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Signing entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

// signing entity
$GLOBALS['relationship']['mil_d_signing'] = array (
	'many_to_one' => array (
		'mil_d_offer_2_service_type' => "mil_d_signing.offer_id"
	)
);



// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Basket and Invoice entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

$GLOBALS['relationship']['mil_d_basket'] = array (
	'many_to_one' => array (
		'mil_c_basket_item_status' => "mil_d_basket.basket_status"
		, 'mil_d_registered' => "mil_d_basket.owner_id"
		, 'mil_d_purchase_cic' => "mil_d_basket.purchase_id"
	)
	, 'one_to_many' => array (
		'mil_d_basket_item' => "mil_d_basket.basket_id"
	)
);

$GLOBALS['relationship']['mil_d_basket_item'] = array (
	'many_to_one' => array (
		'mil_d_basket' => "mil_d_basket_item.basket_id"
	)
);

// I create an object invoice, because, the basket cleaner (finding abandoned baskets) will be faster, and moreover
// 	I always consider either an invoice or an current basket (available, shoppingnow or payingnow) or an abandoned basket.
// L'intérêt de créer une table invoice c'est:
// 	1. avoir un numréo incrémental.
// 	2. décharger le basket cleaner de tous les baskets déjà facturés. (mais dans ce cas, alors il faut aussi et surtout avoir une table invoice_item)
$GLOBALS['relationship']['mil_d_basket_invoice'] = array (
	'many_to_one' => array (
		'mil_c_basket_item_status' => "mil_d_basket_invoice.basket_status"
		, 'mil_d_registered' => "mil_d_basket_invoice.owner_id"
		, 'mil_d_purchase_cic' => "mil_d_basket_invoice.purchase_id"
	)
	, 'one_to_many' => array (
		'mil_d_basket_invoice_item' => "mil_d_basket_invoice.basket_id"
	)
);

$GLOBALS['relationship']['mil_d_basket_invoice_item'] = array (
	'many_to_one' => array (
		'mil_d_basket_invoice' => "mil_d_basket_invoice_item.basket_id"
	)
);


// I create mil_d_basket_abandoned for the same reason thatn I created mil_d_basket_invoice
$GLOBALS['relationship']['mil_d_basket_abandoned'] = array (
	'many_to_one' => array (
		'mil_c_basket_item_status' => "mil_d_basket_abandoned.basket_status"
		, 'mil_d_registered' => "mil_d_basket_abandoned.owner_id"
		, 'mil_d_purchase_cic' => "mil_d_basket_abandoned.purchase_id"
	)
	, 'one_to_many' => array (
		'mil_d_basket_abandoned_item' => "mil_d_basket_abandoned.basket_id"
	)
);

$GLOBALS['relationship']['mil_d_basket_abandoned_item'] = array (
	'many_to_one' => array (
		'mil_d_basket_abandoned' => "mil_d_basket_abandoned_item.basket_id"
	)
);


// ##############################################################################
// ##############################################################################
// ##############################################################################
//
// Purchase entity and related
//
// ##############################################################################
// ##############################################################################
// ##############################################################################

$GLOBALS['relationship']['mil_d_purchase_cic'] = array (
	'one_to_many' => array (
		'mil_d_basket' => "mil_d_purchase_cic.purchase_id"
		, 'mil_d_basket_invoice' => "mil_d_purchase_cic.purchase_id"
		, 'mil_d_purchase_cic_payment_attempt' => "mil_d_purchase_cic.purchase_id"
	)
);

$GLOBALS['relationship']['mil_d_purchase_cic_payment_attempt'] = array (
	'many_to_one' => array (
		'mil_d_purchase_cic' => "mil_d_purchase_cic_payment_attempt.purchase_id"
	)
);


?>
