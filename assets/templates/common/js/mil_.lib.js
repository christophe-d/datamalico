/** 
* @file
* File where the additional functions are stored for the mil_ project (mil_.lib.js)
* The present file manages ajax handling.
*
* @author	Christophe DELCOURTE
* @version	1.0
* @date	2012
*/



$jq1001(document).ready(mil_init);
function mil_init () 
{
	$jq1001('#mil_alert_box').hide();
	$jq1001('#mil_problem_notification_box').hide();
}



// {{{ Layout lib:
// ################################################################################
// ################################################################################

/**
* This procedure is a cute alert box replacing the standard javascript alert() function.
*
* @param msg: {string} (mandatory) Is the message you want to display in the box.
* @param title: {string} (optional, default is $mil_lang_common['hey'] in library/lang/french.lang.php) Is the title of the box you want to set.
*
* Example of call:
* @code
* mil_alert ("Lorem ipsum dolor sit amet"); //alert ("Lorem ipsum dolor sit amet");
* mil_alert ("Lorem ipsum dolor sit amet", "My own title");
* @endcode
*
* @warning The mil_alert() function doesn't block the script like alert(). So: if you want to maker a redirection just after an alert box, you will have to do:
* @code
* // While the following works:
* alert ("Any message");
* window.location.href = "anotherpage.html"; // redirection
*
* // the following doesn't work: (because mil_alert doesn't block the script, and the redirection is immediately done)
* mil_alert ("Any message");
* window.location.href = "anotherpage.html"; // redirection
*
* // Thus you have to do:
* mil_alert ("Any message");
* $jq1001( "#mil_alert_box" ).on( "dialogclose", function( event, ui ) {
* 	window.location.href = "anotherpage.html";
* });
* @endcode
*/
function mil_alert ()
{
	// ########################
	// Params: (overloading with several params possible is considered)
	var msg = arguments[0];
	var title = "[+hey+]"; if (isset_notempty_notnull(arguments[1])) title = arguments[1];

	// ########################	
	// Work
	//$jq1001('#mil_alert_box').attr('title', title);
	$jq1001('#mil_alert_box #msg').html(msg);

	$jq1001( "#mil_alert_box" ).dialog({
		title: title
		, modal: true
		, draggable: false
		, resizable: false
		, height: 250
		, width: 350
		, position: { my: "center bottom", at: "center center", of: window }
		, buttons: {
			Ok: function() {
				$jq1001( this ).dialog( "close" ); // closes the box
			}
		}
		, show: {
			effect: "fade",
			duration: 1000
		}
		, hide: {
			effect: "fade",
			duration: 1000
		}
	});

	var JQUERY_ok_button = $jq1001('#mil_alert_box').parent().find('.ui-dialog-buttonpane').find('span:contains("Ok")').parent();
	var HTML_ok_button = JQUERY_ok_button.get(0);
	HTML_ok_button.focus();
}



/**
* This procedure allows to the user to notify a problem on the current page.
* This notification is sent by email.
* 
* @param type: {string} (default is problem) You can choose a value among:
* 	- problem
* 	- abuse
*/
function mil_problem_notification (type)
{
	// ########################
	// Params: (overloading with several params possible is considered)
	var msg = arguments[0];
	var title = "[+hey+]"; if (isset_notempty_notnull(arguments[1])) title = arguments[1];

	var mil_problem_notification_box_title;
	if (type === "problem")
	{
		mil_problem_notification_box_title = "[+mil_problem_notification_box_title+]"; //"Signaler un problème";
	}
	else if (type === "abuse")
	{
		mil_problem_notification_box_title = "[+mil_abuse_notification_box_title+]";
	}

	// ########################	
	// Work
	//$jq1001('#mil_alert_box').attr('title', title);
	//$jq1001('#mil_alert_box #msg').html(msg);

	$jq1001( "#mil_problem_notification_box" ).dialog({	
		modal: true
		, draggable: true
		, resizable: true
		, height: 350
		, width: 400
		, position: { my: "right bottom", at: "right bottom", of: window }
		, buttons: {
			"Envoyer": function() {
				var dialog_obj = $jq1001(this);
				mil_ajax ({
					form_id: "problem_form"
					, url: "1001_addon/assets/snippets/mil_problem_notification/server.email.ajax.php"
					, data: {
						type: type
						, master_page_params: page_params // the mil_page class populates the javascript page_params with the page_params(get and post), so that you can now send them to the ajax server page.
					}
					, success: on_success
				});

				function on_success (data, textStatus, jqXHR)
				{
					var ajaxReturn = data;
					data = null;

					//if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;

					dialog_obj.dialog( "close" ); // closes the box
				}
			}
		}
		, show: {
			effect: "fade",
			duration: 1000
		}
		, hide: {
			effect: "fade",
			duration: 1000
		}
		, close: function( event, ui ) {
			//console.log(event);

			if (!isset(event.metaKey)) // means that it not closed by the ok button
			{
				mil_alert("merci");
			}
		}
	});

	$jq1001("#mil_problem_notification_box").attr('title', mil_problem_notification_box_title);
	$jq1001("#ui-dialog-title-mil_problem_notification_box").html(mil_problem_notification_box_title);


	$jq1001("#mil_problem_notification_box #problem_msg_id").val("");
	$jq1001("#mil_problem_notification_box #problem_page_url_id").val(window.location.href);
	$jq1001("#mil_problem_notification_box #problem_page_pathname_id").val(window.location.pathname);
}


/**
* This function put a make-up to rows:
* 	- Every {selector} odd get the CSS class 'odd_row'
* 	- Every {selector} even get the CSS class 'even_row'
* 	- Every {selector}:
* 		- get the CSS class 'over_row' when the mouse is over the row.
* 		- lose the CSS class 'over_row' when the mouse goes out the row.
*
* @param selector: {string} (mandatory) Is the full selector to get HTML rows (tr) to be applyed.
*
* @return Nothing returned.
*
* Example of use:
* @code
* makeup_rows ('#mydiv #mytable tr.basicrowclass');
* @endcode
*/
function makeup_rows (selector)
{
	// Display:
	$jq1001(selector).filter(':odd').addClass("odd_row");
	$jq1001(selector).filter(':even').addClass("even_row");
	// datagrid mouseover row management:
	$jq1001(selector).mouseenter( function () { $jq1001(this).addClass("over_row"); } );
	$jq1001(selector).mouseleave( function () { $jq1001(this).removeClass("over_row"); } );
}


var lang = "[+mil_lang+]";

/**
* For tabs (in the admin part) Either selects the default tab, or the tab specified in hyperlink.
*
* @todo Not working yet.
*/
function default_tab_selection (params)
{
	//console.log("default_tab_selection");
	//console.log(params);
	//console.log(page_params);
	if (
		isset_notempty_notnull(params.level)
		|| isset_notempty_notnull(params.tabid)
	)
	{
		// switch to tab specified in hyperlink:
		//$jq1001('[id^="'+params.level+'"]').tabs('select', eval(params.tabid)); // jQuery UI http://api.jqueryui.com/1.8/tabs/#method-select
		$jq1001('[id^="'+params.level+'"]').tabs('select', params.tabid);
	}	
	else
	{
		// Level 1: tabs_menu
		if (isset_notempty_notnull(page_params.tabs_menu))
		{
			default_tab_selection ({
				level: "tabs_menu"	// begin of the id of the container
				, tabid: page_params.tabs_menu
			});
		}

		// Level 2: tabs_object 
		if (isset_notempty_notnull(page_params.tabs_object))
		{
			default_tab_selection ({
				level: "tabs_object"	// begin of the id of the container
				, tabid: page_params.tabs_object
			});
		}

		// Level 3: tabs_action 
		if (isset_notempty_notnull(page_params.tabs_action))
		{
			default_tab_selection ({
				level: "tabs_action"	// begin of the id of the container
				, tabid: page_params.tabs_action
			});
		}

		// Level 4: tabs_spec 
		if (isset_notempty_notnull(page_params.tabs_spec))
		{
			default_tab_selection ({
				level: "tabs_spec"	// begin of the id of the container
				, tabid: page_params.tabs_spec
			});
		}
	}
}

