<?php
/**
 * @file
 * File where the mil_basket class is defined
 *
 * 
 *
 *
 * Justification of the Database storage use instead of only session vars to store baskets:
 * - If using session vars to store baskets and basket items:
 * 	- I loose the overview on all the available_for_shopping baskets. And having such a global view can be handy for many reasons (seeing that an 
 * 		article is taken by someone, and thus cannot be taken by someone else, even if this article is not paid and invoiced yet).
 * 	- Moreover, 
 * 		- Q: how to clean all baskets, in this case ?
 * 		- A: By cleaning at each page loading or whatever mil_basket obj creation... --> But in such a case when the site supports 
 * 			a lot of traffic, the cleaning can be done 100 times per minutes... this is too much and speed low the server.
 * - But if using a centralised DB storage:
 * 	- I run only once a minute the basket cleaning process.
 * 	- I have the global view on available_for_shopping baskets.
 * 	- The only drawback is that it makes connections to the DB and could speed low the DB.
 *
 * Making promotions, special offers:
 * See the file: assets/snippets/demand/demandDetails/server.add_item.ajax.php for basket item addition.
 *
 * See https://www.enseignement.polytechnique.fr/informatique/INF441/INF441a/X2011-2012/Periode3/Cours/Cours4/Cours4_light.pdf for inspiration.
 *
 * @author	Christophe DELCOURTE
 * @version	1.0
 * @date	2013
 *
 *
 *
 * Rules like limiting the number of contacts and offers per demand (more exactely by demand type) complexifies a lot.
 * The basket handling stays as simple as possible, but all the coding arround the basket handling is really 
 *
 *
 * #############################
 * 
 * BASKET lifecycle and status:
 *
 * onshelves --> shoppingnow --> payingnow --> (abandoned + maybe creation of a new onshelves) --> invoice
 *
 * - 'undefined' not used yet.
 * - 'instock' not used yet.
 * - 'onshelves':
 * 	- WHAT?
 * 		- is a basket for a specific customer. This basket is ready for the next time the customer will do shopping. Such a basket can contain a promotion or a debt
 * 			in it, for example.
 * 	- WHEN?
 * 		- Becomes 'onshelves' when we give a special offer to a register in order to motivate him to make a next purchase.
 * 		- or when a previous basket becomes 'abandoned', but there are still items to be reported to the next basket purchase (such as a debt, a promotion...)
 * - 'shoppingnow':
 * 	- WHAT?
 * 		- is a basket without any existing purchase initiated
 * 		- AND with last_update <= 15 minutes (The time I let to the customer for shopping before paying).
 * 	- WHEN?
 * 		- Becomes 'shoppingnow' when the owner adds or remove something into it. When a user adds sth to a brand new basket.
 * 	- NO CLEANING on such a basket, because the customer is 'shoppingnow'.
 *      - mil_d_basket.last_update must be updated for the last time, when the basket form is sent to the bank to be paid, that is to say when becoming a 'payingnow' basket.
 * - 'payingnow':
 * 	- WHAT?
 * 		- is a basket with an existing purchase initiated
 * 		- AND with last_update <= 45 minutes (time the bank let you pay before refusing payment).
 * 	- WHEN?
 * 		- Becomes 'payingnow' when the user wants to pay, thus a purchase is created, and a basket form is sent to the bank to be paid.
 * @warning A 'payingnow' basket is locked for any operation (item modification, addition, substraction...) as long as the bank can be paid for this basket.
 * 	- Thus, NO CLEANING on such a basket, because the customer is 'payingnow'.
 * 	- 2 events can change the status of a 'payingnow' basket:
 * 		- when it is invoiced (the bank confirms the payment)
 * 		- when it is abandoned. (after 45 min for the CIC)
 * - 'abandoned':
 * 	- WHAT?
 * 		- 'shoppingnow' basket older than 15 minutes,
 * 		- or a 'payingnow' basket older than 45 minutes.
 * 	- WHEN?
 * 		- Becomes 'abandoned' by a cron shell script (See ~/admin/decorons/mysql/02_dev/mysql_decorons_02_dev_DB_basket_cleaning.sh and .mysql), which cleans 
 * 			items in a set relevant baskets to 'abandoned'.
 * 		- We must clean items of such a basket. If there are items that must be reported to the next basket (such as promotion or debts),
 * 			a new 'onshelves' basket must be created.
 * 	- DB:
 * 		- Note that all abandoned baskets and their items are no more stored in the mil_d_basket and mil_d_basket_item, but in their twin tables dedicated 
 * 			to abandoned baskets: mil_d_basket_abandoned and mil_d_basket_item_abandoned.
 * - 'invoice':
 * 	- WHAT?
 * 		- is a basket with hasBeenPaid = TRUE
 * 	- WHEN? Becomes 'invoice' when the bank confirms a payment on this basket purchase.
 * 		See kickback.php -> 1001_addon/assets/snippets/purchase/cmcic/03_purchase_back_and_acknowledge/kickback.php
 *       	hasBeenPaid must always verify that this purchase has a successfull purchase attempt. (Admin reports)
 *       - DB: 
 *       	- Note that all invoiced baskets and their items are no more stored in the mil_d_basket and mil_d_basket_item, but in their twin tables dedicated to invoice
 *       	baskets: mil_d_basket_invoice and mil_d_basket_item_invoice.
 *
 *
 *
 * ##############################
 * Because of all theses satatus, the rganisation can be a bit complex.
 * 	Have a look on the section below about the organisation.
 *
 *
 * ##############################
 * Organisation and Implicated files in the basket management:
 * 	- CONFIG:
 * 		- 1001_addon/../../config/02_dev/dev.CMCIC_Config.php is replicated owing to ~/admin/decorons/versions/upgrade.sh in the file 1001_addon/library/CMCIC_Config.php
 * 	- CLASS BASKET: 1001_addon/library/mil_basket.class.php
 * 		- The class managing the basket.
 * 	- ADDING ITEMS: Any page adding an item to a user basket, eg:
 * 		- 1001_addon/assets/snippets/demand/demandDetails/server.add_item.ajax.php
 * 	- VIEWING BASKET: 1001_addon/assets/snippets/basket/...						BASKET
 * 		- with a button: buy_now that invokes a server script (see the following step, just below)
 * 		- On validation of the basket and payment request, the basket is sent to a transitional page: see the following step:
 * 	- PURCHASE NOW: 1001_addon/assets/snippets/purchase/cmcic/01_purchase_go/client.php		PURCHASE GO
 * 		- The file locking the basket and sending data to the bank server.
 * 		- A form is generating and directly sent to the bank.
 * 	- GETTING BANK SERVER ANSWER: kickback.php -> 1001_addon/assets/snippets/purchase/cmcic/03_purchase_back_and_acknowledge/kickback.php	PURCHASE BACK
 * 		- The file transforming the basket to invoice (when paid) and populating the payment attempts data table.
 * 	- CLEANING BASKET: ~/admin/decorons/mysql/02_dev/mysql_decorons_02_dev_DB_basket_cleaning.sh AND ~/admin/decorons/mysql/02_dev/mysql_decorons_02_dev_DB_basket_cleaning.mysql
 * 		The shell invoiked via a CRON task, and the mysql script, transforming a basket to an abandoned basket when necessary.
 * 	- INVOICE PDF GENERATION: 1001_addon/assets/snippets/invoice/generate_invoice_pdf.php: File generating the pdf invoice and using:	INVOICE
 * 		- 1001_addon/library/mil_pdf_invoice.class.php: The class dedicated to the PDF invoice generation.
 *
 *
 *
 *
 * #############################
 * 
 * Items of a basket can be: 
 *       - stocked (available to be sold)
 *               - 'available' (to be purchased)
 *               - 'abandoned' (put in a basket, but then is abandoned before being paid, and we keep trace of it in the DB)
 *       - destocked (unavailable to be sold, because is already taken by another customer)
 *               - 'shoppingnow' (is in the basket)
 *               - 'payingnow' (is being paid onto the bank epayment)
 *               - 'invoice' (has been paid)
 *
 * Thus, an item can be of 7 types:
 * 	- 'undefined'   -->     undefined
 * 	- 'instock'	-->     stocked
 *      - 'onshelves'   -->     stocked
 *      - 'shoppingnow' -->     destocked 
 *      - 'payingnow'   -->     destocked 
 *      - 'abandoned'   -->     stocked (if the basket has been abandoned)
 *      - 'invoice'     -->     destocked
 *
 * #############################
 * 
 * A PURCHASE:
 * A purchase is created (in mil_d_purchase_cic) as soon as the basket form is sent to the bank to be paid.
 *
 *
 * ##############################
 * 
 * Basket cleaning: means remove items of abandoned baskets. We remove only on item_validity and can_be_removed_by_owner, but never remove a basket (because a basket keep trace of the past).
 *
 * What is the basket cleaning?
 * A customer can take an article, but cannot keep it forever in one's basket. That's why a basket cleaning must be done every X minutes in order to free
 * some abandoned articles in some abandoned baskets, so that these articles can be taken by someone else.
 * - A customer must require the payment of ones basket within 'shoppingnow_allowed_time' minutes after the last basket manipulation (article addition or deletion).
 * - A customer must pay ones basket within 'payingnow_allowed_time' minutes since the moment he clicked on the button "pay now" (initiating the bank transaction).
 * After this time the basket is cleaned.
 *
 *
 *
 * - 1. Identify and tag all the abandoned baskets:
 * 	- abandoned are:
 * 		- Everything which is 'shoppingnow' but older than 15 minutes.
 * 		- Everything which is 'payingnow' but older than 45 minutes.
 * 	- Tag them (directly in the mil_d_basket table) with the status abandoned
 * - 2. Copy them into the abandoned tables (mil_d_basket_abandoned and mil_d_basket_abandoned_items)
 * 	- using INSERT INTO target_table SELECT origin_table... : http://dev.mysql.com/doc/refman/5.0/fr/insert-select.html
 * - 3. Keep postponed items (items which must be kept for the next time: debts or promotions...) and delete other not posponed abandoned items:
 * 	- INFO : Identifying postponed items: 
 * 		- FROM mil_d_basket_item 
 * 			- WHERE status = abandoned
 * 			- AND
 * 				- validity is still ok
 * 				- OR
 * 				- owner_can_change_nb = 1 (yes) (also means that he can delete this item of his basket)
 * 	- PROCESS: Delete not posponed items:
 *		- Delete standard items (no validity, and owner_can_change_nb):
 *			-DELETE FROM mil_d_basket_item 
 *				- WHERE status = abandoned
 *				- AND validity is NULL
 *				- AND owner_can_change_nb = 1 (yes)
 *		- Delete all items where validity is over:
 *			- DELETE FROM mil_d_basket_item 
 *				- WHERE 
 *				- (status = abandoned)
 *				- AND validity is not ok any more
 *		- Delete all mil_d_basket which have no items any more:
 *			- DELETE FROM mil_d_basket
 *			- WHERE	mil_d_basket.basket_id NOT IN (SELECT mil_d_basket_item.basket_id FROM mil_d_basket_item);
 *		- --> As of now, all 'abandoned' baskets in mil_d_basket are basket with postponed items.
 * 	- PROCESS: For each mil_d_basket WHERE status = 'abandoned' change the PRIMARY KEY (See the mysql store proc: basket_abandoned_to_onshelves in mil_views_proc_func.mysql)
 * 		- REPLACE INTO mil_d_basket row (updating the pk with a new one and the status to the 'onshelves' status)
 * 		- AND REPLACE INTO mil_d_basket_item row (updating the FK mil_d_basket_item.basket_id to mil_d_basket.basket_id with the new mil_d_basket.basket_id)
 * 		- (The replace statement renews the PK)
 *
 *
 *
 *
 *
 *
 *
 * ##############################
 * 
 * Special offers:
 *
 * For french market: C'est une bonne chose de donner donner le montant de la promotion en HT, car c'est plus honnête avec les pros.
 * 	Concernant les autoentrepreneurs, comme ils ne récupère pas la TVA, on peut meêm leur dire que la promo est plus grande ! Ex: 
 * 		Insister sur le fait que la promo de 10 EUR leur est booste (du montant de la TVA) jusqu'à 11,96 EUR.
 *
 * How to make a special offer:
 * - With PHP:
 * 	- Use the mil_basket::set_item() or mil_basket::add_item() to set the special offer.
 * 	- Use the mil_basket::set_onshelves() in order to change the status from 'shoppinnow' to 'onshelves'.
 *
 * - With a SQL script:
 * 	- INSERT mil_d_basket (if not exists yet) or UPDATE it (See the REPLACE statement)
 * 		- Set the mil_d_basket.basket_status_id to the 'onshelves' status.
 * 	- INSERT the mil_d_basket_item record with the special offer.
 * 		- Set the owner_can_change_nb to false (that is to say: please_choose_value:0 or no_value:2)
 * 	
 *
 *
 * ##############################
 * 
 * More about:
 * 	- sending of data to CIC 
 * 	- and basket locking while bank payment step:
 *
 * Concerning the sending of data to CIC, the string and the MAC seal sent to the CIC server must take into account the 'payingnow' basket status.
 * WHY?
 * 	- Because if the string and the MAC seal do not take care of the status, then, it means that the basket can be paid, even if the basket is not locked for 'payingnow'.
 * 	- Imagine, you have the web page, with the string (form fields), and the MAC seal which do not take accont of the status. You also have a button: 'Pay basket now'.
 * 		The customer click on it. At this time, a javascript ajax function calls a server page called (lock.php). This server page, update the basket status and set it to 'payingnow'.
 * 		The bank page opens. The customer pays.
 * 		BUT! If the customer hack the javascript page, and block the ajax call to the server page (lock.php). Then at this time, the bank page opens, and our customer can still
 * 		add items to his basket, as this one is not locked for 'payingnow'.
 * 	- So in order to avoid such a case, the string (form fileds) and the MAC seal, must take account of the 'patingnow' status. How?
 * 	- You have the web page, WITHOUT any the string (form fields), and NO MAC seal. You also have a button: 'Pay basket now'.
 * 		The customer click on it. At this time, a new page is called: a server page called (lock.php). This server page, update the basket status and set it to 'payingnow'. + The
 * 		lock.php uses as date field (to be sent to the bank server in the string) the date of this last basket update setting it to 'payingnow'. The MAC seal is calculated 
 * 		taking account of this date. lock.php draws the form, and a javascript immediately submits the form to the bank server.
 * 		The bank page opens. The customer pays.
 * 	
 *
 * ##############################
 * What if a customer has accidentaly closed the bank payment page?
 * 	- So far, nothing to do, wait 45 min.
 * 	- In the future we could reopen the bank page, but the risk is that the 1st delay of 45min comes before the customer pays on the 2nd bank page.
 * 		Then there would be a payment on an abandoned basket... So problem.
 *
 *
 * ##############################
 * Credit Note policy: (politique de gestion des avoirs)
 *
 * Invoice = Facture
 * Credit Note = Avoir
 * Special Offer = Offre Promotionnelle
 * Voucher = un chèque-cadeau
 * Coupon = un chèque-cadeau
 * A Gift Token = un chèque-cadeau
 *
 * @warning We don't, formally, do any credit note. Otherwise this would be too simple for the professional to transform a special offer, a voucher, a coupon or a gift token
 * 	to a real Credit Note, which is a debt from our company to the client.
 * 	Thus, in any case, the smallest invoice is 0 EUR and not a negative invoice, which would the constitute a credit note, (the proof of a debt from our company to the client).
 *
 * @warning Thus, if ever we have a debt to a client, and that we need to make a credit note, then, create just an item in the client's basket, like if this was a special offer.
 * 	- Just think that the you need to specify the item_validity to the correct date depending on the country and its laws where you live. In France, it seems, that the
 * 		credit note validity is 5 years.
 * 	- Specify in the label that it refers to the invoice Nr XXXX and item Nr XXXX.
 *
 * ##############################
 * Negative purchases:
 *
 * It may happen that a client wants to validate a negative purchase. Imagine, the client has 3 special offers of (2, 4 and 4.5 EUR = 10.5 EUR) in his basket, and that he wants
 * 	to buy an article of 5.00 EUR. Thus the purchase is -5.00 EUR.
 * In this case, the oldest special offers are deducted first. Thus, imagine oldst to newest: (2, 4 and 4.5)
 * 	- The item of 2 EUR is deducted and put into the invoiced items.
 * 	- The item of 4 EUR is split.
 * 		- One part of 3.00 EUR goes to the invoiced items (then the positive item is totally paid by these special offers: 2 + 3 = 5.00)
 * 		- The other part stays in the basket for a future use: 1.00 EUR.
 * 	- The item of 4.5 EUR stays in the basket for a future use.
 *
 * Finally, the client paid nothing in order to buy the item of 5.00 EUR, but it is invoiced as a 0 EUR invoice (but no e-payment is requested.)
 * 	Moreover the client keeps the rest of the special offers (1 + 4.5 EUR) for a future purchase.
 *
 * @warning Validate a basket with only negative items (only special offers or oter credit notes...) has no impact. Only if there is a positive item to buy has the impact descripted above.
 *
 */




