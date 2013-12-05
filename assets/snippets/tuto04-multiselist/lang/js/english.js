/** 
* @file
* Local javascript file: 1001_addon/assets/snippets/tuto04-multiselist/lang/js/english.js
*/

// ############################################################
// ############################################################
// ############################################################
// Init:
$(document).ready(init_document);	// when the document is ready (using the jquery library)
function init_document ()
{
	// Input action
	var temp_insert_id = dco_get_temp_insert_id ();
	select_empty_ajax(temp_insert_id);
	$("#insert_button")
	.button()			// uses the jQueryUI in order to create a nice button.
	.click(insert_ajax);	// attach a function to the click event.

	// Display
	select_ajax();

	// Write action
	$("#update_button")
	.button()			// uses the jQueryUI in order to create a nice button.
	.click(update_ajax);	// attach a function to the click event.

	// Delete action
	$("#delete_button")
	.button()			// uses the jQueryUI in order to create a nice button.
	.click(delete_ajax);	// attach a function to the click event.
}


// ############################################################
// ############################################################
// ############################################################
// Ajax call to the server responding page for 

/*
* This function draws the insert form depending on your datamalico configuraiton (See the "Tuto 2 - Datamalico configuration")
*/
function select_empty_ajax(temp_insert_id)
{
	console.log("select_empty_ajax() - " + temp_insert_id);

	mil_ajax ({
		form_id: "insert_form"
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto04-multiselist/server.select_empty.ajax.php"
		, data: {
			temp_insert_id: {
				field: "starwars_data_character.char_id"
				, value: temp_insert_id
			}
		}
		, success: on_success
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK for the entity itself:
		if (
			ajaxReturn.character.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.character.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{
			$("#input_div")
			.datamalico(ajaxReturn.character)	// warning, here you must give the result of the datamalico_server_dbquery::output resulting of the datamalico_server_dbquery::select_empty()
			.display({
				field_name : "fullname"
				, row_num: 1
			});

			/*
			$("#input_div")
			.datamalico(ajaxReturn.character)	// warning, here you must give the result of the datamalico_server_dbquery::output resulting of the datamalico_server_dbquery::select_empty()
			.display_datagrid({			// display_datagrid action with a simple configuration See the documentation for more information.
			template: {
			grid: '<table></table>'
			, row: '<tr></tr>'
			, cell: '<td></td>'
			, header_cell: '<th></th>'
			}
			});
			*/
		}
		else
		{
			alert (ajaxReturn.character.metadata.returnMessage);
		}

		// ####################
		// WORK for the multiselist:
		if (
			ajaxReturn.attribute.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.attribute.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{
			//$("#input_multiselist_div").datamalico(ajaxReturn.attribute).display({field_name : "fullname", row_num: 1});

			$("#input_multiselist_div")
			.datamalico(ajaxReturn.attribute)	// warning, here you must give the result of the datamalico_server_dbquery::output resulting of the datamalico_server_dbquery::select_empty()
			.display_datagrid({			// display_datagrid action with a simple configuration See the documentation for more information.
				template: {
					grid: '<table></table>'
					, row: '<tr></tr>'
					, cell: '<td></td>'
					//, header_cell: '<th></th>'
				}
				, columns_order: ["char_id", "attribute"]
			});
		}
		else
		{
			alert (ajaxReturn.attribute.metadata.returnMessage);
		}
	}
}

/*
* This function sends your data in order to insert the new record you create.
*/
function insert_ajax ()
{
	//console.log("insert_ajax()");

	mil_ajax ({
		form_id: "insert_form"
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto04-multiselist/server.delupsert.ajax.php"
		, success: on_success
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK
		if (ajaxReturn.metadata.returnCode === "API_HAS_BEEN_CALLED")
		{
			select_ajax (); // refresh the grid from the DB itself

			$("body").datamalico(ajaxReturn).display_errors(); // displays, but alos hides previous errors if no more error.
		}
		else if (ajaxReturn.metadata.returnCode === "THERE_ARE_INVALID_DATA")
		{
			$("body")		// body, in order to find all potential error message zones within it.
			.datamalico(ajaxReturn)	// datamalico object creation.
			.display_errors({
				display_error_msg: "before" // displays the error message before the field.
			});
		}		
	}
}

/*
* This function displays the grid where you can make changes:
*/
function select_ajax ()
{
	mil_ajax ({	// you can use any other ajax method to reach your server page. I'm using my own mil_ajax() method. See the mil_ library for more information.
		data: {	any_variable: 58 }
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto04-multiselist/server.select.ajax.php"	// the path to reach your ajax responding server page which returns a json result of a mysql query.
		, success: on_success					// links the success case with the below function.
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK for the entity itself:
		if (
			ajaxReturn.character.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.character.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{

			// #######################################
			// display_datagrid()
			$('#write_div')			// jquery selector
			.datamalico(ajaxReturn.character)		// datamalico obj creation with the server result set (json)
			.display_datagrid({		// display_datagrid action with a simple configuration See the documentation for more information.
				template: {
					grid: '<table></table>'
					, row: '<tr></tr>'
					, cell: '<td></td>'
					, header_cell: '<th></th>'
				}
			});

			makeup_rows ('#write_div tr.row_class'); // put a make-up to rows
		}	


		// ####################
		// WORK for the multiselist:
		if (
			ajaxReturn.attribute.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.attribute.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{
			//$("#input_multiselist_div").datamalico(ajaxReturn.attribute).display({field_name : "fullname", row_num: 1});

			$("#write_multiselist_div")
			.datamalico(ajaxReturn.attribute)	// warning, here you must give the result of the datamalico_server_dbquery::output resulting of the datamalico_server_dbquery::select_empty()
			.display_datagrid({			// display_datagrid action with a simple configuration See the documentation for more information.
				template: {
					grid: '<table></table>'
					, row: '<tr></tr>'
					, cell: '<td></td>'
					//, header_cell: '<th></th>'
				}
				, columns_order: ["char_id", "attribute"]
			});
		}
	}
}

/*
* This function sends your modifications on existing records.
*/
function update_ajax ()
{
	//console.log("update_ajax()");

	mil_ajax ({
		form_id: "update_form"
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto04-multiselist/server.delupsert.ajax.php"
		, success: on_success
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK
		if (ajaxReturn.metadata.returnCode === "API_HAS_BEEN_CALLED")
		{
			select_ajax (); // refresh the grid from the DB itself
		}
		else if (ajaxReturn.metadata.returnCode === "THERE_ARE_INVALID_DATA")
		{
			ajaxReturn.display_error_msg = "before";	// "before", "after"
			dco_display_errors (ajaxReturn);
		}
	}
}


/*
* This function sends data in order to make some delete.
*/
function delete_ajax ()
{
	console.log("delete_ajax()");

	var data_without_sqlfields_for_delete = get_update_form_data_and_prepare_for_deletion();
	//console.log(data_without_sqlfields_for_delete);

	mil_ajax ({
		data: data_without_sqlfields_for_delete
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto04-multiselist/server.delupsert.ajax.php"
		, success: on_success
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK
		if (ajaxReturn.metadata.returnCode === "API_HAS_BEEN_CALLED")
		{
			select_ajax (); // refresh the grid from the DB itself
			alert("All the records tagged with your IP address have been deleted.");
		}
		else if (ajaxReturn.metadata.returnCode === "THERE_ARE_INVALID_DATA")
		{
			ajaxReturn.display_error_msg = "before";	// "before", "after"
			dco_display_errors (ajaxReturn);
		}
	}
}

/*
* This function get data written in HTML fields in the update_form.
* These data are transformed into a JSON format.
* Then, in order to fit the rules of the delupsert concept, fields matching the SQL fields are deleted of the retruned list, so that the server page,
* 	will receive only:
* 	- a SQL table name
* 	- a criteria (the WHERE clause) in order to make a DELETE command.
*/
function get_update_form_data_and_prepare_for_deletion()
{
	var data = $('#update_form').serializeObject();

	for (i in data)
	{
		var pattern = /\[f\]/gi;
		if (pattern.test(i)) delete data[i]; // equivalent to unset()
	}

	return data;
}