$("#accordion").accordion({ active: false });

/**
* mil_panel is an addition to jqueryui accordions. Whereas an accordion (jquery accordion) can only open 1 part of the accordion, mil_panel can open several at a time.
*
* See also: http://jqueryui.com/accordion/
*
* @param params (optional) {object}
* 	- active: (optional, default is [0]) {numerical array} Define active panels. Active means opened. Inactive means: closed.
* 	- unmoveable: (optional, default is []) {numerical array} Define unmoveable panels. Unmoveable means: cannot be opened or closed.
*
* @code
* $jq1001("#action_panel").mil_panel({
* 	active: [0]
* 	, unmoveable: [0]
* });
* @endcode
*/
$jq1001.fn.mil_panel = function (params)
{
	var classes = {
		panel_ctnr: "ui-accordion ui-accordion-icons ui-widget ui-helper-reset"
		, header: {
			inactive: "ui-accordion-header ui-helper-reset ui-state-default ui-corner-top ui-corner-bottom"
			, active: "ui-accordion-header ui-helper-reset ui-corner-top ui-accordion-header-active ui-state-active"
			, toggle: "ui-accordion-header-active ui-state-active ui-state-default ui-corner-bottom"
			, hover: "ui-state-hover"
			, unmoveable: "ui-accordion-icons"
		}
		, icon: {
			inactive: "ui-icon ui-icon-triangle-1-e"
			, active: "ui-icon ui-icon-triangle-1-s"
			, toggle: "ui-icon-triangle-1-e ui-icon-triangle-1-s"
			, unmoveable: "ui-icon ui-icon-pencil"
		}
		, content: {
			inactive: "ui-accordion-content  ui-helper-reset ui-widget-content ui-corner-bottom"
			, active: "ui-accordion-content  ui-helper-reset ui-widget-content ui-corner-bottom ui-accordion-content-active"
			, toggle: "ui-accordion-content-active"
		}
	};

	var get_config = function (params)
	{
		var config;

		if (isset_notempty_notnull(params))
		{
			//console.log("isset_notempty_notnull(params)");
			config = params;
		}
		else
		{
			//console.log("!isset_notempty_notnull(params)");
			config = {active: [0], unmoveable:[]};
		}

		if (!isset_notempty_notnull(config.active)) config.active = [0];
		if (!isset_notempty_notnull(config.unmoveable)) config.unmoveable = [];

		return config;
	};

	/**
	* For any h3 element (that is to say the bar of a panel)
	*/
	$jq1001.fn.panelToggle = function ()
	{
		//console.log ($jq1001(this).next().css("display"));
		//console.log ($jq1001(this).next());

		$jq1001(this).toggleClass(classes.header.toggle)
		.find("> .ui-icon").toggleClass(classes.icon.toggle).end()
		.next().slideToggle().toggleClass(classes.content.toggle); //slideToggle

		/*
		// Here a simple toggle should do the trick. But there is a bug from the original code, so I use the value css dipsplay to to know if show or hide.
		var state = $jq1001(this).next().css("display"); // display state of the content

		$jq1001(this).toggleClass(classes.header.toggle);
		$jq1001(this).find("> .ui-icon").toggleClass(classes.icon.toggle);

		if (state === "none")
		{
		$jq1001(this).next()
		.slideDown()
		.removeClass(classes.content.inactive + " " + classes.content.active)
		.addClass(classes.content.active); //slideToggle
		}
	else
		{
		$jq1001(this).next()
		.slideUp()
		.removeClass(classes.content.inactive + " " + classes.content.active)
		.addClass(classes.content.inactive); //slideToggle
		}
		*/
		//console.log ($jq1001(this).next().css("display"));
		//console.log ($jq1001(this).next());
	};

	var config = get_config (params);

	$jq1001(this).addClass(classes.panel_ctnr)
	.find("h3").addClass(classes.header.inactive)
	.next().addClass(classes.content.inactive)
	.hide();		// keep this hide, otherwise there is a motion bug


	// ##############
	// What panel should be unmoveable?
	$jq1001(this).find("h3").each(function (index, element)
	{
		//console.log(index);
		var in_unmoveable_at_position = $jq1001.inArray(index, config.unmoveable);

		// collapsible panels
		if (in_unmoveable_at_position === -1)
		{
			//console.log("Not in unmoveable: " + index);
			$jq1001(this)
			.hover(function() { $jq1001(this).toggleClass(classes.header.hover); })
			.prepend('<span class="' + classes.icon.inactive + '"></span>')
			.click(function() {
				$jq1001(this).panelToggle();
				return false;
				$jq1001(this)
				.toggleClass(classes.header.toggle)
				.find("> .ui-icon").toggleClass(classes.icon.toggle).end()
				.next().toggleClass(classes.content.toggle).slideToggle();

			}); //.on("click", $jq1001(this), panelToggle)

		}
		else
			// unmoveable panels
		{
			//console.log("In unmoveable: " + index);
			//$jq1001(this).prepend('<span class="' + classes.icon.unmoveable + '"></span>');
			$jq1001(this)
			.click(function () { return false; })
			.toggleClass(classes.header.unmoveable)
			.find("a").css("cursor", "text");

			var in_active_at_position = $jq1001.inArray(index, config.active);
			if (in_active_at_position === -1)
			{
				//console.log("But not in active: " + index + ", so we add it in active.");
				//config.active.push(index);
			}
		}
	});

	// ##############
	// What panel should be active?
	$jq1001(this).find("h3").each(function (index, element)
	{
		var in_active_at_position = $jq1001.inArray(index, config.active);
		if (in_active_at_position !== -1)
		{
			//$jq1001(this).show();
			//console.log($jq1001(this));
			$jq1001(this).panelToggle();
		}
	});

	return;

	// OK
	$jq1001(this).addClass(classes.panel_ctnr)
	.find("h3").addClass(classes.header.inactive)
	.hover(function() { $jq1001(this).toggleClass(classes.header.hover); })
	.prepend('<span class="' + classes.icon.inactive + '"></span>')
	.click(function() {
		$jq1001(this)
		.toggleClass(classes.header.toggle)
		.find("> .ui-icon").toggleClass(classes.icon.toggle).end()
		.next().toggleClass(classes.content.toggle).slideToggle();
		return false;
	})
	.next().addClass(classes.content.inactive)
	.hide();
};

/**
* Create a button
*/
$jq1001.fn.mil_create_button = function ()
{
	$jq1001(this).wrap('<span class="mil_button_bg"><span class="mil_button_leftandright"></span></span>');
};


/**
* Center a div on a screen
*
* @code
* $jq1001(element).center();
* @endcode
*
* @todo The good thing should be to center any element (and keep its content as it is) in its direct parent. Think alos to add params for horizontally and vertically centering.
*/
jQuery.fn.center = function () {
	this.css("position","absolute");
	this.css("top", Math.max(0, (($jq1001(window).height() - $jq1001(this).outerHeight()) / 2) + 
		$jq1001(window).scrollTop()) + "px");
	this.css("left", Math.max(0, (($jq1001(window).width() - $jq1001(this).outerWidth()) / 2) + 
		$jq1001(window).scrollLeft()) + "px");
	return this;
}