/**
 */
class mil_basket
{
	/**
	 * - $input {associative array} This is an array containting params given as input for this datamalico_server_dbquery object.
	 * 	- pagination: {pagination object} Pagination properties. See the pagination class in pagination.class.php
	 */
	public $owner_id;

	/**
	 * Is the number of baskets availables for shopping (that is to say with any mil_c_basket_item_status.status_name) except of an archived type : 
	 * 	'invoice' or 'abandoned.'
	 */
	public $count_available_for_shopping;

	/**
	 * Is the number of baskets locked because the customer has already opened a page in order to pay the basket.
	 * As soon as there is such a basket, the basket must be:
	 * 	- paid on the bank epayment page
	 * 	- abandoned after 'payingnow_allowed_time' and cleaned by the basket cleaner cron process.
	 */
	public $count_locked_for_payingnow;


	/**
	 * specifies what particular status have been required.
	 * 	- If is empty, the basket will be of any type but only an 'available_for_shopping' one and not an 'archived' basket: 'invoiced' or 
	 * 		'abandoned' basket.
	 * 	- Otherwise, you can specify any status defined in the DB in the mil_c_basket_item_status.status_name. Note that if you 
	 * 		specify a 'abandoned' or 'invoice', then the result will be a SQL UNION of mil_d_basket and mil_d_basket_invoice and 
	 * 		mil_d_basket_abandoned.
	 */
	public $status_name;

	/**
	 * The baskets itself (all fields from the table mil_d_basket, and mil_d_basket_invoice.invoice_id, mil_d_basket_abandoned.abandoned_id):
	 * 	- invoice_id or NULL
	 * 	- abandoned_id or NULL
	 * 	- mil_d_basket.*
	 */
	public $basket;

	private $shoppingnow_allowed_time;
	private $payingnow_allowed_time;



	/**
	 * Constructor of the mil_basket class.
	 *
	 * @params {associative array} (mandatory)
	 * 	- owner_id: {int} (mandatory) Is necessary to retrieve the basket of the owner.
	 */
	function __construct ($params)
	{
		//echo trace2web("At the begining of the " . __CLASS__ . " constructor");

		//$this->timing = array (
		//	'begin' => ''	// look for debug_chronometer () in mil_.lib.php
		//	, 'laps' => ''
		//	, 'end' =>  ''
		//);

		$this->get_config__constructor ($params);	//echo trace2web($this, "after get_config__constructor()");

		//$this->get_basket();

		//echo trace2web($this, "At the end of the " . __CLASS__ . " constructor");
	}

