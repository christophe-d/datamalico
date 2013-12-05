/** 
* @file
* Local javascript file: [+this_relative_file_path+]/lang/js/[+mil_lang+].js
*/

// ############################################################
// ############################################################
// ############################################################
// Init:
var pagination; // only for the paginate() method. Must be global in order to work with require_another_page()
$(document).ready(init_document);	// when the document is ready (using the jquery library)
function init_document ()
{
	select_ajax();

	pagination = {page: 1, perpage:10};
	select_ajax_with_pagination();
}


// ############################################################
// ############################################################
// ############################################################
// Ajax call to the server responding page for 
// 	- datamalico::display()
// 	- datamalico::display_datagrid()
function select_ajax ()
{
	mil_ajax ({	// you can use any other ajax method to reach your server page. I'm using my own mil_ajax() method. See the mil_ library for more information.
		data: {	any_variable: 58 }
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto01-display/server.select.ajax.php"	// the path to reach your ajax responding server page which returns a json result of a mysql query.
		, success: on_success					// links the success case with the below function.
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK
		if (
			ajaxReturn.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{

			// #######################################
			// display()
			$("#display_div_1")			// jquery selector
			.datamalico(ajaxReturn)		// datamalico obj creation with the server result set (json)
			.display({			// display action with a simple configuration
				field_name : "fullname"	// what column name you want to display (from your ajax sql result)
				, row_num: 1	// what row number you want to display (from your ajax sql result)
			});

			// #######################################
			// display_datagrid()
			$('#display_div_2')			// jquery selector
			.datamalico(ajaxReturn)		// datamalico obj creation with the server result set (json)
			.display_datagrid({		// display_datagrid action with a simple configuration See the documentation for more information.
				template: {
					grid: '<table></table>'
					, row: '<tr></tr>'
					, cell: '<td></td>'
					, header_cell: '<th></th>'
				}
			});
		}
		else
		{
			alert (ajaxReturn.metadata.returnMessage);
		}
	}
}




// ############################################################
// ############################################################
// ############################################################
// Ajax call to the server responding page for 
// 	- datamalico::paginate()
function select_ajax_with_pagination ()
{
	mil_ajax ({	// you can use any other ajax method to reach your server page. I'm using my own mil_ajax() method. See the mil_ library for more information.
		data: {
			pagination: pagination	// this is major to specify to the page server page and optimize the query: {page: 3, perpage: 10} 
		}
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto01-display/server.select_paginate.ajax.php"	// the path to reach your ajax responding server page which returns a json result of a mysql query.
		, success: on_success					// links the success case with the below function.
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK
		if (
			ajaxReturn.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{
			// #######################################
			// paginate()
			$("#display_div_3 #results_book")
			.find(".pagination")
			.datamalico(ajaxReturn)
			.paginate({
				report_ctnr: "report"
				, render_this_inner_page: render_this_inner_page // function (page_num) {...}
				, require_another_page: require_another_page // null // use null for a normal HTML link (Read the whole documentation of this method).
				// See also the pagination variable (to be sent as GET or POST) in this Javascript client page, in the target PHP server page as a parameter of
				// datamalico_server_dbquery::select();
			});

			function render_this_inner_page (page_num) // This page_num, is necessary for the jquery paging extension. This is the number of the rendered page.
			{
				$("#results_book").find("#page_content").datamalico(ajaxReturn).display_datagrid({
					template: {
						grid: '<table></table>'
						, row: '<tr></tr>'
						, cell: '<td></td>'
						, header_cell: '<th></th>'
					}
				});

				makeup_rows ('#results_book #page_content tr.row_class'); // put a make-up to rows
			}

			function require_another_page (page_num) // This page_num, is necessary for the jquery paging extension. This is the number of the clicked page.
			{
				pagination.page = page_num;     // Think also that the following datamalico_server_dbquery::select(), must specify the pagination param
				select_ajax_with_pagination ();             // recall the function where all this chunk takes place, so that a this paginate() can be run again.
			}
		}
		else
		{
			alert (ajaxReturn.metadata.returnMessage);
		}
	}
}