/**
* Found on http://coley.co/center-to-parent/
*
* Centers an element within it's parent.
* This method is an extension of jQuery: jQuery.centerToParent()
*
* @warning Do not use it to much. One of the workaround for this method would be to insert element into a div, and then center this div.
*
* Usage:
* @code
* To center along both x and y axes: $jq1001(el).centerToParent();
* To center along the x axis only: $jq1001(el).centerToParent("x");
* To center along the y axis only: $jq1001(el).centerToParent("y");
* @endcode
*
* For information about window and sizes:
* @code
* $jq1001(window).width()
* $jq1001(this).outerWidth()
* @endcode
*/
$jq1001.fn.centerToParent = function( method )
{
	var css = {
		position: 'relative'
	}

	var methods = {
		init : function() { 
			return private_methods.center($jq1001(this));
		},
		x : function() {
			return private_methods.center($jq1001(this), true, false);
		},
		y : function() { 
			return private_methods.center($jq1001(this), false, true);
		}
	}

	var private_methods = {
		center : function(els, x, y) {
			els.each(function(i){
				var el = $jq1001(this);
				var p = el.parent();
				p = p.is('body') ? $jq1001(window) : p;
				x = (typeof(x)==='undefined') ? true : x;
				y = (typeof(y)==='undefined') ? true : y;
				if(p.height() <= el.height()) {
					$jq1001.error("Selected element is larger than it's parent");
				} else if(y && x) {
					css['top'] = ((p.height() / 2) - (el.height() / 2)) + "px";
					css['left'] = ((p.width() / 2) - (el.width() / 2)) + "px";
				} else if(y) {
					css['top'] = ((p.height() / 2) - (el.height() / 2)) + "px";
				} else if(x) {
					css['left'] = ((p.width() / 2) - (el.width() / 2)) + "px";
				}
				el.css(css);
			});
			return els;
		}
	}


	if ( methods[method] ) {
		return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$jq1001.error( 'Method ' +  method + ' does not exist on jQuery.centerToParent' );
	}
};


// }}}


// {{{ For dev purposes :
// ################################################################################
// ################################################################################

function objectToString(o)
{
	var parse = function(_o){
		var a = [], t;
		for(var p in _o){
			//if(_o.hasOwnProperty(p)){
				t = _o[p];
				if(t && typeof t == "object"){
					a[a.length]= p + ":{ " + arguments.callee(t).join(", ") + "}";
				}
				else {
					if(typeof t == "string"){
						a[a.length] = [ p+ ": \"" + t.toString() + "\"" ];
					}
					else{
						a[a.length] = [ p+ ": " + t.toString()];
					}
				}
				//}
		}
		return a;
	};
	return "{" + parse(o).join(", ") + "}";
}

function debugWindow(txt)
{
	myWindow = window.open('','DebugWindow',"width=310,height=600,left=0,top=0");
	myWindow.document.write(txt + "<br />");
	window.focus();
	//if (myWindow.open) alert("is opened 02");
}


// debugDisplayTable ({array_or_object:my_array})
function debugDisplayTable2 (params)
{
	// Params and config
	var config = {};
	if (jQuery.type(params.indentlevel) === "undefined") config.indentlevel = 0;
	else config.indentlevel = params.indentlevel;

	// init
	var indent = "";
	for (i=0; config.identlevel <= i; i++)
	{
		indent += "&nbsp;&nbsp;&nbsp;&nbsp;";
	}

	// work
	var html = "";

	html += $jq1001.each(params.array_or_object, iterate_through_records);
	function iterate_through_records (key, value)
	{
		if (
			jQuery.type(value) === "object" ||
			jQuery.type(value) === "array"
		)
		{
			debugDisplayTable ( { array_or_object:value, indentlevel:config.indentlevel + 1 } );
		}
		else
		{
			html += indent + "[" + key + "] => " + value + "<br />";
		}

		return html;
	}

	return html;
}

function debugDisplayTable(obj, obj_name)
{
	var output = "";
	output += "<pre style='font-size:12px;'>" + obj_name + " :";
	output += print_r(obj);
	output += "</pre><hr>";

	return output;
}

function print_r(obj)
{
	var res;

	if (jQuery.type(obj) === "object")
	{
		res = JSON.stringify(obj,null,'\t').replace(/\n\r/g,'<br>').replace(/\n/g,'<br>').replace(/\r/g,'<br>').replace(/\t/g,'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	}
	else
	{
		obj = obj + "";
		res = obj.replace(/\r\n/g,'<br>').replace(/\r/g,'<br>').replace(/\n/g,'<br>').replace(/\t/g,'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	}

	return res;
}

// }}}


// {{{ Utilities:
// ################################################################################
// ################################################################################

function isset (param)
{
	return (param !== undefined && $jq1001.type(param) !== "undefined");
}

function isitnull (param)
{
	var is_it_null;
	var type = $jq1001.type (param);

	if (type === "null")
	{
		return true;
	}
	else if (type === "string")
	{
		is_it_null = is_it_null || (param.toLowerCase() === "null");
	}

	return is_it_null;
}

function isitempty (param)
{
	var is_it_empty;
	var type = $jq1001.type (param);

	if (type === "string")
	{
		is_it_empty = (param === "") || (param.length === 0);
	}
	else if (type === "array")
	{
		is_it_empty = (param.length === 0);
	}

	return is_it_empty;
}

function param_exists_and_not_empty (param)
{
	//return (param !== undefined && param != "" && param !== null && param.toString().toLowerCase() !== "null");
	return (param !== undefined && param !== "" && param !== null);
}

function exists_and_not_empty (param)
{
	param_exists_and_not_empty (param);
}

//function exists_and_not_empty (param)
//	exists {isset:1, notempty:1, notnull:1; notzero:1, notfalse:0}
function isset_notempty_notnull (param)
{
	//console.log("type: " + $jq1001.type (param));
	var is_it_set = isset (param);
	var is_it_empty = isitempty (param);
	var is_it_null = isitnull (param);
	/*var notempty = (param !== "") && (param.length !== 0);
	var notnull = (param !== null);
	if ($jq1001.type (param) === "string")
	{
	notnull = notnull && (param.toLowerCase() !== "null");
	notempty = notempty && (param !== "0");
	}
else if ($jq1001.type (param) === "number")
	{
	notnull = notnull && (param !== 0);
	}*/

	return (isset (param) && !is_it_empty && !is_it_null);
}

/**
* Structure of the BrowserDetect object found on http://www.quirksmode.org/js/detect.html
* See also http://api.jquery.com/jQuery.support/ for next browser feature investigations and http://api.jquery.com/jQuery.browser/ (deprecated)
*
* How to use it:
* @code
* Browser name: BrowserDetect.browser
* Browser version: BrowserDetect.version
* OS name: BrowserDetect.OS
* @endcode
*
* Last browsers: http://www.01net.com/telecharger/windows/Internet/navigateur/
*/
var BrowserDetect = {
	init: function () {
		this.browser = this.searchString(this.dataBrowser) || "An unknown browser"; // dataBrowser.identity is returned. // is a string
		this.version = this.searchVersion(navigator.userAgent)
		|| this.searchVersion(navigator.appVersion)
		|| "an unknown version"; // is a number
		this.OS = this.searchString(this.dataOS) || "an unknown OS"; // dataOS.identity is returned. // is a string
	},
	searchString: function (data) {
		for (var i=0;i<data.length;i++)	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if (dataString) {
				if (dataString.indexOf(data[i].subString) != -1)
					return data[i].identity;
			}
			else if (dataProp)
				return data[i].identity;
		}
	},
	searchVersion: function (dataString) {
		var index = dataString.indexOf(this.versionSearchString);
		if (index == -1) return;
		return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera",
			versionSearch: "Version"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes (6+)
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes (4-)
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.userAgent,
			subString: "iPhone",
			identity: "iPhone/iPod"
		},
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]

};
BrowserDetect.init();



(function () {
	var browserMessage = "[+YourBrowser+]";	 // You are using...
	var displayMessage = false;
	if (BrowserDetect.browser === "Chrome")
	{
		var newestVersion = 28;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += "[+WatchOutBrowserVersion+]";
			displayMessage = false;
		}
	}
	else if (BrowserDetect.browser === "Safari")
	{
		var newestVersion = 6;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += "[+WatchOutBrowserVersion+]";
			displayMessage = false;
		}
	}
	else if (BrowserDetect.browser === "Opera")
	{
		var newestVersion = 15;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += "[+WatchOutBrowserVersion+]";
			displayMessage = false;
		}
	}
	else if (BrowserDetect.browser === "Firefox")
	{
		var newestVersion = 22;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += "[+WatchOutBrowserVersion+]";
			displayMessage = false;
		}
	}
	else if (BrowserDetect.browser === "Explorer")
	{
		var newestVersion = 10;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += " [+WatchOutBrowserVersion+]";
			displayMessage = true;
		}
	}
	else if (BrowserDetect.browser === "Mozilla")
	{
		var newestVersion = 1;
		browserMessage += " " + BrowserDetect.browser + " " + BrowserDetect.version + ".";	 // You are using...
		if (BrowserDetect.version < newestVersion)
		{
			browserMessage += "[+WatchOutBrowserVersion+]";
			displayMessage = false;
		}
	}
	else
	{
		browserMessage += " [+WatchOutBrowserType+]";	 // You are using...
		displayMessage = true;
	}

	if (displayMessage === true)
	{
		alert (browserMessage);
		//alert(BrowserDetect.browser + " " + BrowserDetect.version + " " + BrowserDetect.OS);
		//alert($jq1001.type(BrowserDetect.browser) + " " + $jq1001.type(BrowserDetect.version) + " " + $jq1001.type(BrowserDetect.OS));
	}	
})();