	private function get_config__constructor ($params)
	{
		$this->shoppingnow_allowed_time = $GLOBALS['config_ini']['basket']['shoppingnow_allowed_time'];
		$this->payingnow_allowed_time = $GLOBALS['config_ini']['basket']['payingnow_allowed_time'];
	
		if (exists_and_not_empty($params['owner_id']))
		{
			$this->owner_id = $params['owner_id'];
		}
		else
		{
			$this->owner_id = NULL;
			//new mil_Exception (__FUNCTION__ . " : params['owner_id'] must not be empty.", "1201111240", "WARN", __FILE__ .":". __LINE__ );
			//die (__FUNCTION__ . " : params['owner_id'] must not be empty." .  __FILE__ .":". __LINE__);
		}

		if (exists_and_not_empty($params['status_name'])) $this->status_name = $params['status_name'];
		else $this->status_name = null;
	}

	function __destruct ()
	{
		//echo trace2web($this, "Destruction of the object " . __CLASS__);
	}

	private function new_basket ()
	{
		if (!exists_and_not_empty($this->owner_id)) return;


		$owner_id = $this->owner_id;
		$now = now ();
		$basket_status_id = $GLOBALS['config_ini']['DB']['mil_c_basket_item_status']['undefined'];

		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$sql = "
			INSERT INTO `mil_d_basket` (`owner_id`, `last_update`, `basket_status_id`)
			VALUES ('$owner_id', '$now', '$basket_status_id');
		";
		//echo trace2web($sql, "sql");

		$mysqli_result = $mysqli_con->query($sql);
		if ($mysqli_result !== FALSE)
		{
			// SUCCESS
			//echo $mysqli_con->insert_id;		// for insert
			//echo $mysqli_con->affected_rows;	// insert, update, delete, select
		}
		else
		{
			new mil_Exception ("This is not possible to execute the request: $sql, " . trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
			//echo trace2web($mysqli_con->error, "mysqli_con->error");
		}
		//$mysqli_con->close();
	}

	private function is_there_already_an_available_basket ()
	{
		if (!exists_and_not_empty($this->owner_id)) return;


		$this->get_basket();
		$fn_return;
		//var_dump($this->count_available_for_shopping);
		if ($this->count_available_for_shopping === 0) $fn_return = false;
		else $fn_return = true;

		//echo trace2web($fn_return, "is_there_already_an_available_basket() fn_return");
		return $fn_return;
	}

	/**
	 * Returns one basket (not its content). It mainly returns available_for_shopping basket and not:
	 * 	- a locked basket because it is being 'payingnow'
	 * 	- or an 'archived' basket: 'invoiced' or 'abandoned' basket.
	 * But you can also require specific status in the list of mil_c_basket_item_status.status_name and even 'archived' baskets ('invoiced' or 'abandoned')
	 * 	by specifying status_name as parameter.
	 *
	 * @param status_name: {numerical array} (optional, default is none) You can specify what status you the mil_basket class must retrieve.
	 * 		- If you specify nothing, the basket will be of any type but only an 'available_for_shopping' one and not:
	 * 			- a locked basket because it is being 'payingnow'
	 * 			- or an 'archived' basket: 'invoiced' or 'abandoned' basket.
	 * 		- Otherwise, you can specify any status defined in the DB in the mil_c_basket_item_status.status_name. Note that if you 
	 * 			specify a 'abandoned' or 'invoice', then the result will be a SQL UNION of mil_d_basket and mil_d_basket_invoice and 
	 * 			mil_d_basket_abandoned.
	 *
	 * @param params {associative array} (optional) , the id of the basket, or for an other type ('invoice' or 'abandoned'), their invoice_id or abandoned_id too.
	 * 	By default, if you leave it blank, then the get basket, will be the current available one (If exists). But you can alos specify the status of the basket you want 
	 * 	and off course its invoice_id or abandoned_id
	 * 	- status_name: {string} (mandatory) You have only 2 choices:
	 * 		- invoice
	 * 		- abandoned
	 * 	- id: {int|string} (mandatory) invoice_id or abandoned _id depending on the status (above) param.
	 *
	 * @return Nothing is returned, but
	 * 	- $this->basket property is filled
	 * 	- $this->count_available_for_shopping is updated
	 * 	- $this->count_locked_for_payingnow is updated
	 *
	 * @warning There can be only one current basket, as long as there is a UNIQUE key on the field: mil_d_basket.owner_id
	 */
	public function get_basket ($params = array())
	{
		if (!exists_and_not_empty($this->owner_id)) return;



		$owner_id = $this->owner_id;
		$status;
		$sql;

		// ###############
		// Part only for baskets: available_for_shopping
		$status_available_for_shopping = "
			mil_c_basket_item_status.status_name != 'payingnow' 
			AND mil_c_basket_item_status.status_name != 'abandoned' 
			AND mil_c_basket_item_status.status_name != 'invoice'
			";

		$sql_available_for_shopping = "
			SELECT
			NULL AS invoice_id
			, NULL AS abandoned_id
			, mil_d_basket.basket_id
			, mil_d_basket.owner_id
			, mil_d_basket.last_update
			, mil_d_basket.purchase_id_cic
			, mil_d_basket.purchase_id_paypal
			, mil_d_basket.basket_status_id
			, mil_d_basket.TOTAL_price_cents_ET
			, mil_d_basket.TOTAL_price_cents_ATI
			, mil_d_basket.TOTAL_VAT_cents
			, mil_c_basket_item_status.status_name
			, mil_c_basket_item_status.english
			, mil_c_basket_item_status.french
			, mil_c_basket_item_status.spanish
			, mil_c_basket_item_status.german
			FROM  `mil_d_basket`
			INNER JOIN `mil_c_basket_item_status` ON mil_c_basket_item_status.status_id = mil_d_basket.basket_status_id
			WHERE
			mil_d_basket.owner_id = $owner_id
			AND 
			(
				$status_available_for_shopping
			)
			";

		$sql_available_for_shopping_count = "
			SELECT COUNT(*) FROM
			(
				$sql_available_for_shopping
			) count_temp_table # this is an alias of this temp table allowing to count and avoiding 
			# the error: 1248 - Every derived table must have its own alias
			";



		if (
			strtolower($params['status_name']) === "invoice"
			&& exists_and_not_empty($params['id'])
		)
		{
			$status_name = $params['status_name'];
			$invoice_id = $params['id'];

			$sql = "
				SELECT
				mil_d_basket_invoice.invoice_id
				, NULL AS abandoned_id
				, mil_d_basket_invoice.basket_id
				, mil_d_basket_invoice.owner_id
				, mil_d_basket_invoice.last_update
				, mil_d_basket_invoice.purchase_id_cic
				, mil_d_basket_invoice.purchase_id_paypal	
				, mil_d_basket_invoice.basket_status_id
				, mil_d_basket_invoice.TOTAL_price_cents_ET
				, mil_d_basket_invoice.TOTAL_price_cents_ATI
				, mil_d_basket_invoice.TOTAL_VAT_cents
				, mil_c_basket_item_status.status_name
				, mil_c_basket_item_status.english
				, mil_c_basket_item_status.french
				, mil_c_basket_item_status.spanish
				, mil_c_basket_item_status.german
				FROM  `mil_d_basket_invoice` INNER JOIN `mil_c_basket_item_status` 
				ON mil_c_basket_item_status.status_id = mil_d_basket_invoice.basket_status_id
				WHERE
				mil_d_basket_invoice.owner_id = $owner_id
				AND mil_c_basket_item_status.status_name = '$status_name'
				AND mil_d_basket_invoice.invoice_id = $invoice_id
				";
		}
		else if (
			strtolower($params['status_name']) === "abandoned"
			&& exists_and_not_empty($params['id'])
		)
		{
			$status_name = $params['status_name'];
			$abandoned_id = $params['id'];

			$sql = "
				SELECT
				NULL AS invoice_id
				, mil_d_basket_abandoned.abandoned_id
				, mil_d_basket_abandoned.basket_id
				, mil_d_basket_abandoned.owner_id
				, mil_d_basket_abandoned.last_update
				, mil_d_basket_abandoned.purchase_id_cic
				, mil_d_basket_abandoned.purchase_id_paypal	
				, mil_d_basket_abandoned.basket_status_id
				, mil_d_basket_invoice.TOTAL_price_cents_ET
				, mil_d_basket_invoice.TOTAL_price_cents_ATI
				, mil_d_basket_invoice.TOTAL_VAT_cents
				, mil_c_basket_item_status.status_name
				, mil_c_basket_item_status.english
				, mil_c_basket_item_status.french
				, mil_c_basket_item_status.spanish
				, mil_c_basket_item_status.german
				FROM  `mil_d_basket_abandoned` INNER JOIN `mil_c_basket_item_status` 
				ON mil_c_basket_item_status.status_id = mil_d_basket_abandoned.basket_status_id
				WHERE
				mil_d_basket_abandoned.owner_id = $owner_id
				AND mil_c_basket_item_status.status_name = '$status_name'
				AND mil_d_basket_abandoned.abandoned_id = $abandoned_id
				";
		}
		else
		{
			$sql = $sql_available_for_shopping;
		}

		//echo trace2web ($sql, "sql");

		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$basket;

		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
			//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

			if ($nbRes == 0) {
				$basket = array();
			} else if ($nbRes === 1) { 
				$basket = $mysqli_result->fetch_assoc();
			} else if ($nbRes > 1) {
				new mil_Exception (
					__FUNCTION__ . " : Should not be possible! More than one result for the query: $sql"
					, "1201111240", "ERROR", $config['calling_FILE'] .":". $config['calling_LINE'] );
			}

			$mysqli_result->free();
		} else {
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
			//echo trace2web($mysqli_con->error, "mysqli_con->error");
		}

		// ###############
		// Part only for baskets: available_for_shopping
		if ($mysqli_result = $mysqli_con->query($sql_available_for_shopping_count))
		{
			$row = $mysqli_result->fetch_row();
			$this->count_available_for_shopping = (int) $row[0];

			if ($this->count_available_for_shopping > 1)
			{
				new mil_Exception (
					__FUNCTION__ . " : Should not be possible! this->count_available_for_shopping is bigger than 1."
					, "1201111240", "ERROR", $config['calling_FILE'] .":". $config['calling_LINE'] );
			}
			$mysqli_result->free();
		}
		else
		{
			new mil_Exception (
				__FUNCTION__ . " : This is not possible to execute the request: $sql"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}



		// ###############
		// Part only for baskets: 'payingnow' that is to say locked for any item addition or deletion.
		$sql_locked_for_payingnow = "
			SELECT
			NULL AS invoice_id
			, NULL AS abandoned_id
			, mil_d_basket.basket_id
			, mil_d_basket.owner_id
			, mil_d_basket.last_update
			, mil_d_basket.purchase_id_cic
			, mil_d_basket.purchase_id_paypal	
			, mil_d_basket.basket_status_id
			, mil_d_basket.TOTAL_price_cents_ET
			, mil_d_basket.TOTAL_price_cents_ATI
			, mil_d_basket.TOTAL_VAT_cents
			, mil_c_basket_item_status.status_name
			, mil_c_basket_item_status.english
			, mil_c_basket_item_status.french
			, mil_c_basket_item_status.spanish
			, mil_c_basket_item_status.german	
			FROM  `mil_d_basket`
			INNER JOIN `mil_c_basket_item_status` ON mil_c_basket_item_status.status_id = mil_d_basket.basket_status_id
			WHERE
			mil_d_basket.owner_id = $owner_id
			AND mil_c_basket_item_status.status_name = 'payingnow'
			";

		$sql_locked_for_payingnow_count = "
			SELECT COUNT(*) FROM
			(
				$sql_locked_for_payingnow
			) count_temp_table # this is an alias of this temp table allowing to count and avoiding 
			# the error: 1248 - Every derived table must have its own alias
			";
		//echo //trace2file($sql_locked_for_payingnow, "sql_locked_for_payingnow", __FILE__);
		//echo //trace2file($sql_locked_for_payingnow_count, "sql_locked_for_payingnow_count", __FILE__);

		if ($mysqli_result = $mysqli_con->query($sql_locked_for_payingnow_count))
		{
			$row = $mysqli_result->fetch_row();
			//echo //trace2file ($row[0], "row[0]", __FILE__);
			$this->count_locked_for_payingnow = (int) $row[0];
			$mysqli_result->free();
			//echo trace2web($this->count_locked_for_payingnow, "this->count_locked_for_payingnow");

			// Get the basket payingnow if it exists:
			if ((int) $this->count_locked_for_payingnow === 1)
			{
				if ($mysqli_result = $mysqli_con->query($sql_locked_for_payingnow))
				{
					$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
					//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

					if ($nbRes == 0) {
						//$basket = array();
					} else if ($nbRes === 1) { 
						$basket = $mysqli_result->fetch_assoc();
					} else if ($nbRes > 1) {
						new mil_Exception (
							__FUNCTION__ . " : Should not be possible! More than one result for the query: $sql"
							, "1201111240", "ERROR", $config['calling_FILE'] .":". $config['calling_LINE'] );
					}

					$mysqli_result->free();
				} else {
					new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
						. trace2web($mysqli_con->error, "mysqli_con->error")
						, "1201111240", "WARN", __FILE__ .":". __LINE__ );
					//echo trace2web($mysqli_con->error, "mysqli_con->error");
				}		
			}
		}
		else
		{
			new mil_Exception (
				__FUNCTION__ . " : This is not possible to execute the request: $sql"
				, "1201111240", "WARN", $config['calling_FILE'] .":". $config['calling_LINE'] );
		}

		//$mysqli_con->close();

		// Re-evaluate the 100th to the normal values:
		$basket['TOTAL_price_cents_ET'] = _100th_2_unit ($basket['TOTAL_price_cents_ET']);
		$basket['TOTAL_price_cents_ATI'] = _100th_2_unit ($basket['TOTAL_price_cents_ATI']);
		$basket['TOTAL_VAT_cents'] = _100th_2_unit ($basket['TOTAL_VAT_cents']);

		$this->basket = $basket;
		//$this->get_items();
		//echo trace2web ($basket, "basket");
	}


