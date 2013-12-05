/** 
* @file
* Local javascript file: [+this_relative_file_path+]/lang/js/[+mil_lang+].js
*/

// ############################################################
// ############################################################
// ############################################################
// Init:
$(document).ready(init_document);
function init_document () 
{
	//console.log(page_params);
	$("#div_debug_display").empty();

	//$("#selectbutton").button().click(select_empty_ajax);	
	//$("#global_save_button").button().click(global_save_ajax);
	//$("#reset_button").button().click(reset_form);
	$("#research_button").button().click(select_ajax);

	/*
	pagination = {		// If you use the mil_help library and the mil_page class, per default this variable is set to $GLOBALS['pagination']['page'] and $GLOBALS['pagination']['perpage'] in library/datamalico/datamalico.conf.php
		page: 1
		, perpage: 5
	};
	*/

	get_from_ajax();

	//load_empty_form ();
}


// ############################################################
// ############################################################
// ############################################################
// Functions:
function get_from_ajax ()
{
	//console.log("get_from_ajax()");

	mil_ajax ({
		data: {
			pagination: pagination
			, master_page_params: page_params // the mil_page class populates the javascript page_params with the page_params(get and post), so that you can now send them to the ajax server page.
		}
		, url: window.location.protocol + "//" + window.location.hostname + "/[+this_relative_file_path+]/server.get_research_form.ajax.php"
		, success: on_success
	});

	function on_success (ajaxReturn, textStatus, jqXHR)
	{
		//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

		// ####################
		// WORK on character
		if (
			ajaxReturn.character.metadata.returnCode == "1_RESULT_DISPLAYED" ||
			ajaxReturn.character.metadata.returnCode == "X_RESULTS_DISPLAYED"
		)
		{
			$('#research_panel').datamalico(ajaxReturn.character).display_datagrid({
				template: {
					grid: '<table></table>'
					, row: '<tr></tr>'
					, cell: '<td></td>'
					, header_cell: '<th></th>'
				}
				, manipulation: "research"
			});
		}
		else
		{
			alert (ajaxReturn.character.metadata.returnMessage);
		}


		// ####################
		// WORK on attribute form
		if (
			ajaxReturn.attribute.metadata.returnCode === "1_RESULT_DISPLAYED" ||
			ajaxReturn.attribute.metadata.returnCode === "X_RESULTS_DISPLAYED"
		)
		{
			$('#data_form_div_attribute').datamalico(ajaxReturn.attribute).display_datagrid({
				columns_order: ["char_id", "attribute"]	
				, template: {
					grid: '<table></table>'
					, row: '<tr></tr>' // '<div></div>'
					, cell: '<td></td>' //'<span></span>'
					//, header_cell: '<th></th>'
				}
				, manipulation: "research"
			});

			makeup_rows('#data_form_div_attribute tr.row_class');

			//$('#data_form_div_attribute input:checkbox').attr('checked', true);

			select_ajax();
		}
		else
		{
			$('#data_form_div_attribute').emtpy();
			alert (ajaxReturn.attribute.metadata.returnMessage);
		}

	}
}


function select_ajax ()
{
	//console.log("select_ajax()");

	mil_ajax ({
		form_id: "research_form"
		, data: {
			pagination: pagination
			, master_page_params: page_params // the mil_page class populates the javascript page_params with the page_params(get and post), so that you can now send them to the ajax server page.
		}
		, url: window.location.protocol + "//" + window.location.hostname + "/[+this_relative_file_path+]/server.select.ajax.php"
		, success: on_success
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
			/*$('#page_content').datamalico(ajaxReturn).display_datagrid({
				template: {
				grid: '<table></table>'
				, row: '<tr></tr>'
				, cell: '<td></td>'
				, header_cell: '<th></th>'
				}
				});
				*/

				// #######################################
				// paginate()
				$("#res_ctnr #results_book")
				.find(".pagination")
				.datamalico(ajaxReturn)
				.paginate({
					report_ctnr: "report"
					, render_this_inner_page: render_this_inner_page // function (page_num) {...}
					, require_another_page: require_another_page // null // use null for a normal HTML link (Read the whole documentation of this method).
					, page_link_format: "?page={page}&perpage={perpage}"
					//, page_link_format: "-[page]-[perpage]"
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
					select_ajax();             // recall the function where all this chunk takes place, so that a this paginate() can be run again.
				}
		}
		else
		{
			alert (ajaxReturn.metadata.returnMessage);
		}
	}
}



function reset_form ()
{
	window.location = window.location.href;
}