// }}}


// {{{ Section for the mil_ Ajax:
// ################################################################################
// ################################################################################

/**
* It eases the ajax handling. It uses the jquery $jq1001.ajax() function.
*
* @param params: {object} Is an associative array, containing params for the ajax transaction.
* 	- form_id: (REQUIRED AND OPTIONAL: you must specify at least, form_id or data, both are possible too) {string} Id of the form you want to submit.
* 	- data: (REQUIRED AND OPTIONAL: you must specify at least, form_id or data, both are possible too) {object} You can add data even to forms or just send data alone.
* 		- ex: {hello: 'Good morning', bye: 'Good bye', alphabet:['a', 'b', 'c']}
* 		- ex: {delupsert: {html_ctnr_lastname: { t: "registered", f: { lastname: "Bond" }, c: { reg_id: 1 } } } }
* 			- In this case, the 'delupsert' from the HTML form is not overrided by this 'delupsert', but completes it.
* 		- Escape strings:
* 			- In this data param (not in a form text or textarea), you must escape \ by a \ and naturally escape " by \ if within "" or escape ' by \ if within ''
* 			- In such case, having a textarea in wich you copy-paste, or having such a data string so escaped, the server will receive the same.
* 			- It has been tested successfully with the following string: "! \"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~ `‚ƒ„…†‡ˆ‰Š‹ŒŽ‘’“”•–—˜™š›œžŸ ¡¢£¤¥¦§¨©ª«¬®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿ€";
* 				- ==> I also tried to insert CR and LN into a textarea and variables and this is kept into the db, and displayed if you fetch this from db
* 					and display it in a div or in a textarea
* 				- ==> I just note that 2 chars are rendered in a different maner : DEL (%7F) and NBSP (%A0)
* 				- ==> In conclusion : except for DEL and NBSP, if you write something into a textarea or a variable (which must escaped for quotes and \),
* 					you will have the same result when fetching from the db it and displaying it onto the web page.
* 				- GET or POST you get the same strings on the server side
* 	- url: (optional if a form and form action is defined) {string} Target url of the ajax transaction. Default is the form action, but you can overwrite it with this
* 		url param.
* 		- In the mil_ system, it could be something like: "[+this_relative_file_path+]/server_script.ajax.php"
* 	- method: (optional) {string} Method to use for the ajax transcation.
* 		- 2 values are possible : 'POST', 'GET'
* 		- If you don't specify it, this is the form method defined, or if no form is specifyed as form_id, then , the default method is 'POST'.
* 	- success: (optional) {function} Specify what must be executed as a callback on ajax transaction success. Params of the functions are those of the success 
* 		function invoked by jQuery.ajax() :
* 		- data: Is the response of the server. It is handled as:
* 			- text if Content-type of response is text/plain
* 			- html if Content-type of response is text/html
* 			- json if Content-type of response is application/json
* 			- See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
* 		- textStatus: See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
* 		- jqXHR: See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
* 	- error: (optional) {function} Specify what must be executed as a callback on ajax transaction error. Params of the functions are those of the success 
* 		function invoked by jQuery.ajax() :
* 		- jqXHR: See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
* 		- textStatus: See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
* 		- errorThrown: See the jquery documentation for more information: http://api.jquery.com/jQuery.ajax/
*
* Example of use:
* @code
* mil_ajax ({
* 	form_id: "select_objects_form"
* 	, data: {
* 		temp_insert_id: {		// will be an associative array in the PHP page: $_POST['temp_insert_id']
* 			field: "mil_d_registered.reg_id"	// will be: $_POST['temp_insert_id']['field']
* 			, value: temp_insert_id
* 		}
* 		, hello: 'Good morning'
* 		, bye: 'Good bye'
* 		, alphabet:['a', 'b', 'c']			// will be a numerical array: $_POST['alphabet']
* 	}
* 	, success: on_success
* 	});
*
* function on_success (data, textStatus, jqXHR)
* {
* 	var ajaxReturn = data;	// so many things are called data in various libraries (jquery...) that I prefer rename data to ajaxReturn
* 	data = null;		// and destroy data in order to free memory
* 
* 	//mil_ajax_debug_and_see_raw_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display); return;
* 	//mil_ajax_debug_and_see_object_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display);
* 
* 	// ####################
* 	// WORK
* 	alert (ajaxReturn.metadata.returnMessage);
* }
* @endcode
*/
function mil_ajax (params)
{
	// ############################
	// Params and config
	config = check_params(params);
	function check_params(params)
	{
		//console.log($jq1001.type(params));
		//console.log($jq1001.type(params.form_id));
		//console.log($jq1001.type(params.data));
		if (
			!isset_notempty_notnull (params.form_id)
			&& !isset_notempty_notnull (params.data)
		)
		{
			millib_Exception ("the form_id in the mil_ajax () function must be fullfilled", "1205211121", "WARN");
			alert ("the form_id in the mil_ajax () function must be fullfilled");
		}
		if (isset_notempty_notnull (params.form_id))
		{
			if (!isset_notempty_notnull (params.url)) params.url = $jq1001('#' + params.form_id).attr("action");
			if (!isset_notempty_notnull (params.method)) params.method = $jq1001('#' + params.form_id).attr("method");
		}
		else
		{
			if (!isset_notempty_notnull (params.url)) params.url = "";
			if (!isset_notempty_notnull (params.method)) params.method = "POST";
		}

		if (!isset_notempty_notnull (params.data)) params.data = {};
		if (!isset_notempty_notnull (params.success)) params.success = function () {};
		if (!isset_notempty_notnull (params.error)) params.error = function () {};

		return params;
	}


	// ############################
	// Work
	config.data = $jq1001('#' + config.form_id).serialize () + '&' + $jq1001.param (config.data);

	//console.log(config);
	//console.log("mil_ajax (), data to be sent:" + config.data);

	var request = $jq1001.ajax({url: config.url
		, type : config.method
		, data: config.data
		, success : config.success
		//, dataFilter : on_dataFilter
		, error : error
		, timeout : 45000
		, isLocal : true
		, beforeSend: mil_xhrPool_beforeSend
		, complete : mil_xhrPool_complete
	});

	function error (jqXHR, textStatus, errorThrown)
	{
		//console.log (jqXHR);
		//console.log (textStatus);
		//console.log (errorThrown);

		mil_Exception ({adminMessage: "mil_ajax error, the reason is:" + textStatus, errorId: "1207251800", errorLevel: "WARN"});
		alert ("[+ajax_Failure_error+]");
		config.error ();

		return;
	}
}