	/**
	 * This function fills up the property mil_basket::items with the content of the basket selected in mil_basket::basket.
	 * It also returns a consolidation per item of the total price (ET and ATI) and the total VAT.
	 *
	 * @warning This method must never call the mil_basket::get_basket() method because, this get_basket() calls mil_basket_get_item() (Avoid recursivity).
	 */
	public function get_items ()
	{
		if (!exists_and_not_empty($this->owner_id)) return;



		//$this->get_basket();
		$basket_id = $this->basket['basket_id'];

		// I must not use the datamalico.server.dbquery::select() method in order to get items, because of the pagination param, which handles display.
		// 	Using such a method would truncate the SUM of the items amount.

		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$sql = "
			(
				SELECT
				NULL AS invoice_item_pk
				, NULL AS abandoned_item_pk
				, mil_d_basket_item.basket_item_pk
				, mil_d_basket_item.basket_id
				, mil_d_basket_item.item_type_id
				, mil_d_basket_item.item_fk
				, mil_d_basket_item.item_label
				, mil_d_basket_item.item_nb_100th
				, mil_d_basket_item.VAT_rate_100th
				, mil_d_basket_item.item_unit_price_cents_ET
				, mil_d_basket_item.item_unit_price_cents_ATI
				, mil_d_basket_item.currency_id
				, mil_c_currency.currency_code
				, mil_c_currency.currency_display
				, mil_c_currency.currency_rate
				, mil_d_basket_item.item_validity
				, mil_d_basket_item.owner_can_change_nb
				FROM `mil_d_basket_item`
				INNER JOIN `mil_c_currency` ON mil_c_currency.currency_id = mil_d_basket_item.currency_id
				WHERE
				mil_d_basket_item.basket_id = $basket_id
			)
			UNION
			(
				SELECT
				mil_d_basket_invoice_item.invoice_item_pk
				, NULL AS abandoned_item_pk
				, mil_d_basket_invoice_item.basket_item_pk
				, mil_d_basket_invoice_item.basket_id
				, mil_d_basket_invoice_item.item_type_id
				, mil_d_basket_invoice_item.item_fk
				, mil_d_basket_invoice_item.item_label
				, mil_d_basket_invoice_item.item_nb_100th
				, mil_d_basket_invoice_item.VAT_rate_100th
				, mil_d_basket_invoice_item.item_unit_price_cents_ET
				, mil_d_basket_invoice_item.item_unit_price_cents_ATI
				, mil_d_basket_invoice_item.currency_id
				, mil_c_currency.currency_code
				, mil_c_currency.currency_display
				, mil_c_currency.currency_rate
				, mil_d_basket_invoice_item.item_validity
				, mil_d_basket_invoice_item.owner_can_change_nb
				FROM `mil_d_basket_invoice_item`
				INNER JOIN `mil_c_currency` ON mil_c_currency.currency_id = mil_d_basket_invoice_item.currency_id
				WHERE
				mil_d_basket_invoice_item.basket_id = $basket_id
			)
			UNION
			(
				SELECT
				NULL AS invoice_item_pk
				, mil_d_basket_abandoned_item.abandoned_item_pk
				, mil_d_basket_abandoned_item.basket_item_pk
				, mil_d_basket_abandoned_item.basket_id
				, mil_d_basket_abandoned_item.item_type_id
				, mil_d_basket_abandoned_item.item_fk
				, mil_d_basket_abandoned_item.item_label
				, mil_d_basket_abandoned_item.item_nb_100th
				, mil_d_basket_abandoned_item.VAT_rate_100th
				, mil_d_basket_abandoned_item.item_unit_price_cents_ET
				, mil_d_basket_abandoned_item.item_unit_price_cents_ATI
				, mil_d_basket_abandoned_item.currency_id
				, mil_c_currency.currency_code
				, mil_c_currency.currency_display
				, mil_c_currency.currency_rate
				, mil_d_basket_abandoned_item.item_validity
				, mil_d_basket_abandoned_item.owner_can_change_nb
				FROM `mil_d_basket_abandoned_item`
				INNER JOIN `mil_c_currency` ON mil_c_currency.currency_id = mil_d_basket_abandoned_item.currency_id
				WHERE
				mil_d_basket_abandoned_item.basket_id = $basket_id
			)	
			ORDER BY basket_item_pk
			";

		//echo trace2web ($sql, "sql" . __LINE__);
		$items = array ();

		if ($mysqli_result = $mysqli_con->query($sql))
		{
			$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
			$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

			if ($nbRes == 0) {
				new mil_Exception (__FUNCTION__ . " : Should not happen: $sql", "1201111240", "WARN", __FILE__ .":". __LINE__ );
			} else if ($nbRes > 0) {
				// The first record will be $items[1]
				for ($l = 1; $row = $mysqli_result->fetch_assoc(); $l++) {
					$items[$l] = $row;
				}
			}

			$mysqli_result->free();
		} else {
			new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "WARN", __FILE__ .":". __LINE__ );
			//echo trace2web($mysqli_con->error, "mysqli_con->error");
		}
		//$mysqli_con->close();

