<?php
$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
include_once $doc_root."/1001_addon/library/mil_/mil_.conf.php";
include_once $doc_root."/1001_addon/library/datamalico/datamalico_server_dbquery.lib.php";
?>

<style type="text/css">
	#display { background-color:#FFAA00; }
	#div_debug_display { background-color:yellow; }
	#datatable td { width:50px; height:10px; border:solid 1px #AAAAAA; }
	#datatable td input[type=text] { width:25px; height:10px; }
	#datatable td select { width:100px; height:10px; }

/* Rounded box*/
.roundedbox-4px {

	border: 1px solid rgba(0, 0, 0, 0.15);
	padding: 8.5px;
	margin: 0 0 9px;

	/* top-left */
	-moz-border-radius-topleft:             4px;
	-webkit-border-top-left-radius:         4px;
	-khtml-border-top-left-radius:          4px;
	border-top-left-radius:                 4px;

	/* top-right */
	-moz-border-radius-topright:            4px;
	-webkit-border-top-right-radius:        4px;
	-khtml-border-top-right-radius:         4px;
	border-top-right-radius:                4px;

	/* bottom-right */
	-moz-border-radius-bottomright:         4px;
	-webkit-border-bottom-right-radius:     4px;
	-khtml-border-bottom-right-radius:      4px;
	border-bottom-right-radius:             4px;

	/* bottom-left */
	-moz-border-radius-bottomleft:          4px;
	-webkit-border-bottom-left-radius:      4px;
	-khtml-border-bottom-left-radius:       4px;
	border-bottom-left-radius:              4px;
}

</style>

<!-- #####################################
display() -->
display() method (The first of the 12 methods you really need in datamalico).
<div id="display_div_1" class="roundedbox-4px">
</div>

<!-- #####################################
display_datagrid() -->
display_datagrid() method (The second of the 12 methods you really need in datamalico).
<div id="display_div_2" class="roundedbox-4px">
</div>

<!-- #####################################
paginate() -->
paginate() method (The third of the 12 methods you really need in datamalico).
<div id="display_div_3" class="roundedbox-4px">
	<div id="results_book">
		<div id="report"></div>
		<div class="pagination"></div>
		<div id="page_content"></div>
		<div class="pagination"></div>
	</div>
</div>

<div id="div_debug_display">
</div>

<script>


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
			pagination: pagination
		}
		, url: window.location.protocol + "//" + window.location.hostname + "/1001_addon/assets/snippets/tuto01-display/server.select_paginate.ajax.php"	// the path to reach your ajax responding server page which returns a json result of a mysql query.
			, success: on_success					// links the success case with the below function.
	});

	function on_success (data, textStatus, jqXHR)
	{
		var ajaxReturn = data;
		data = null;

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
						, render_this_inner_page: render_this_inner_page
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
				console.log(page_num);
				// $("#page").val(page_num);    // only if this hidden form elem exists in the HTML page.
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


</script>