// call examples : 
// 	mil_Exception ({adminMessage: "test", errorId: "1207251825", errorLevel: "WARN"});
// 	mil_Exception ({adminMessage: "test", errorId: "1207251825", errorLevel: "WARN", userMessage: "We are sorry. An error occured."});
/**
* Send an exception to the server via an ajax transaction.
*
* @param params: (optional) {object} This is an associative array containing params of this exception. All params are optional, but the fullURL (window.location.href) 
* 	is anyway sent to the server and logged into log files (if the loglevel is big enough to be logged).
* 	- adminMessage: (optional) {string} Is the message that must be written in the log files. Moreover, the fullURL (window.location.href) is also sent to 
* 		the server and logged.
* 	- errorId: (optional) {string} You can specify an error id.
* 	- errorLevel: (optional) {string} Specify the error level. You can choose among the following values:
* 		- FATAL, ERROR, WARN, INFO, DEBUG, TRACE
* 	- userMessage: (optional) {string} Is the message returned by the server to the client page. If this param is specifyed, then, at the end of the ajax transaction
* 		(of sending this exception), the end-user has an alert box with this message.
*
* See also:
* - mil_exception.ajax.php
* - mil_exception.class.php
*
* Example of use:
* @code
* mil_Exception ({adminMessage: "In dco_paginate_procedural() params.display.pages_className has a bad value"
* 		, errorId: "1207251825"
* 		, errorLevel: "WARN"
* 		, userMessage: "We are sorry. An error occured."
* 		});
* @endcode
*/
function mil_Exception (params)
{
	// ############################
	// Params and config
	config = check_params(params);
	function check_params(params)
	{
		if (!isset_notempty_notnull (params)) params = {};
		params.fullURL = window.location.href;
		if (!isset_notempty_notnull (params.adminMessage)) params.adminMessage = "No admin message specified in the code";
		if (!isset_notempty_notnull (params.errorId)) params.errorId = "NO_ERROR_NUM";
		if (!isset_notempty_notnull (params.errorLevel)) params.errorLevel = "WARN";
		if (!isset_notempty_notnull (params.userMessage)) params.userMessage = "";

		return params;
	}


	// ############################
	// Work

	//console.log("mil_Exception, config :");
	//console.log(config);
	var data = {"fullURL" : config.fullURL
		, "adminMessage" : config.adminMessage
		, "errorId" : config.errorId
		, "errorLevel" : config.errorLevel
		, "userMessage" : config.userMessage
	};

	mil_ajax ({data : data
		, success : on_success
		, url : "1001_addon/library/mil_exception.ajax.php"
		, method : "post"
	});

	function on_success (data, textStatus, jqXHR)	
	{
		//console.log("mil_Exception (), on_success, data as object :");
		//console.log(data);
		if (data.metadata.returnCode === "DISPLAY_USER_MESSAGE")
		{
			alert (data.metadata.returnMessage);
		}
	}
}




/**
* This function is very usefull in order to debug an ajax answer.
* Using the PHP mil_page class (see mil_page.class.php), you can receive 2 kinds of answer:
* 	- a string, if you set the mil_page::ajax to false
* 	- a json well formed string (understood as a javascript object), if you set mil_page::ajax to true.
*
* @param reset {bool} (optional, default is true) If you want to empty the div_debug_display zone before writing debug info into it.
*
* @code
* if (!mil_ajax_debug (ajaxReturn, textStatus, jqXHR, "div_debug_display")) return;
* @endcode
*/
function mil_ajax_debug (ajaxReturn, textStatus, jqXHR, div_debug_display)
{

	reset = arguments[4]; if ($jq1001.type(reset) === "undefined") reset = true;

	var is_json = false;
	// if the server has returned a string, that is to say a non ajax well formed object:
	if ($jq1001.type (ajaxReturn) === "string")
	{
		mil_ajax_debug_and_see_raw_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display, reset); //return;
		is_json = false;
	}
	else
	{
		// if the server has returned an object, that is to say a well formed json object (string actually, but well formed, using the PHP json_encode() function):
		mil_ajax_debug_and_see_object_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display, reset);
		is_json = true;
	}

	return is_json;
}

// ####################
// DEBUG RAW ajaxReturn : For debugging when you get ajaxReturn from server NOT under Content-type: application/json :
//mil_ajax_debug_and_see_raw_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display); return;
function mil_ajax_debug_and_see_raw_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display)
{
	reset = arguments[4]; if ($jq1001.type(reset) === "undefined") reset = true;

	//console.log("DEBUG RAW (string) ajaxReturn from server:");
	//console.log(ajaxReturn);
	//console.log($jq1001.type(ajaxReturn));
	if (reset === true) $jq1001('#' + div_debug_display).html("");
	$jq1001("#"+div_debug_display).append (ajaxReturn);	// if the //console.log works and displays the object, but not this line, then it means that a JSON object is returned, and not a string. That means that the server page has not error. In order to display something into the div_debug_display, then, do echo into server page, so that the output will be a string and not a JSON object.
}

// ####################
// OBJECT RAW ajaxReturn after json : For debugging when you get ajaxReturn from server UNDER Content-type: application/json :
//mil_ajax_debug_and_see_object_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display);
function mil_ajax_debug_and_see_object_server_results (ajaxReturn, textStatus, jqXHR, div_debug_display)
{
	reset = arguments[4]; if ($jq1001.type(reset) === "undefined") reset = true;

	//console.log("OBJECT JSON ajaxReturn from server:");
	//console.log(ajaxReturn);

	if (reset === true) $jq1001('#' + div_debug_display).html("");

	//$jq1001("#"+div_debug_display).empty();
	if (!isset_notempty_notnull(ajaxReturn)) ajaxReturn = {};
	if (!isset_notempty_notnull(ajaxReturn.metadata)) ajaxReturn.metadata = {};

	if (isset_notempty_notnull(ajaxReturn.metadata))
	{
		if (!isset_notempty_notnull(ajaxReturn.metadata.sql)) ajaxReturn.metadata.sql = "";
		if (isset_notempty_notnull(ajaxReturn.metadata.sql_query))
		{
			$jq1001("#"+div_debug_display).append(print_r (ajaxReturn.metadata.sql_query));
		}
	}
	$jq1001("#"+div_debug_display).append(debugDisplayTable (ajaxReturn, "OBJECT JSON ajaxReturn from server:"));
}

/**
* Tests if there are ajax requests running in the mil
*/
function are_mil_ajax_running ()
{
	if ($jq1001.mil_xhrPool.length <= 0) return false;
	else return true;
}

/*
$jq1001.ajaxSetup({
beforeSend: mil_xhrPool_beforeSend
, complete : mil_xhrPool_complete
});
*/

function mil_xhrPool_beforeSend (jqXHR)
{
	$jq1001.mil_xhrPool.push(jqXHR);
	//console.log("ajaxSetup.beforeSend, $jq1001.mil_xhrPool.length: " + $jq1001.mil_xhrPool.length);
	//console.log($jq1001.mil_xhrPool);

	if ($jq1001.mil_xhrPool.length === 1)
	{
		mil_display_waitingModalBox (); // activate the modal wainting box only for the first ajax request
		//$jq1001(document).on ("keydown", mil_listen_to_ESC_down);
	}
}
function mil_xhrPool_complete (jqXHR)
{
	//console.log("mil_xhrPool_complete");
	//var index = $jq1001.mil_xhrPool.indexOf(jqXHR);	// http://stackoverflow.com/questions/3629183/why-doesnt-indexof-work-on-an-array-ie8
	var index = $jq1001.inArray (jqXHR, $jq1001.mil_xhrPool);

	if (index > -1) {
		$jq1001.mil_xhrPool.splice(index, 1);
	}
	//console.log("ajaxSetup.complete, $jq1001.mil_xhrPool.length: " + $jq1001.mil_xhrPool.length);
	//console.log($jq1001.mil_xhrPool);

	if ($jq1001.mil_xhrPool.length <= 0)
	{
		mil_hide_waitingModalBox (); // deactivate the modal wainting box only for the last ajax request
		//$jq1001(document).off ("keydown", mil_listen_to_ESC_down);
	}
}