		foreach ($items as $index => $row)
		{
			// Re-evaluate the 100th to the normal values:
			$items[$index]['item_nb_100th'] = _100th_2_unit ($row['item_nb_100th']);
			$items[$index]['VAT_rate_100th'] = _100th_2_unit ($row['VAT_rate_100th']);
			$items[$index]['item_unit_price_cents_ET'] = _100th_2_unit ($row['item_unit_price_cents_ET']);
			$items[$index]['item_unit_price_cents_ATI'] = _100th_2_unit ($row['item_unit_price_cents_ATI']);

			// Calculate the Total price per item and its total VAT:
			$items[$index]['item_TOTAL_price_cents_ET'] = $items[$index]['item_unit_price_cents_ET'] * $items[$index]['item_nb_100th'];
			//$items[$index]['item_TOTAL_price_cents_ATI'] = $items[$index]['item_unit_price_cents_ATI'] * $items[$index]['item_nb_100th'];
			$items[$index]['item_TOTAL_price_cents_ATI'] = $items[$index]['item_unit_price_cents_ET'] * $items[$index]['item_nb_100th'] * (1 + $items[$index]['VAT_rate_100th'] / 100);

			$items[$index]['item_TOTAL_VAT'] = $items[$index]['item_TOTAL_price_cents_ATI'] - $items[$index]['item_TOTAL_price_cents_ET'];
		}

		$this->items = $items;
	}

	/**
	 * Sets an item in the basket for this owner (but nothing is set if mil_basket::count_locked_for_payingnow > 0).
	 * 	(See mil_basket::lock_for_payingnow() to lock the basket during the time of the payment).
	 *
	 * If, in the DB, no basket is created for the current user, a new one is created for him, and the item is added,
	 * 	and if a basket is already created, the item is added.
	 *
	 * $param item_fields: {associative array} (mandatory) Information to write for an item in the basket: Eg: for a book of (id=151)
	 * 	- addition: {bool} (optional, default is false) Setting this variable to true, is like using the method mil_basket::add_item(). You add the number of items item_nb
	 * 		to the already existing item number.
	 * 	- basket_item_pk: {id} (optional) 
	 * 		@note If this basket_item_pk is set, then all mandatory sibling params are not mandatory any more. So this param can be the only one to identify
	 * 		an EXISTING item.
	 * 	- item_type: {string} (mandatory if basket_item_pk is not set) One value of mil_c_basket_item_type.type_name. Owing to get_config_table ("mil_c_basket_item_type", "type_id", "type_name");
	 * 		called at the load of the mil_.conf.php, you can write the mil_c_basket_item_type.type_id '1' or the mil_c_basket_item_type.type_name 'contact_offer'.
	 * 	- item_fk: {int} (mandatory if basket_item_pk is not set) The id of the item.
	 * 	- item_label: {string} (optional, default is "") The label of item, you want to write.
	 * 	- item_nb: {float} (optional, default is 1 if basket_item_pk is not set as param) One unit must be written 1, and a half unit, must be written 0.5
	 * 		- if basket_item_pk is set:
	 * 			- setting item_nb to 0 or a negative number, deletes the row of the item
	 * 		- if other mandatory params are set:
	 * 			- Note that you can 'delete' one item of the basket, by writting -1 (This is actually an addition of -1 item)
	 * 			- Note also that if you have in your basket, 5 books (id=151), and if you add -6 books (id=151), then all books (id=151) 
	 * 				are deleted of the basket (the row is deleted).
	 * 			- Adding 0 item has no effect, and no query is executed to INSERT, UPDATE, REPLACE or DELETE a row.
	 * 	- VAT_rate: {float} (mandatory if basket_item_pk is not set) Is the VAT rate to apply to the ET price. Eg: 19.6 for a VAT of 19.6%
	 * 	- item_price_ET: {float} (mandatory if basket_item_pk is not set) Is the unit price (Exlcusive of Taxes) of the item. This value is transformed and stored in DB as an integer
	 * 		(in order to optimize the storage - only 4 bytes for an integer while a decimal is much more expensive)? See the functions unit_2_100th()
	 * 		and _100th_2_unit() in data_validator.conf.php
	 * 	- currency_id: {string} (mandatory if basket_item_pk is not set) Is the currency: EUR, GBP, USD, AUD, CHF... (See what is in mil_c_currency.currency_id)
	 * 	- item_validity: {timestamp|string|int} (optional, default is NULL) Is the item validity.
	 * 	- owner_can_change_nb: {bool} (optional, default is true) Can this article be deleted of the basket by the owner, or can he change the quantity? You can set the value as follow:
	 * 		- With a real boolean: 
	 * 			- true: (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 *			- false: (no_value:2 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 		- With a integer:
	 * 			- -1: (no_value:2 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 0: (please_choose_value:0 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 1: (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 2: (no_value:2 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 		- With a string (any string is set to a lower case strin):
	 * 			- '0': (please_choose_value:0 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 *			- 'please_choose_value': (please_choose_value:0 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 *			- 'please_choose': (please_choose_value:0 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- '1': (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 'true': (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 'yes': (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- 'y': (yes_value:1 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 			- anything else: (no_value:2 in DB) (See the yes_no_valuelist in frontend_access.conf.php)
	 * 		@note We advise you to use theses values:
	 * 			- 'please_choose'
	 * 			- 'yes' or true
	 * 			- 'no' or false
	 *
	 * 	- runas: {string} (optional, default is empty) By default, this param is empty and means that the current user does the action. If this is the current user and if
	 * 		the field 'owner_can_change_nb' is false, then he cannot change anything on the record. But for administration cases, you can specify 'CODER' in order to force any change.
	 *
	 * @return fn_return: {associative array} (mandatory) is the return status:
	 * 	- returnCode: {string} (mandatory) Can be:
	 * 		- "item_set_successful"
	 * 		- "basket_well_created_and_item_set_successful"
	 * 		- "is_locked_for_payingnow"
	 * 		- "owner_can_change_nb"
	 * 	- returnMessage: {string} (optional) Can be:
	 * 		- GLOBALS['mil_lang_common']['mil_basket.locked_for_payingnow'] is the return message associated to the returnCode: "is_locked_for_payingnow"
	 * 		- empty if returnCode is "item_set_successful"
	 *
	 * Example of use with as mandatory fields: item_type, item_fk, VAT_rate, currency_id
	 * @code
	 * $set_item_status = $this_mil_page->basket->set_item( array (
	 * 	'item_type' => "contact_offer"
	 * 	, 'item_fk' => 55
	 * 	, 'item_label' => "Any label"
	 * 	, 'item_nb' => 5			// 5 items
	 * 	, 'VAT_rate' => 19.6			// 19.6%
	 * 	, 'item_price_ET' => 18			// 18 (Eur) Exclusive of Taxes.
	 * 	, 'currency_id' => 1			// EUR, GBP, USD, AUD, CHF... (See what is in mil_c_currency.currency_id)
	 * 	//, 'currency_id' => $GLOBALS['config_ini']['region']['currency']['currency_id']
	 * 	, 'item_validity' => '2013-02-15'
	 * 	, 'owner_can_change_nb' => true
	 * ));
	 * @endcode
	 *
	 * Example of use with as identifier: 
	 * @code
	 * $set_status = $this_mil_page->basket->set_item( array (
	 * 	'basket_item_pk' => 83
	 * 	, 'item_nb' => 25
	 * ));
	 * @endcode
	 */
	public function set_item ($item_fields)
	{
		//echo trace2web ("mil_basket::set_item() BEGINS");
		if (!exists_and_not_empty($this->owner_id)) return;



		$fn_return;
		//echo trace2web("set_item() method");
		$this->refresh();
		//echo trace2web($this, "this");


		// Locked, so no addition or substraction.
		//trace2file (18, "var = ", __FILE__, FALSE);
		//trace2file((int) $this->count_locked_for_payingnow, "this->count_locked_for_payingnow", __FILE__, FALSE);
		//trace2file($this, "this", __FILE__, FALSE);
		if ((int) $this->count_locked_for_payingnow > 0)
		{
			// No item can be added as long as you don't pay your current basket.
			// If you don't want to pay this basket, you must abandon your current basket for 45 min and create a new basket after this time.
			$fn_return = array (
				'returnCode' => "is_locked_for_payingnow"
				, 'returnMessage' => $GLOBALS['mil_lang_common']['mil_basket.locked_for_payingnow']
			);
		}
		else
		{
			//echo trace2web ($this, "this basket");

			// if there is no basket available_for_shopping for this owner:
			if ($this->is_there_already_an_available_basket() === false)
			{
				//echo trace2web("No basket so create one");
				$this->new_basket();
				$set_status = $this->set_item($item_fields);

				if ($set_status['returnCode'] === "item_set_successful")
				{
					$fn_return = array (
						'returnCode' => "basket_well_created_and_item_set_successful"
						, 'returnMessage' => $GLOBALS['mil_lang_common']['mil_basket.locked_for_payingnow']
					);
				}
			}

			// if there is already a basket available_for_shopping for this owner:
			else
			{
				//echo trace2web($this, "Pure addition to basket: " . __LINE__);

				// #################################
				// CONFIG vars

				$sql;
				$now = now ();
				$item_fields['addition'] = exists_and_not_empty ($item_fields['addition']) ? $item_fields['addition'] : false;

				// In case of basket_item_pk identifier:
				$basket_item_pk = exists_and_not_empty ($item_fields['basket_item_pk']) ? $item_fields['basket_item_pk'] : NULL;

				// In case of these identifiers:
				$basket_id = $this->basket['basket_id'];
				$item_type_id;
				$item_fk = $item_fields['item_fk'];

				// if basket_item_pk is not well defined, then the other identifiers and mandatory params must be well defined:
				if (!exists_and_not_empty ($basket_item_pk))
				{
					// #########
					// item_type
					if (!exists_and_not_empty($item_fields['item_type']))
					{
						$msg = __FUNCTION__ . " : Basket Addition not possible because item_type as param of the function: " . __FUNCTION__ . "()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}
					else if (gettype($item_fields['item_type']) === "string")
					{
						//echo trace2web ($item_fields['item_type'], "item_fields['item_type']");
						//echo trace2web ($GLOBALS['config_ini']['DB']['mil_c_basket_item_type'], "GLOBALS['config_ini']['DB']['mil_c_basket_item_type']");
						$item_type_id = $GLOBALS['config_ini']['DB']['mil_c_basket_item_type'][$item_fields['item_type']]; // Thanks to get_config_table()
						if (!exists_and_not_empty($item_type_id))
						{
							$msg = __FUNCTION__ 
								. " : Basket Addition not possible because item_type is not defined in a GLOBALS['config_ini']['DB']";
							new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
							die($msg);
						}
					}
					

					// #########
					// item_fk
					if (!exists_and_not_empty($item_fk))
					{
						$msg = __FUNCTION__ . " : Basket Addition not possible because item_fk as param of the function: " . __FUNCTION__ . "()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}

					// #########
					// VAT_rate
					if (!isset($item_fields['VAT_rate'])) {
						$msg = __FUNCTION__." : Basket Addition not possible because VAT_rate is empty as param of the function: ".__FUNCTION__."()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}

					// #########
					// item_price_ET
					if (!isset($item_fields['item_price_ET'])) {
						$msg = __FUNCTION__." : Basket Addition not possible because item_price_ET is empty as param of the function: ".__FUNCTION__."()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}

					// #########
					// currency_id
					if (!isset($item_fields['currency_id'])) {
						$msg = __FUNCTION__." : Basket Addition not possible because currency_id is empty as param of the function: ".__FUNCTION__."()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}
				}

				// ####################################
				// Get an hypothetical already existing item of the same nature, in order to addition $item_fields['item_nb']
				global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

				if (exists_and_not_empty ($basket_item_pk))
				{
					$sql = "
						SELECT *
						FROM mil_d_basket_item
						WHERE
						basket_id = '$basket_id'
						AND basket_item_pk = $basket_item_pk
						";
				}
				else
				{
					$sql = "
						# SELECT basket_item_pk, item_nb_100th, item_validity, owner_can_change_nb
						SELECT *
						FROM mil_d_basket_item
						WHERE
						basket_id = '$basket_id'
						AND item_type_id = '$item_type_id'
						AND item_fk = '$item_fk'
						;
					";
				}

				//echo trace2web($sql, "sql");

				$already_existing_item;
				$nbRes;
				if ($mysqli_result = $mysqli_con->query($sql))
				{
					$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
					//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

					if ($nbRes === 0) {
						// $item_nb_100th remains the same, and there is going to be an insertion
					} else if ($nbRes === 1) {
						// but if there is already a row, it is going to be updated:
						$already_existing_item = $mysqli_result->fetch_assoc();
					} else if ($nbRes > 1) {
						new mil_Exception (__FUNCTION__ . " : Should not happen, More than one record for this query: $sql"
							, "1201111240", "WARN", __FILE__ .":". __LINE__ );
					}

					$mysqli_result->free();
				} else {
					new mil_Exception (__FUNCTION__ . " : This is not possible to execute the request: $sql, "
						. trace2web($mysqli_con->error, "mysqli_con->error")
						, "1201111240", "WARN", __FILE__ .":". __LINE__ );
					//echo trace2web($mysqli_con->error, "mysqli_con->error");
				}

				//echo trace2web ("already_existing_item['owner_can_change_nb']");
				//var_dump((bool) $already_existing_item['owner_can_change_nb']);
				//echo trace2web ($already_existing_item, "already_existing_item");




				// If the owner has no right to change the number of the item (eg: for a debt, or a promotion...)
				if (
					exists_and_not_empty ($already_existing_item)
					&& (int) $already_existing_item['owner_can_change_nb'] !== 1
					&& $item_fields['runas'] !== 'CODER'
				)
				{
					$fn_return = array (
						'returnCode' => "owner_can_NOT_change_nb"
						, 'returnMessage' => $GLOBALS['mil_lang_common']['mil_basket_item.owner_can_NOT_change_nb']
					);
				}





				// ###########################################################
				// THE REAL SETTING, ADDITION OR SUBSTRACTION of items:
				// If the owner can change the number of items for this item, or if the item doesn't exist yet in his basket:
				else
				{
					// #####
					// Settings: Fields of the mil_d_basket_item table:
					$basket_item_pk;
					if ($nbRes === 1) {$basket_item_pk = $already_existing_item['basket_item_pk'];}	// or set the already_existing_item value (REPLACE)
					else if ($nbRes === 0) {
						$basket_item_pk = "NULL";
					}						// or default for insertion (INSERT)

					//$basket_id;
					$item_type_id;
					if (!exists_and_not_empty($item_type_id)) {
						if ($nbRes === 1) {$item_type_id = $already_existing_item['item_type_id'];}	// set the already_existing_item value (REPLACE)
						else if ($nbRes === 0) {
							$msg = __FUNCTION__ . " : Basket Addition not possible because there is no mil_d_basket_item row with basket_item_pk = '".
								$basket_item_pk."'. So change the param basket_item_pk of the function: " . __FUNCTION__ . "(). SQL=" . $sql;
							new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
							die($msg);
						}						// or default for insertion (INSERT)
					}

					$item_fk;
					if (!exists_and_not_empty($item_fields['item_fk'])) {
						if ($nbRes === 1) {$item_fk = $already_existing_item['item_fk'];}	// set the already_existing_item value (REPLACE)
						else if ($nbRes === 0) {
							$msg = __FUNCTION__ . " : Basket Addition not possible because item_fk OR basket_item_pk as params of the function: " . __FUNCTION__ . "()";
							new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
							die($msg);
						}						// or default for insertion (INSERT)
					}

					$item_label;
					if (isset($item_fields['item_label'])) {$item_label = $item_fields['item_label'];} 	// set the param value first
					else if ($nbRes === 1) {$item_label = $already_existing_item['item_label'];}	// or set the already_existing_item value
					else if ($nbRes === 0) {$item_label = "";
					}						// or default for insertion

					$item_nb_100th;
					if (isset($item_fields['item_nb'])) {
						// set the item_nb_100th in the absolute:
						if ($item_fields['addition'] === false)
						{
							$item_nb_100th = unit_2_100th($item_fields['item_nb']);
						}

						// set the item_nb_100th by adding to the already existing item number:
						else
						{
							//$item_nb = _100th_2_unit($already_existing_item['item_nb_100th']) + $item_nb;
							$item_nb_100th = $already_existing_item['item_nb_100th'] + unit_2_100th($item_fields['item_nb']); // add_item() but also delete_item() here.
						}
					} 	// set the param value first
					else if ($nbRes === 1) {
						$item_nb_100th = $already_existing_item['item_nb_100th'];
					}	// or set the already_existing_item value
					else if ($nbRes === 0) {$item_nb_100th = 100;
					}						// or default for insertion

					$VAT_rate_100th;
					if (isset($item_fields['VAT_rate'])) {$VAT_rate_100th = unit_2_100th($item_fields['VAT_rate']);} 	// set the param value first
					else if ($nbRes === 1) {$VAT_rate_100th = $already_existing_item['VAT_rate_100th'];}			// or set the already_existing_item value
					else if ($nbRes === 0) {
						$msg = __FUNCTION__ . " : Basket Addition not possible because VAT_rate is empty as param of the function: " . __FUNCTION__ . "()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}								// or default for insertion

					$item_unit_price_cents_ET;
					if (isset($item_fields['item_price_ET'])) {$item_unit_price_cents_ET = unit_2_100th($item_fields['item_price_ET']);} 	// set the param value first
					else if ($nbRes === 1) {$item_unit_price_cents_ET = $already_existing_item['item_unit_price_cents_ET'];}		// or set the already_existing_item value
					else if ($nbRes === 0) {
						$msg = __FUNCTION__." : Basket Addition not possible because item_price_ET is empty as param of the function: ".__FUNCTION__."()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}								// or default for insertion

					$item_unit_price_cents_ATI = unit_2_100th(_100th_2_unit($item_unit_price_cents_ET) * (1 + (_100th_2_unit($VAT_rate_100th) / 100)));

					$currency_id;
					if (isset($item_fields['currency_id'])) {$currency_id = $item_fields['currency_id'];} 	// set the param value first
					else if ($nbRes === 1) {$currency_id = $already_existing_item['currency_id'];}			// or set the already_existing_item value
					else if ($nbRes === 0) {
						$msg = __FUNCTION__ . " : Basket Addition not possible because currency_id is empty as param of the function: " . __FUNCTION__ . "()";
						new mil_Exception ($msg, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
						die($msg);
					}								// or default for insertion

					$item_validity;
					if (isset($item_fields['item_validity'])) {$item_validity = "'" . $item_fields['item_validity'] . "'";} 	// set the param value first
					else if ($nbRes === 1) {$item_validity = "'" . $already_existing_item['item_validity'] . "'";}			// or set the already_existing_item value
					else if ($nbRes === 0) {
						$item_validity = "NULL";
					}								// or default for insertion
					if (
						strtolower($item_validity) === "'null'"
						|| strtolower($item_validity) === "''"
						|| strtolower($item_validity) === "'0'"
					) $item_validity = "NULL"; // avoid the value 0000-00-00 00:00:00

					$owner_can_change_nb;
					//if (isset($item_fields['owner_can_change_nb'])) {$owner_can_change_nb = (string) (bool) $item_fields['owner_can_change_nb'];} 	// set the param value first
					//else if ($nbRes === 1) {$owner_can_change_nb = $already_existing_item['owner_can_change_nb'];}			// or set the already_existing_item value
					//else if ($nbRes === 0) {
					//	$owner_can_change_nb = true;
					//}										// or default for insertion
					if (gettype($item_fields['owner_can_change_nb']) === "boolean")
					{
						if ($item_fields['owner_can_change_nb'] === true) $owner_can_change_nb = 1;
						else $owner_can_change_nb = 2;
					}
					else if (gettype($item_fields['owner_can_change_nb']) === "integer")
					{
						if ($item_fields['owner_can_change_nb'] === 0) $owner_can_change_nb = 0; // please_choose_value
						else if ($item_fields['owner_can_change_nb'] === 1) $owner_can_change_nb = 1; // yes_value
						else $owner_can_change_nb = 2; // no_value
					}
					else if (gettype($item_fields['owner_can_change_nb']) === "string")
					{
						// please_choose_value:
						if (strtolower($item_fields['owner_can_change_nb']) === '0') $owner_can_change_nb = 0; 
						else if (strtolower($item_fields['owner_can_change_nb']) === 'please_choose_value') $owner_can_change_nb = 0;
						else if (strtolower($item_fields['owner_can_change_nb']) === 'please_choose') $owner_can_change_nb = 0;
						// yes_value:
						else if (strtolower($item_fields['owner_can_change_nb']) === '1') $owner_can_change_nb = 1;
						else if (strtolower($item_fields['owner_can_change_nb']) === 'true') $owner_can_change_nb = 1;
						else if (strtolower($item_fields['owner_can_change_nb']) === 'yes') $owner_can_change_nb = 1;
						else if (strtolower($item_fields['owner_can_change_nb']) === 'y') $owner_can_change_nb = 1;
						// no_value:
						else $owner_can_change_nb = 2; // no_value
					}






					// ####################################
					// If there is no already existing item of the same nature, then INSERT one row.
					if ($nbRes === 0)
					{
						// Insert
						//$basket_item_pk = "NULL";
						//$item_nb_100th = unit_2_100th($item_nb);
					}

					// If there is already an hypothetical existing item of the same nature, then REPLACE it by updating the item_nb.
					else if ($nbRes === 1)
					{
						// Replace
						//$basket_item_pk = $already_existing_item['basket_item_pk'];

						// set the item_nb_100th in the absolute:
						//if ($item_fields['addition'] === false)
						//{
						//	$item_nb_100th = unit_2_100th($item_nb);
						//}

						// set the item_nb_100th by adding to the already existing item number:
						//else
						//{
						//$item_nb = _100th_2_unit($already_existing_item['item_nb_100th']) + $item_nb;
						//	$item_nb_100th = $already_existing_item['item_nb_100th'] + unit_2_100th($item_nb); // add_item() but also delete_item() here.
						//}
					}


					// In a basket an item must always be positive, and never negative. (In other words, there is an item or more in the basket, or nothing)
					if ($item_nb_100th > 0)
					{
						//$item_fields['item_type'];
						//$item_fields['item_fk'];
						//$item_label = $item_fields['item_label'];
						//$item_fields['item_nb']);
						//$VAT_rate_100th = unit_2_100th($item_fields['VAT_rate']);
						//$item_unit_price_cents_ET = unit_2_100th($item_fields['item_price_ET']);
						//$item_unit_price_cents_ATI = unit_2_100th($item_fields['item_price_ET'] * (1 + ($item_fields['VAT_rate'] / 100)));
						//$item_fields['item_validity'];
						//$item_fields['owner_can_change_nb'];

						$sql = "
							REPLACE INTO  `mil_d_basket_item` (
								`basket_item_pk`
								, `basket_id`
								, `item_type_id`
								, `item_fk`
								, `item_label`
								, `item_nb_100th`
								, `VAT_rate_100th`
								, `item_unit_price_cents_ET`
								, `item_unit_price_cents_ATI`
								, `currency_id`
								, `item_validity`
								, `owner_can_change_nb`
							)
							VALUES (
								$basket_item_pk
								, '$basket_id'
								, '$item_type_id'
								, '$item_fk'
								, '$item_label'
								, '$item_nb_100th'
								, '$VAT_rate_100th'
								, '$item_unit_price_cents_ET'
								, '$item_unit_price_cents_ATI'
								, '$currency_id'
								,  $item_validity
								, $owner_can_change_nb
							);

						";
					}

					// But if the number of items is negative, then it means that we empty the basket of all this item:
					else
					{
						// Delete only there is already a row in the basket:
						if ($basket_item_pk !== "NULL")
						{
							$sql = "
								DELETE 
								FROM `mil_d_basket_item` 
								WHERE `basket_item_pk` = $basket_item_pk
								";
						}
					}

					//echo trace2web($sql, "mil_basket::set_item() sql");


					$mysqli_result = $mysqli_con->query($sql);
					if ($mysqli_result !== FALSE)
					{
						// SUCCESS
						//echo $mysqli_con->insert_id;		// for insert
						//echo $mysqli_con->affected_rows;	// insert, update, delete, select
					}
					else
					{
						new mil_Exception ("This is not possible to execute the request: $sql, " 
							. trace2web($mysqli_con->error, "mysqli_con->error")
							, "1201111240", "WARN", __FILE__ .":". __LINE__ );
						//echo trace2web($mysqli_con->error, "mysqli_con->error");
					}

					//$mysqli_con->close();


					//echo trace2web ($this, "this");
					$fn_return = array (
						'returnCode' => "item_set_successful"
						, 'returnMessage' => ""
					);



					// ##########################
					// update the basket:
					$dco = new datamalico_server_dbquery ();

					// runas : CODER
					$dco->upsert(array (
						'table_name' => "mil_d_basket"
						, 'fields' => array (
							'basket_status_id' => $GLOBALS['config_ini']['DB']['mil_c_basket_item_status']['shoppingnow']
						)
						, 'conditions' => array (
							'basket_id' => $basket_id
						)
						, 'runas' => "CODER"
						, 'calling_FILE' => __FILE__
						, 'calling_LINE' => __LINE__
					));

					//echo trace2web($dco, "dco of the basket update to shoppingnow.");

					$this->set_basket_last_update($now);
				}

				$this->refresh();
			}
		}

		return $fn_return;
	}

	/**
	 * Adds item_nb in an available, existing (or not, because, it is then created) basket.
	 * This method is actually the same as mil_basket::set_item() except that there is no need to specify the parameter 'addition', because
	 * 	with mil_basket::add_item() this param is automatically set to true.
	 *
	 * See mil_basket::set_item() for more information.
	 */
	public function add_item ($item_fields)
	{
		$item_fields['addition'] = true;

		return $this->set_item ($item_fields);
	}

	/**
	 * Deletes an item in the basket.
	 * This method actually uses the mil_basket::add_item() method but adds a negative number of items.
	 *
	 * $param item_fields: {associative array} (mandatory) Information to write for an item in the basket: Eg: for a book of (id=151)
	 * 	- item_type: {string} (mandatory) One value of mil_c_basket_item_type.type_name
	 * 	- item_fk: {int} (mandatory) The id of the item.
	 * 	- item_label: {string} (optional, default is "") The label of item, you want to write.
	 * 	- item_nb: {float} (optional, default is 1) One unit must be written 1, and a half unit, must be written 0.5
	 * 		- Note that you can write "all" or "*" to delete everything of the item of the basket.
	 * 		- Note also that you cannot delete a negative number of items. This would be an addition. But it actually has no effect.
	 * 	- VAT_rate: {float} (mandatory) Is the VAT rate to apply to the ET price. Eg: 19.6 for a VAT of 19.6%
	 * 	- item_price_ET: {float} (mandatory) Is the unit price (Exlcusive of Taxes) of the item. This value is transformed and stored in DB as an integer
	 * 		(in order to optimize the storage - only 4 bytes for an integer while a decimal is much more expensive)? See the functions unit_2_100th()
	 * 		and _100th_2_unit() in data_validator.conf.php
	 * 	- item_validity: {timestamp|string|int} (optional, default is NULL) Is the item validity.
	 * 	- owner_can_change_nb: {bool} (optional, default is true) Can this article be deleted of the basket by the owner.
	 *	- runas: {string} (optional, default is empty) By default, this param is empty and means that the current user does the action. If this is the current user and if
	 * 		the field 'owner_can_change_nb' is false, then he cannot change anything on the record. But for administration cases, you can specify 'CODER' in order to force any change.
	 *
	 * @return Nothing is returned.
	 *
	 * Example of use.
	 * @code
	 * $this_mil_page->basket->delete_item( array (
	 * 	'item_type' => "contact_offer"
	 * 	, 'item_fk' => 55
	 * 	, 'item_label' => "Un label quelconque."
	 * 	, 'item_nb' => "all"
	 * 	, 'VAT_rate' => 19.6
	 * 	, 'item_price_ET' => 18
	 * 	, 'item_validity' => '2013-02-15'
	 * 	, 'owner_can_change_nb' => true
	 * ));
	 * @endcode
	 */
	public function delete_item ($item_fields)
	{
		//echo trace2web($function_return, "function_return");

		if (strtolower($item_fields['item_nb']) === "all" || strtolower($item_fields['item_nb']) === "*") $item_fields['item_nb'] = 4294967296;
		if ($item_fields['item_nb'] <= 0) return;

		$item_fields['item_nb'] = -($item_fields['item_nb']);
		return $this->add_item ($item_fields);
	}


	/**
	 * This method updates the table mil_d_basket and refreshes the PHP object 'basket' (only for current available basket):
	 * 	- via the private method $this->get_items()
	 * 	- via the private method $this->get_basket()
	 */
	public function refresh ()
	{
		//trace2file ($this, "this->refresh() BEGIN", __FILE__);
		if (!exists_and_not_empty($this->owner_id)) return;


		$this->get_basket();
		$this->get_items();

		$basket_id = $this->basket['basket_id'];


		// ##########################
		// Clean basket of items with quantity <= 0
		global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

		$sql = "
			DELETE 
			FROM `mil_d_basket_item` 
			WHERE `basket_id` = '$basket_id'
			AND `item_nb_100th` <= 0
			";

		$mysqli_result = $mysqli_con->query($sql);
		if ($mysqli_result !== FALSE)
		{
			// SUCCESS
			//echo $mysqli_con->insert_id;		// for insert
			//echo $mysqli_con->affected_rows;	// insert, update, delete, select
		}
		else
		{
			new mil_Exception ("This is not possible to execute the request: $sql, " 
				. trace2web($mysqli_con->error, "mysqli_con->error")
				, "1201111240", "ERROR", __FILE__ .":". __LINE__ );
			//echo trace2web($mysqli_con->error, "mysqli_con->error");
		}
		//$mysqli_con->close();




		$dco = new datamalico_server_dbquery ();
		$sum = array ();


		//trace2file ($this, "this", __FILE__, true);
		foreach ($this->items as $index => $row)
		{
			// ##########################
			// Calculate and update the ATI for each basket_item:

			//$item_unit_price_cents_ATI = unit_2_100th(_100th_2_unit($item_unit_price_cents_ET) * (1 + (_100th_2_unit($VAT_rate_100th) / 100)));
			$basket_item_pk = $row['basket_item_pk'];
			$updated_item_unit_price_cents_ATI = $row['item_unit_price_cents_ET'] * (1 + $row['VAT_rate_100th'] / 100);
			//trace2file (gettype($updated_item_unit_price_cents_ATI), "gettype - updated_item_unit_price_cents_ATI", __FILE__);
			//trace2file ($updated_item_unit_price_cents_ATI, "updated_item_unit_price_cents_ATI", __FILE__);
			//trace2file (gettype($row['item_TOTAL_price_cents_ATI']), "gettype : row['item_TOTAL_price_cents_ATI']", __FILE__);
			//trace2file ($row['item_TOTAL_price_cents_ATI'], "row['item_TOTAL_price_cents_ATI']", __FILE__);

			if ($updated_item_unit_price_cents_ATI !== $row['item_TOTAL_price_cents_ATI'])
			{
				$dco->upsert(array (
					'table_name' => "mil_d_basket_item"
					, 'fields' => array (
						'item_unit_price_cents_ATI' => unit_2_100th ($updated_item_unit_price_cents_ATI)
					)
					, 'conditions' => array (
						'basket_item_pk' => $basket_item_pk
					)
					//, 'runas' => "CODER"
					, 'calling_FILE' => __FILE__
					, 'calling_LINE' => __LINE__
				));

				//trace2file ($dco, "dco item", __FILE__);
			}


			// ##########################
			// Calculate the SUM of the whole basket in order to update it later:
			$row_calculation['TOTAL_price_cents_ET'] = $row['item_TOTAL_price_cents_ET'];
			$row_calculation['TOTAL_price_cents_ATI'] = $row['item_nb_100th'] * _100th_2_unit(unit_2_100th($updated_item_unit_price_cents_ATI)); // _100th_2_unit(unit_2_100th... in order to keep the same round().
			$row_calculation['TOTAL_VAT_cents'] = $row_calculation['TOTAL_price_cents_ATI'] - $row_calculation['TOTAL_price_cents_ET'];

			//trace2file ($row_calculation, "row_calculation", __FILE__);

			$sum['TOTAL_price_cents_ET'] += $row_calculation['TOTAL_price_cents_ET'];
			$sum['TOTAL_price_cents_ATI'] += $row_calculation['TOTAL_price_cents_ATI'];
			$sum['TOTAL_VAT_cents'] += $row_calculation['TOTAL_VAT_cents'];

			//trace2file ($sum, "sum", __FILE__);
		}	


		// ##########################
		// Calculate the SUM of the whole basket in order to update it later:
		/*
		$sum = array ();
		foreach ($this->items as $index => $row)
		{
			$sum['TOTAL_price_cents_ET'] += $row['item_TOTAL_price_cents_ET'];
			$sum['TOTAL_price_cents_ATI'] += $row['item_TOTAL_price_cents_ATI'];
			$sum['TOTAL_VAT_cents'] += $row['item_TOTAL_VAT'];
		}
		 */

		//echo trace2web ($sum, "sum");


		// ##########################
		// update the basket:
		$dco->upsert(array (
			'table_name' => "mil_d_basket"
			, 'fields' => array (
				'TOTAL_price_cents_ET' => unit_2_100th ($sum['TOTAL_price_cents_ET'])
				, 'TOTAL_price_cents_ATI' => unit_2_100th ($sum['TOTAL_price_cents_ATI'])
				, 'TOTAL_VAT_cents' => unit_2_100th ($sum['TOTAL_VAT_cents'])
			)
			, 'conditions' => array (
				'basket_id' => $basket_id
			)
			, 'calling_FILE' => __FILE__
			, 'calling_LINE' => __LINE__
		));
		//trace2file ($dco, "dco basket", __FILE__);


		$this->get_basket();
		$this->get_items();

		//trace2file ($this, "this->refresh() END", __FILE__);	
	}

	/**
	 * This method updates the mil_d_basket.last_update to the now parameter:
	 *
	 * @param now: {string to the format "Y-m-d H:i:s"} (optional, default is the result of the now() mil_.lib.php function)
	 *
	 * @return nothing
	 */
	public function set_basket_last_update ($now = false)
	{
		if (!exists_and_not_empty($this->owner_id)) return;



		if ($now === false) $now = now();

		$basket_id = $this->basket['basket_id'];

		if (exists_and_not_empty($basket_id))
		{
			// ##########################
			// update the basket:
			$dco = new datamalico_server_dbquery ();

			// runas : CODER
			$dco->upsert(array (
				'table_name' => "mil_d_basket"
				, 'fields' => array (
					'last_update' => $now
				)
				, 'conditions' => array (
					'basket_id' => $basket_id
				)
				, 'runas' => "CODER"
				, 'calling_FILE' => __FILE__
				, 'calling_LINE' => __LINE__
			));
		}
	}

	/**
	 * This method locks the basket by setting the status 'payingnow'.
	 *
	 * @param now: {string, date to the format "Y-m-d H:i:s"} (optional, default will be present time) This is the time to set as mil_d_basket.last_update
	 *
	 * @return nothing
	 */
	public function lock_for_payingnow ($now = NULL)
	{
		if (!exists_and_not_empty($this->owner_id)) return;


		$this->get_basket();			// refresh the basket and get basket_id...

		if ($now === NULL) $now = now ();
		$basket_id = $this->basket['basket_id'];

		if (exists_and_not_empty($basket_id))
		{
			// Only a "shoppingnow" basket can be set to a "payingnow"
			if (
				exists_and_not_empty($this->basket['status_name'])
				&& $this->basket['status_name'] !== "payingnow"
				&& $this->basket['status_name'] !== "abandoned"
				&& $this->basket['status_name'] !== "invoice"
			)
			{
				// update the basket:
				$dco = new datamalico_server_dbquery ();
				$dco->upsert(array (
					'table_name' => "mil_d_basket"
					, 'fields' => array (
						'last_update' => $now
						, 'basket_status_id' => $GLOBALS['config_ini']['DB']['mil_c_basket_item_status']['payingnow']
					)
					, 'conditions' => array (
						'basket_id' => $basket_id
					)
					, 'runas' => "CODER"
					, 'calling_FILE' => __FILE__
					, 'calling_LINE' => __LINE__
				));
			}


			// Only a "shoppingnow" basket can be set to a "payingnow"
			else
			{
				//echo trace2web ("Wrong basket status");
				//die();
				new mil_Exception (
					__FUNCTION__ . " : Wrong basket status. Before setting the basket to 'payingnow', it must not already be 'payingnow' or 'abandoned' or 'invoice' but it is.\n"
					. trace2web ($this, "this")
					, "1201111240", "ERROR", $config['calling_FILE'] .":". $config['calling_LINE'] );
			}			
		}

		$this->refresh ();			// get_basket again in order to update (in $this->basket object) the last update date.
	}


	/**
	 * This method stocks the basket by setting the status 'onshelves'.
	 * This is necessary to call this method when we give a special offer to a register in order to motivate him to make a next purchase.
	 *
	 * @return nothing
	 */
	public function set_onshelves ()
	{
		if (!exists_and_not_empty($this->owner_id)) return;



		$now = now ();
		$basket_id = $this->basket['basket_id'];

		if (exists_and_not_empty($basket_id))
		{
			// update the basket:
			$dco = new datamalico_server_dbquery ();
			$dco->upsert(array (
				'table_name' => "mil_d_basket"
				, 'fields' => array (
					'last_update' => $now
					, 'basket_status_id' => $GLOBALS['config_ini']['DB']['mil_c_basket_item_status']['onshelves']
				)
				, 'conditions' => array (
					'basket_id' => $basket_id
				)
				, 'runas' => "CODER"
				, 'calling_FILE' => __FILE__
				, 'calling_LINE' => __LINE__
			));
		}
	}


	/**
	 * @todo Do this method, when passing to multi-currency mode.
	 *
	 * $GLOBALS['config_ini']['region']['currency']['currency_id']
	 */
	private function set_basket_to_local_currency ()
	{
		// Run this method only if the basket is shoppingnow.
	}
}


?>