function mil_display_waitingModalBox ()
{
	//console.log("mil_display_waitingModalBox");
	//ui-widget-overlay --> position:fixed
	//div mère role="dialog" --> fixed

	//console.log("#dialog-modal type: " + $.type($("#dialog-modal")));
	//console.log($("#dialog-modal"));
	//$('body').prepend ('<div id="dialog-modal" title="Please wait"></div>');
	$('body').prepend ("<div id=\"dialog-modal\" title=\"[+pleaseWait+]\"></div>");
	$('#dialog-modal').append('<p"><img src="' + window.location.protocol + "//" + window.location.hostname + "/" + '1001_addon/assets/templates/common/img/wait.gif" /></p>');

	$("#dialog:ui-dialog").dialog("destroy"); // a workaround for a flaw in the demo system. See the page: http://dev.jqueryui.com/ticket/4375
	$("#dialog-modal").dialog({
		height: 140
		, width: 250
		, modal: true
		, draggable: false
		, resizable: false
		, close: function(event, ui) 
		{
			$.mil_xhrPool.abortAll ();
		}
	});

	$("#dialog-modal").parent("div").css("position", "fixed");
	$("#dialog-modal").css("height", "25px");
	$(".ui-widget-overlay").css("position", "fixed");
	$(".ui-widget-overlay").css("background", "#666 url('1001_addon/assets/templates/common/img/phpThumb_generated_thumbnailpng.png') 50% 50% repeat");

	return;

	//console.log("mil_display_waitingModalBox");


	function mil_xhrPool_abortAll_click (event)
	{
		event.stopImmediatePropagation();
		//console.log("click on X");
		$.mil_xhrPool.abortAll ();
	}

	popup('loadingWheel');

	$("#waitingModalBox .mil_button_bg").css('margin', '0px');
	$("#waitingModalBox .mil_button_bg").css('width', '100%');
	$("#loadingWheel").css('top', '200px');

	function toggle(div_id, open_close)
	{
		var el = document.getElementById(div_id);
		if ( open_close == 'popup' ) {	el.style.display = 'block';}
		else if ( open_close == 'popdown' ){el.style.display = 'none';}
	}
	function blanket_size(popUpDivVar)
	{
		if (typeof window.innerWidth != 'undefined') {
			viewportheight = window.innerHeight;
		} else {
			viewportheight = document.documentElement.clientHeight;
		}
		if ((viewportheight > document.body.parentNode.scrollHeight) && (viewportheight > document.body.parentNode.clientHeight)) {
			blanket_height = viewportheight;
		} else {
			if (document.body.parentNode.clientHeight > document.body.parentNode.scrollHeight) {
				blanket_height = document.body.parentNode.clientHeight;
			} else {
				blanket_height = document.body.parentNode.scrollHeight;
			}
		}
		var blanket = document.getElementById('blanket');
		blanket.style.height = blanket_height + 'px';
		var popUpDiv = document.getElementById(popUpDivVar);
		popUpDiv_height=blanket_height/2-40;//40 is half popup's height
		popUpDiv.style.top = popUpDiv_height + 'px';
	}
	function box_positioning(popUpDivVar)
	{
		if (typeof window.innerWidth != 'undefined') {
			viewportwidth = window.innerHeight;
		} else {
			viewportwidth = document.documentElement.clientHeight;
		}
		if ((viewportwidth > document.body.parentNode.scrollWidth) && (viewportwidth > document.body.parentNode.clientWidth)) {
			window_width = viewportwidth;
		} else {
			if (document.body.parentNode.clientWidth > document.body.parentNode.scrollWidth) {
				window_width = document.body.parentNode.clientWidth;
			} else {
				window_width = document.body.parentNode.scrollWidth;
			}
		}
		var popUpDiv = document.getElementById(popUpDivVar);
		window_width=window_width/2-40;//40 is half popup's width
		popUpDiv.style.left = window_width + 'px';
	}

	function popup(windowname)
	{
		blanket_size(windowname);
		box_positioning(windowname);
		//toggle('blanket', "popup");
		//toggle(windowname, "popup");		
	}

	function popdown(windowname)
	{
		//toggle('blanket', "popdown");
		//toggle(windowname, "popdown");		
	}	
}

function mil_hide_waitingModalBox ()
{
	//console.log("mil_hide_waitingModalBox");
	jQuery("#dialog-modal").dialog("close");
	$jq1001("#dialog-modal").parent("div").remove();
	$jq1001("#dialog-modal").remove();	// necessary, because, the .dialog("close") doesn't destroy my original container "#dialog-modal"
	//console.log("#dialog-modal type: " + $jq1001.type($jq1001("#dialog-modal")));
	//console.log($jq1001("#dialog-modal"));
	return;
	//console.log("mil_hide_waitingModalBox");
	//$jq1001("#mil_xhrPool_abortAll").off ("click");
	//popdown('loadingWheel');
	$jq1001('#waitingModalBox').remove();
}


function mil_listen_to_ESC_down (event)
{
	if (event.which === 27)	// ESC
	{
		//console.log("listen_to_ESC_down");
		$jq1001.mil_xhrPool.abortAll ();
	}
}


$jq1001.mil_xhrPool = [];
$jq1001.mil_xhrPool.abortAll = function() {
	$jq1001(this).each(function(idx, jqXHR) {
		jqXHR.abort();
	});
	$jq1001.mil_xhrPool.length = 0
};

// }}}


// {{{ Encoding:
// #############################################################
// #############################################################

// Interface with php.


/**
* Is my equivalent for PHP rawurlencode()
*/
function rawurlencode(s)
{
	s = encodeURIComponent(s);
	return s.replace(/~/g,'%7E').replace(/%20/g,'+'); // Avant PHP 5.3.0, rawurlencode encodait les tildes (~) suivant la » RFC 1738.
	// .replace(/%5B/g, '[').replace(/%5D/g, ']');	// just in case
}

/**
* Is my equivalent for PHP rawurldecode()
*/
function rawurldecode(s)
{
	s = decodeURIComponent(s);
	return s.replace(/%7E/g,'~'); // Avant PHP 5.3.0, rawurlencode encodait les tildes (~) suivant la » RFC 1738.
}


/**
*/
function mixed_rawurldecode (mixed)
{
	if (jQuery.type(mixed) === "boolean") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "number") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "function") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "string") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "array")
	{
		var function_return;
		//foreach (mixed as $key => $val)
		$jq1001.each(mixed, iterate_through_array);
		function iterate_through_array (key, value)
		{
			function_return[key] = mixed_rawurldecode(value);
		}
		return function_return;
	}

	if (jQuery.type(mixed) === "object") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "date") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "regexp") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "null") return rawurldecode(mixed);

	if (jQuery.type(mixed) === "undefined") return rawurldecode(mixed);
}

/**
*/
function mixed_convert_uudecode (mixed)
{
	if (jQuery.type(mixed) === "boolean") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "number") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "function") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "string") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "array")
	{
		var function_return;
		//foreach (mixed as $key => $val)
		$jq1001.each(mixed, iterate_through_array);
		function iterate_through_array (key, value)
		{
			function_return[key] = mixed_convert_uudecode(value);
		}
		return function_return;
	}

	if (jQuery.type(mixed) === "object") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "date") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "regexp") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "null") return convert_uudecode(mixed);

	if (jQuery.type(mixed) === "undefined") return convert_uudecode(mixed);
}


/**
* See https://github.com/kvz/phpjs/blob/master/_workbench/strings/convert_uudecode.js
*/
function convert_uudecode (str) 
{
	// http://kevin.vanzonneveld.net
	// +   original by: Ole Vrijenhoek
	// +   bugfixed by: Brett Zamir (http://brett-zamir.me)
		// -    depends on: is_scalar
		// -    depends on: rtrim
		// *     example 1: convert_uudecode('+22!L;W9E(%!(4\"$`\n`');
		// *     returns 1: 'I love PHP'

		// Not working perfectly

		// shortcut
		var chr = function (c) {
			return String.fromCharCode(c);
		};

		if (!str || str=="") {
			return chr(0);
		} else if (!this.is_scalar(str)) {
			return false;
		} else if (str.length < 8) {
			return false;
		}

		var decoded = "", tmp1 = "", tmp2 = "";
		var c = 0, i = 0, j = 0, a = 0;
		var line = str.split("\n");
		var bytes = [];

		for (i in line) {
			c = line[i].charCodeAt(0);
			bytes = line[i].substr(1);

			// Convert each char in bytes[] to a 6-bit
			for (j in bytes) {
				tmp1 = bytes[j].charCodeAt(0)-32;
				tmp1 = tmp1.toString(2);
				while (tmp1.length < 6) {
					tmp1 = "0" + tmp1;
				}
				tmp2 += tmp1
			}

			for (i=0; i<=(tmp2.length/8)-1; i++) {
				tmp1 = tmp2.substr(a, 8);
				if (tmp1 == "01100000") {
					decoded += chr(0);
				} else {
					decoded += chr(parseInt(tmp1, 2));
				}
				a += 8;
			}
			a = 0;
			tmp2 = "";
		}
		return this.rtrim(decoded, "\0");
}

// }}}


// {{{ Section for the mil_ Errors and Exceptions and logs (and email):
// ################################################################################
// ################################################################################


var phpAjaxLibrary = "1001_addon/library/";

var millib_Log_Success = function(o)
{
	if(o.responseText !== undefined)
	{
		if (o.status == 200)
		{
			//alert(o.responseText);
			var ajaxReturn = JSON.parse(o.responseText);
			if (ajaxReturn.returnCode == "DISPLAY_USER_MESSAGE")
			{
				alert(ajaxReturn.returnMessage);
			}
		}
	}
};

var millib_Log_Failure = function(o) {};

var millib_Log_callback =
{
	success:millib_Log_Success,
	failure:millib_Log_Failure,
	argument:['foo','bar'],
	timeout: 30000  /* in milliseconds */
};


function millib_Exception(adminMessage, errorId, errorLevel)
{
	userMessage = "";
	millib_Exception ("adminMessage Attention Exception", "1201191024", "WARN", userMessage);
}

//invoke : millib_Exception ("adminMessage Attention Exception", "1201181024", "WARN"[, "An unknown error has occured."]);
function millib_Exception(adminMessage, errorId, errorLevel, userMessage)
{
	thisPage = window.location.href;
	var postData;

	var sUrl = phpAjaxLibrary + "millib_Exception_ajax.php";
	postData = "fullURL=" + thisPage;
	postData += "&adminMessage=" + adminMessage;
	postData += "&errorId=" + errorId;
	postData += "&errorLevel=" + errorLevel;
	postData += "&userMessage=" + userMessage;
}

// }}}


// {{{ Time handling:
// ################################################################################
// ################################################################################


/**
* Returns a unique_time_id
* @return {string} {year} + "" + {month} + "" + {day} + "" + {hour} + "" + {minute} + "" + {second} + "" + {millisec}
* @code
* - ex: 20120806085027531
* @endcode
*/
function get_unique_time_id ()
{
	// goes up to 99 thousand of billions
	// va jusqu'à 99 mille milliards
	sleep(1);
	var d = new Date();

	var year = d.getFullYear();
	var month = d.getMonth() + 1; if (month < 10) month = "0"+month;
	var day = d.getDate(); if (day < 10) day = "0"+day;

	var hour = d.getHours(); if (hour < 10) hour = "0"+hour;
	var minute = d.getMinutes(); if (minute < 10) minute = "0"+minute;
	var second = d.getSeconds(); if (second < 10) second = "0"+second;

	var millisec = d.getMilliseconds();
	if (millisec < 10) millisec = "0"+millisec;
	if (millisec < 100) millisec = "00"+millisec;

	var iso_now = year + "" + month + "" + day + "" + hour + "" + minute + "" + second + "" + millisec;

	return iso_now;
}


/**
* Returns today's date in the iso format.
* @return {string} {year} + "-" + {month} + "-" + {day}
* @code
* - ex: 2012-08-06
* @endcode
*/
function get_today ()
{
	// goes up to 99 thousand of billions
	// va jusqu'à 99 mille milliards
	sleep(1);
	var d = new Date();

	var year = d.getFullYear();
	var month = d.getMonth() + 1; if (month < 10) month = "0"+month;
	var day = d.getDate(); if (day < 10) day = "0"+day;

	var hour = d.getHours(); if (hour < 10) hour = "0"+hour;
	var minute = d.getMinutes(); if (minute < 10) minute = "0"+minute;
	var second = d.getSeconds(); if (second < 10) second = "0"+second;

	var millisec = d.getMilliseconds();
	if (millisec < 10) millisec = "0"+millisec;
	if (millisec < 100) millisec = "00"+millisec;

	var iso_now = year + "-" + month + "-" + day;

	return iso_now;
}

// }}}


// {{{ Forms handling:
// ################################################################################
// ################################################################################

/**
* Transform data of a form into a JSON format.
*
* Get on http://jsfiddle.net/sxGtM/3/ and http://stackoverflow.com/questions/1184624/convert-form-data-to-js-object-with-jquery
*
* Example of use:
* @code
* $jq1001myjson = $jq1001('#formid').serializeArray();
* @endcode 
*/
$jq1001.fn.serializeObject = function()
{
	var o = {};
	var a = this.serializeArray();
	$jq1001.each(a, function() {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};


/**
* Transform data of a form into a JSON format.
*
* Get on http://stackoverflow.com/questions/1184624/convert-form-data-to-js-object-with-jquery
*
* Example of use:
* @code
* $jq1001myjson = $jq1001('#formid').serializeArray();
* @endcode 
*/
(function($jq1001){
	$jq1001.fn.serializeObject2 = function(){

		var self = this,
		json = {},
		push_counters = {},
		patterns = {
			"validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
			"key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
			"push":     /^$/,
			"fixed":    /^\d+$/,
			"named":    /^[a-zA-Z0-9_]+$/
		};


		this.build = function(base, key, value){
			base[key] = value;
			return base;
		};

		this.push_counter = function(key){
			if(push_counters[key] === undefined){
				push_counters[key] = 0;
			}
			return push_counters[key]++;
		};

		$jq1001.each($jq1001(this).serializeArray(), function(){

			// skip invalid keys
			if(!patterns.validate.test(this.name)){
				return;
			}

			var k,
			keys = this.name.match(patterns.key),
			merge = this.value,
			reverse_key = this.name;

			while((k = keys.pop()) !== undefined){

				// adjust reverse_key
				reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');

				// push
				if(k.match(patterns.push)){
					merge = self.build([], self.push_counter(reverse_key), merge);
				}

				// fixed
				else if(k.match(patterns.fixed)){
					merge = self.build([], k, merge);
				}

				// named
				else if(k.match(patterns.named)){
					merge = self.build({}, k, merge);
				}
			}

			json = $jq1001.extend(true, json, merge);
		});

		return json;
	};
})(jQuery);



/**
* Prohibit all non digit characters: \D
*
* @param text: {string} (mandatory) Text to be checked.
*
* @return replacement: {string} (mandatory) The cleaned text.
*/
function only_digits_strict (text)
{
	var replacement = text.replace(/\D/g,"");

	//var filter = /\D/;
	//if (filter.test(text)) alert("[+only_digits_are_allowed+]");

	return replacement;
}


/**
* Allows only digits and negative sign, comma, dot, space.
*
* @param text: {string} (mandatory) Text to be checked.
*
* @return replacement: {string} (mandatory) The cleaned text.
*/
function only_digits_plus (text)
{
	var replacement = text.replace(/[^-,.0-9 ]*/g,""); // allow only negative sign, comma, dot, space and numbers
	// or ^(\d|,)*\.?\d*$

	//var filter = /\D/;
	//if (filter.test(text)) alert("[+only_digits_are_allowed+]");

	return replacement;
}

/*
* Prohibit all non digit characters: \D
* Popup an alert box if needed, but no correction is done.
*
* @param obj: {DOM object} (mandatory) Object to be checked.
*/
function only_digits_old (obj)
{
	var text = obj.value;
	var replacement = text.replace(/\D/g,"");
	obj.value = replacement;

	var filter = /\D/;
	if (filter.test(text)) alert("[+only_digits_are_allowed+]");
}

/**
*/
function check_email_while_typing (obj)
{
	var replacement = obj.value.replace(/[\s]/g, ""); // any white space character
	obj.value = replacement;
}

/**
*/
function is_email_address (email)
{
	// in php in modx:
	// 	     /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i
	var filter = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	if (!filter.test(email)) return false; // not valid
	else return true; // valid
}


/**
*/
function check_zipcode(input_id, select_country_id)
{
	var zipcode = document.getElementById(input_id).value;

	if (zipcode == "")
	{
		millib_notifyErrorToUser( "[+please_input_zipcode+]", input_id + "_err_container");
		return;
	}

	// if France
	if (
		document.getElementById(select_country_id).value == 76 ||
		document.getElementById(select_country_id).value == 77 ||
		document.getElementById(select_country_id).value == 78 ||
		document.getElementById(select_country_id).value == 79 ||
		document.getElementById(select_country_id).value == 89 ||
		document.getElementById(select_country_id).value == 140 ||
		document.getElementById(select_country_id).value == 189
	)
	{
		var filter = /\D/g;
		if ( filter.test(zipcode) ||  zipcode.length != 5) {
			millib_notifyErrorToUser( "[+only_5_digits_in_zip+]", input_id + "_err_container");
		}
	}


	// if Belgium, Switzerland, Luxembourg
	if (
		document.getElementById(select_country_id).value == 22 || 	// Belgium
		document.getElementById(select_country_id).value == 216 ||	// Switzerland
		document.getElementById(select_country_id).value == 130	//Luxembourg
	)
	{
		var filter = /\D/g;
		if ( filter.test(zipcode) ||  zipcode.length != 4) {
			millib_notifyErrorToUser( "[+only_4_digits_in_zip+]", input_id + "_err_container");
		}
	}

	return;
}

/**
* This function formats the $jq1001(this) jquery element, according to the parameter country_id_jelem
*
* @param country_id_jelem (mandatory) {jquery element} Must be the 'select' element where the country is specifyed.
*/
$jq1001.fn.phone_format = function (country_id_jelem)
{
	var frequency_separator;
	var separator;

	frequency_separator = 2;
	separator = " ";

	var num = $jq1001(this).val();
	var num_only_digits = num.replace(/\D/g,"");
	$jq1001(this).val(num);

	var country_id_element_value = parseInt ( country_id_jelem.val() );

	// if France
	if (
		country_id_element_value === 76 ||
		country_id_element_value === 77 ||
		country_id_element_value === 78 ||
		country_id_element_value === 79 ||
		country_id_element_value === 89 ||
		country_id_element_value === 140 ||
		country_id_element_value === 189
	)
	{
		frequency_separator = 2;
		separator = " ";

		//Le principe de cette expression est d'insérer un espace tous les frequency_separator caractères (sauf à la fin)
		var num = $jq1001(this).val();
		var pattern = new RegExp("(.{" + frequency_separator + "})(?!$)", "g"); // /(.{2})(?!$)/g
		var replacement = "$1" + separator;
		var new_val = num.replace(/[ .-\/\D]/g,"");
		new_val = new_val.replace(pattern, replacement);

		$jq1001(this).val(new_val);

		return;

		//Le principe de cette expression est d'insérer un espace tous les 2 caractères (sauf à la fin)
		var pattern = new RegExp("(.{" + frequency_separator + "})(?!$)", "g"); // /(.{2})(?!$)/g
		var replacement = "$1" + separator;

		var num = obj.value;
		var new_val = num.replace(/[ .-\/\D]/g,"");
		var new_val = new_val.replace(pattern, replacement); //var replacement = replacement.replace(/(.{2})(?!$)/g,"$1 ");

		obj.value = new_val;
	}
}

/**
*/
function check_phoneNumbers()
{
	var phone = document.getElementById('phone').value;
	var mobile = document.getElementById('mobile').value;

	if (phone == "" && mobile == "")
	{
		millib_notifyErrorToUser( "Veuillez saisir au moins un numéro de téléphone ou mobile.", "phoneNumbers_err_container");
		return; 
	}
	else
	{
		if (phone != "") check_phone("phone", "calling_country_listing_1");
		else document.getElementById('calling_country_listing_1').selectedIndex = 0;

		if (mobile != "") check_phone("mobile", "calling_country_listing_2");
		else document.getElementById('calling_country_listing_2').selectedIndex = 0;
	}
}

/**
*/
function check_phone(phone_id, select_calling_country_id)
{
	var phone = document.getElementById(phone_id).value;

	if (document.getElementById(select_calling_country_id).value == 0)
	{
		millib_notifyErrorToUser( "[+please_input_calling_code+]", phone_id + "_err_container");
		return;
	}

	// if France
	if (
		document.getElementById(select_calling_country_id).value == 76 ||
		document.getElementById(select_calling_country_id).value == 77 ||
		document.getElementById(select_calling_country_id).value == 78 ||
		document.getElementById(select_calling_country_id).value == 79 ||
		document.getElementById(select_calling_country_id).value == 89 ||
		document.getElementById(select_calling_country_id).value == 140 ||
		document.getElementById(select_calling_country_id).value == 189
	)
	{
		if (phone.length != 14)
		{
			millib_notifyErrorToUser( "[+only_10_digits_in_phone+]", phone_id + "_err_container");
			return;
		}
	}
}

/**
*/
function check_country(select_country_id)
{
	if (document.getElementById(select_country_id).value == 0)
		millib_notifyErrorToUser( "[+please_select_a_country+]", select_country_id + "_err_container");
}

//delupsert[country_id][f][country_id]
/**
* This function formats the $jq1001(this) jquery element, according to the parameter country_id_jelem
*
* @param country_id_jelem (mandatory) {jquery element} Must be the 'select' element where the country is specifyed.
*/
$jq1001.fn.companynum_format = function (country_id_jelem)
{
	var frequency_separator;
	var separator;

	var country_id_element_value = parseInt ( country_id_jelem.val() );

	// if France
	if (
		country_id_element_value === 76 ||
		country_id_element_value === 77 ||
		country_id_element_value === 78 ||
		country_id_element_value === 79 ||
		country_id_element_value === 89 ||
		country_id_element_value === 140 ||
		country_id_element_value === 189
	)
	{
		frequency_separator = 3;
		separator = " ";

		var num = $jq1001(this).val();
		num = num.replace(/\D/g,"");

		//Le principe de cette expression est d'insérer un espace tous les frequency_separator caractères (sauf à la fin)
		var num_part_1 = num.substr(0,9);
		var pattern = new RegExp("(.{" + frequency_separator + "})(?!$)", "g"); // /(.{2})(?!$)/g
		var replacement = "$1" + separator;
		num_part_1 = num_part_1.replace(pattern, replacement); //var replacement = replacement.replace(/(.{2})(?!$)/g,"$1 ");

		var num_part_2 = num.substr(9, 5);

		var new_val;
		if (num_part_2 == "") new_val = num_part_1;
		else new_val = num_part_1 + separator + num_part_2;

		$jq1001(this).val(new_val);
	}

	return $jq1001(this);
}

/**
*/
function generateCaptcha(captchaImg)
{
	var rand0to10 = Math.floor(Math.random()*11);
	document.getElementById(captchaImg).src = "manager/includes/veriword.php?rand=" + rand0to10;
}


/**
* This string is equivalent to the PHP ucwords, it uppercases the first character of each word in a string.
* See also the PHP ucfirst - Make a string's first character uppercase.
*/
String.prototype.capitalize = function()
{
	// m = complete pattern found
	// p1 = 1st back reference
	// p2 = 2nd back reference
	return this.replace( /(^|\s|-)(\D)/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
};

/**
*/String.prototype.capitalize_1st_word_of_sentence = function()
{
	//return this.replace( /(^|\.|\.\.\.|\?|\!)(\D)/g , function(m,p1,p2){//console.log(m + "-" + p1 + "-" + p2.toUpperCase()); } );
		return this.replace( /(^|\.+|\.+\s*|\?|\?\s*|\!|\!\s*)(\w)/g , function(m,p1,p2){ return p1+p2.toUpperCase(); } );
};

// }}}




