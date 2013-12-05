<?php
//---------------------------------------------------------------------------
/** 
 * \file
 * \brief is a very usefull library of general functions.
 * 
 * \author christophed
 * 
 * \version 1.0
 * \date    2010
 */
//---------------------------------------------------------------------------

#############################################################
#															#
# Created on 16-12-2003						#
# 															#
# Last update: 16-12-2003									#
#															#
#															#
#############################################################


// {{{ Log, tracers, display vars...
/**
 * Instead of tracing your variables via an output on the web page itself, you can use trace2file functionnalities, like a console.
 * This allows you to display your variables in a trace2file file (like a console output).
 *
 * @warning If you want to use this trace2file functionnality, you have to set the $GLOBALS['config_ini']['trace2file'] = true; before.
 *
 * @param params: (optional default is an empty string) {mixed} Vars you want to display in your trace2file file.
 * @param label: {string} (optional, default is "") Is a albel to display before your output.
 * @param logfile: {string} (optional) This is the fullpathname of the output file. Please note the whatever the fullpathname you set, the extension 
 * 	.trace.log.txt will be added to the fullpathname.
 * 	- The default value is:
 * 		- __FILE__ (the trace file, will be a sibling of the executed script, with the appended extension)
 * 		-  or $GLOBALS['config_ini']['trace2file']['file'] if you have set in your conf.
 * 	- TIP: If you use __FILE__ the output trace file will be a sibling of the executed script, with the appended extension
 * @param reset: {bool|string} (optional, default is FALSE) If you want to 'reset' (that is to say to empty) the file at the begining of the function call,
 * 	set it to TRUE or "reset", or FALSE to add the text output at the end of the file.
 * @param rights: {octal integer, notation prepended by 0} (optional, default is '0600') Sets access rights to the file. Eg: 0600
 *
 * @return Nothing
 *
 * @warning This function is activated by the bool:
 * @code
 * $GLOBALS['config_ini']['trace2file']['state']
 * @endcode
 *
 *
 * @warning Use also var_dump_nooutput() of this present library in order to avoid the var_dump output and send it into the file you want.
 *
 *
 * Example of calls:
 * @code
 * // Examples of what could be a variable:
 * $foo = "Hello world";
 * $foo = array ("Hello world", "of computing");
 * $foo = array ('first' => "Hello world", 'second' => "of computing");
 * 
 * // Function call:
 * trace2file ($foo);				// simple string
 * trace2file (__FILE__.":".__LINE__);		// simple string
 * trace2file (array ($foo, "bar"));		// a numerical array
 * trace2file (); 				// empty for a new line
 *
 * // What about labelling:
 * trace2file ($foo, "my foo label");
 * trace2file (array ('logvalue' => $foo, 'loglabel' => "my foo label"));
 *
 * // Other examples:
 * echo trace2file ($var, "var = ", __FILE__, FALSE, 0600);
 * echo trace2file ($var, "var = ", realpath($_SERVER["DOCUMENT_ROOT"]) . "/1001_addon/logs/trace2file", FALSE, 0600);
 * 
 * // VERY USEFULL examples:
 * trace2file ("", "", __FILE__, true, 0600);		// Empty the sibling trace file.
 * trace2file ("", "", __FILE__, "reset", 0600);	// Empty the sibling trace file.
 * trace2file (__FILE__.":".__LINE__, "", __FILE__);	// tracer of file and line in the sibling trace file.
 *
 * @endcode
 */
function trace2file ($params = "", $label = "", $logfile = "", $reset = FALSE, $rights = 0600)
{
	$output = "";

	if ($GLOBALS['config_ini']['trace2file']['state'] === true)
	{
		if (
			is_array($params)
			&& count ($params) === 2
			&& isset ($params['logvalue'])
			&& isset ($params['loglabel'])
		)
		{
			$output .= print_r ($params['loglabel'] . ": ", true);
			$output .= print_r ($params['logvalue'], true);
		}
		else
		{
			$zetype = gettype($params);
			if (exists_and_not_empty ($label)) $output .= "$label ($zetype): " . print_r ($params, true);
			else $output .= print_r ($params, true);
			//else $output .= print_r ("(".gettype($params)."):".$params, true);
		}


		// ###################
		// Log into file part:
		$o_logfile;
		$extension = ".trace.log.txt";

		$mode;
		if ($reset === TRUE || strtoupper($reset) === "RESET" ) $mode = "w";
		else $mode = "a";


		if ($logfile === "")
		{
			// If you have set in your conf, the var $GLOBALS['config_ini']['trace2file']['file'], the output will go there:
			if (exists_and_not_empty ($GLOBALS['config_ini']['trace2file']['file']))
			{
				$logfile = $GLOBALS['config_ini']['trace2file']['file'] . $extension;
			}

			// By default, the trace file, will be a sibling of this file, with the appended extension.
			else
			{
				$logfile = __FILE__  . $extension;
			}
		}
		else
		{
			$logfile = $logfile . $extension;
		}
		//$o_logfile = fopen($logfile, "a");
		$o_logfile = fopen($logfile, $mode);

		chmod($logfile, $rights);

		flock($o_logfile, LOCK_EX);
		fputs($o_logfile, $output . "\n");
		flock($o_logfile, LOCK_UN);

		fclose($o_logfile);
	}
}

/**
 * You can trace your variables via an output on the web page itself.
 * This allows you to display your variables in the web page.
 *
 * trace2web() do the same as trace() or debugDisplayTable(), that you could use in the past. So you can replace prior use of 
 * trace() and debugDisplayTable() by trace2web().
 *
 * @param params: (optional default is an empty string) {mixed} Vars you want to display in your trace2file file.
 *
 * @return {string} Unlike the trace2file() function, trace2web() returns a string. Then you have to display or bufferrize this string.
 *
 * Example of calls:
 * @code
 * // Examples of what could be a variable:
 * $foo = "Hello world";
 * $foo = array ("Hello world", "of computing");
 * $foo = array ('first' => "Hello world", 'second' => "of computing");
 * 
 * // Function call:
 * echo trace2web ($foo);				// simple string
 * echo trace2web (__FILE__ . ": " . __LINE__);	// simple string
 * echo trace2web (array ($foo, "bar"));		// a numerical array
 * echo trace2web (); 				// empty for a new line
 *
 * What about labelling:
 * echo trace2web ($foo, "my foo label");
 * echo trace2web (array ('logvalue' => $foo, 'loglabel' => "my foo label"));
 * @endcode
 */
function trace2web ($params = "", $label = "")
{
	$output = "";

	if (
		is_array($params)
		&& count ($params) === 2
		&& isset ($params['logvalue'])
		&& isset ($params['loglabel'])
	)
	{
		$output .= print_r ($params['loglabel'] . ": ", true);
		$output .= print_r ($params['logvalue'], true);
	}
	else
	{
		$zetype = gettype($params);
		if (exists_and_not_empty ($label)) $output .= "$label ($zetype): " . print_r ($params, true);
		else $output .= print_r ($params, true);
		//else $output .= print_r ("(".gettype($params)."):".$params, true);
	}

	$output = "<pre style='font-size:12px;'>$output</pre>";


	return $output;
}
/** 
 * \fn function trace ($txt)
 * \brief is a function used to display a message (mostly used in order to debug)
 * \param $txt is the text to display.
 */
function trace ($txt="")
{
	echo "<pre>$txt</pre>";
}

/** 
 * \fn function debugDisplayTable ($tab, $name="")
 * \brief Has to display an array (in order to debug)
 * \param $tab is the array
 * \param $name is the name of the array. Can be empty.
 * 
 * How to call this function : 
 * 	echo debugDisplayTable ($tab, "\$tab");
 */
function debugDisplayTable ($tab, $name="")
{
	$output .= "<pre style='font-size:12px;'>$name :";
	$output .= print_r($tab, TRUE);
	$output .= "</pre><hr>";

	return $output;
}


// call : echo super_tracer (__LINE__);
// or echo super_tracer (__LINE__, __FILE__);
function super_tracer ($line, $file="")
{
	$ret;
	if ($file=="") $ret = "[" . $line . "]<br />";
	else $ret = "[" . $line . ":" . $file . "]<br />";

	return $ret;
}


// example of call : echo debug_chronometer (__LINE__);
/**
 * Helps to chronometer elapsed time between the present function call and the previous one.
 *
 * @param $place (mandatory) {string} A text helping you to identify what is the present line.
 *
 * @return $chrono {string} The elapsed time between the present call and the previous call.
 *
 * Example of call:
 * @code
 * echo debug_chronometer (__FILE__ . ":" . __LINE__);
 * @endcode
 */
function debug_chronometer ($place)
{
	$chrono = ($place . " -- > " . (microtime(true) - $GLOBALS['timer']['rigth_now']) . " seconds<br />");
	$GLOBALS['timer']['rigth_now'] = microtime(true);
	return $chrono;
}


/** 
 * \fn function debugDisplayVariables ()
 * \brief (used to debug) Has to display tose very important arrays:
 * 	- $_SESSION
 * 	- $_POST
 * 	- $_GET
 */
function debugDisplayVariables ()
{
	//debugDisplayTable ($GLOBALS, "\$GLOBALS");
	$output .= debugDisplayTable ($_SESSION, "\$_SESSION");
	$output .= debugDisplayTable ($_POST, "\$_POST");
	$output .= debugDisplayTable ($_GET, "\$_GET");

	/*
	$_GET
	$_POST
	$_SESSION
	$_COOKIE
	$_FILES
	$_SERVER
	$_ENV
	 */

	return $output;
}

function notifyErr ($err_code)
{
	$page = $_SERVER[PHP_SELF];

	$to  = "Intra-master <your@email.com>, " ;

	$from = "your@email.com>";
	//$cc = "Intra-master <your@email.com>";
	//$bcc = "Intra-master <your@email.com>";

	/* subject */
	$subject = "!!! Alert Intranet page=$page";

	/* message */
	$message = "
		<html>

		</html>
		";

	/* To send a mail in the HTML format, we use the: Content-type. */
	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";

	/* other headers */
	$headers .= "To: $to\r\n";
	$headers .= "From: $from\r\n";
	$headers .= "Cc: $cc\r\n";
	$headers .= "Bcc: $bcc\r\n";

	/* go to the PTT */
	$geslagt = mail($to, $subject, $message, $headers);
}
// }}}


// {{{ Time functions:
/// now () is an appendix function that return the date and time we are under the form of time that SQL understand.
/** 
 * \fn function now ()
 * \return a string under this form : 2004-03-19 11:07:58
 * is ok for mysql timestamp type
 */
function now ()
{	
	return date("Y-m-d H:i:s");
}

/// now () is an appendix function that return the date and time we are under the form of time that SQL understand.
/** 
 * \fn function now ()
 * \return a string under this form : 2004-03-19CET11:07:58
 */
function nowCET ()
{	
	return date("Y-m-dTH:i:s");
}

// $format : "[TOTAL_DAY_DIFF]d [REST_HOUR_DIFF]h [REST_MINUTE_DIFF]min"
function intelligent_time_diff ($iso_t1, $iso_t2, $format)
{
	$one_sec = 1;
	$one_min = $one_sec * 60;
	$one_hour = $one_min * 60;
	$one_day = $one_hour * 24;
	$one_week = $one_day * 7;
	$one_month = $one_day * 30;
	$one_year = $one_day * 365;
	$one_semester = $one_year / 2;
	$one_quarter = $one_semester / 2;

	$unix_t1 = strtotime($iso_t1);
	$unix_t2 = strtotime($iso_t2);

	$biggest_value = $unix_t2 > $unix_t1 ? $unix_t2 : $unix_t1;
	$smallest_value = $unix_t1 < $unix_t2 ? $unix_t1 : $unix_t2;


	$time_diff = $biggest_value - $smallest_value; //trace("time_diff : $time_diff");

	$total_day_diff = (int)($time_diff / $one_day); //trace("total_day_diff : $total_day_diff");
	$rest = (int)($time_diff % $one_day); //trace("rest : $rest");

	$rest_hour_diff = (int)($rest / $one_hour); //trace("hour_diff : $hour_diff");
	$rest = (int)($rest % $one_hour); //trace("rest : $rest");

	$rest_minute_diff = (int)($rest / $one_min); //trace("minute_diff : $minute_diff");
	$rest = (int)($rest % $one_min); //trace("rest : $rest");

	$rest_second_diff = (int)($rest / $one_sec); //trace("second_diff : $second_diff");
	$rest = (int)($rest % $one_sec); //trace("rest : $rest");

	$week_diff = $unix_t2 - $unix_t1; //trace("week_diff : $week_diff");
	$month_diff = $unix_t2 - $unix_t1; //trace("month_diff : $month_diff");
	$year_diff = $unix_t2 - $unix_t1; //trace("year_diff : $year_diff");
	$semester_diff = $unix_t2 - $unix_t1; //trace("semester_diff : $semester_diff");
	$quarter_diff = $unix_t2 - $unix_t1; //trace("quarter_diff : $quarter_diff");

	$total_hour_diff = (int)($time_diff / $one_hour);
	$total_minute_diff = (int)($time_diff / $one_min);
	$total_second_diff = (int)($time_diff / $one_sec);


	$function_return = $format;
	$function_return = str_replace("[TOTAL_DAY_DIFF]", $total_day_diff, $function_return);
	$function_return = str_replace("[REST_HOUR_DIFF]", $rest_hour_diff, $function_return);
	$function_return = str_replace("[REST_MINUTE_DIFF]", $rest_minute_diff, $function_return);
	$function_return = str_replace("[REST_SECOND_DIFF]", $rest_second_diff, $function_return);

	return $function_return;
}


/** 
 * \fn function shorterDate ($str)
 * \brief has to modify the format of the dates in string. \n\n
 * 	 	- before: 2004-03-18 11:03:02
 * 		- after: 2004-03-18
 * \param $str a string where the dates are to be changed.
 * \return $res an html string
 */
function shorterDate ($str)
{
	$motifLongDate = "([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})";  // 2004-02-10 12:00:27

	if (eregi ($motifLongDate, $str, $regs))
		$str = eregi_replace ($motifLongDate, "$regs[3]-$regs[2]-$regs[1]", $str);

	return $str;
}


/** 
 * \fn function getmicrotime ()
 * \brief used to count the time of the research
 * \return 
 */
function getmicrotime ()
{
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}



function throwhours ($fulldate)
{
	$date = explode(" ", $fulldate);
	return $date[0];
}



// }}}


// {{{ Files and dir accesses:
function myfilesize($file)
{
	// First check if the file exists.
	if(!is_file($file)) exit("File does not exist!");

	// Setup some common file size measurements.
	$kb = 1024;         // Kilobyte
	$mb = 1024 * $kb;   // Megabyte
	$gb = 1024 * $mb;   // Gigabyte
	$tb = 1024 * $gb;   // Terabyte

	// Get the file size in bytes.
	$size = filesize($file);

	if($size < $kb) return $size." B";
	else if($size < $mb) return round($size/$kb,2)." KB";
	else if($size < $gb) return round($size/$mb,2)." MB";
	else if($size < $tb) return round($size/$gb,2)." GB";
	else return round($size/$tb,2)." TB";
}

function DirectoryIndex($dirname, $newartid)
{
	$res .= "<form enctype=\"multipart/form-data\" action=\"$_SERVER[PHP_SELF]?op=DirectoryProcess&dirname=$dirname&newartid=$newartid\" method=\"post\">";
	$res .= "<table><tr><td><input type=\"file\" name=\"fileupload[]\"></td>";
	$res .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"10000000\">";
	$res .= "<td><input type=\"Submit\" value=\"Upload\"></td></tr></table>";
	$res .= "<input type=\"hidden\" name=\"submitted\" value=\"true\" style=\"border: solid #000000 1px; cursor:hand; \"></form>";

	return $res;
}

function DirectoryProcess($dirname)
{
	$submitted = $_POST['submitted'];
	$comments = $_POST['message'];

	if ($submitted == "true")
	{
		$userfile_name = $_FILES['fileupload']['name'][0];
		$userfile_tmp = $_FILES['fileupload']['tmp_name'][0];
		$userfile_size = $_FILES['fileupload']['size'][0];
		$userfile_type = $_FILES['fileupload']['type'][0];

		// check if the file existe
		if ($userfile_name == "")
		{
			echo "You haven't select a file<br>";
		}
		if ($userfile_name != "")
		{
			//move the file into $location
			$location = "$dirname/$userfile_name";
			move_uploaded_file($userfile_tmp, $location);
		}
	}
}


function DirectoryList($dirname, $baseurl, $newartid)
{
	print "<style type='text/css'>.oddrow { background-color: #ffffff; }.evenrow { background-color: #eeffee; }</style>";

	if ($handle = opendir($dirname))
	{
		print "<table cellpadding=5><tr class=heading><td>File</td><td align=center>Filesize</td><td>Delete</td></tr>";

		// SECOND PASS for files only
		$handle = opendir($dirname);
		$count = 0;
		while ($file = readdir($handle))
		{
			// don't show .dotfiles, self, directories, or links (links are bad??? hmm)
			if (substr($file,0,1)==".") continue;
			if ($file=="index.php") continue;
			if (@is_dir("$dirname/$file")) continue;
			//if (@is_link("$filepath/$file")) continue;
			$myfilearray[$count]= $file;
			$count++;
		}
		closedir($handle);

		// now sort on filename
		if (is_array($myfilearray))
		{
			sort($myfilearray);
			reset($myfilearray);
			foreach ($myfilearray AS $key=>$file)
			{
				$timestamp= filemtime("$dirname/$file");
				$modified= date("r", $timestamp);
				if ($evenrow)
				{
					$evenrow=0;
					$rowclass= "evenrow";
				}
				else
				{
					$evenrow=1;
					$rowclass= "oddrow";
				}

				$filesize= myfilesize("$dirname/$file");

				print "<tr class='$rowclass'><td valign=top><a href='$baseurl/$file'> $baseurl/$file </a></td><td valign=top align=center>$filesize</td><td align=center><a href=\"$_SERVER[PHP_SELF]?newartid=$newartid&op=DirectoryDelete&file=$dirname/$file\"><b> x </b></a></td></tr>";
			}
		}
		else print "<tr><td valign=middle colspan='3'>&nbsp; No files here.</td></tr>";
	}
	print "</table>";
}


function DirectoryListRO ($dirname, $baseurl)
{		
	if ($handle = opendir($dirname))
	{
		// SECOND PASS for files only
		$handle = opendir($dirname);
		$count = 0;
		while ($file = readdir($handle))
		{
			// don't show .dotfiles, self, directories, or links (links are bad??? hmm)
			if (substr($file,0,1)==".") continue;
			if ($file=="index.php") continue;
			if (@is_dir("$dirname/$file")) continue;
			//if (@is_link("$filepath/$file")) continue;
			$myfilearray[$count]= $file;
			$count++;
		}
		closedir($handle);

		//debugDisplayTable ($myfilearray, "\$myfilearray");


		// now sort on filename
		if (is_array($myfilearray))
		{
			$titles[] = "Attached files";
			$titles[] = "Filesize";

			sort($myfilearray);
			reset($myfilearray);

			foreach ($myfilearray AS $key=>$file)
			{
				//trace ("[$dirname]/[$file]");
				$timestamp= filemtime("$dirname/$file");
				$modified= date("r", $timestamp);

				$filesize= myfilesize("$dirname/$file");

				$cell1 = "<td valign=top><a href='$baseurl/$file'>$baseurl/$file</a></td>";
				$cell2 = "<td valign=top align=center>$filesize</td>";
				$row[] = array ("$cell1", "$cell2");
			}

			$res[rows] = $row;
			$res[titles] = $titles;
			return $res;
		}
	}

	$res[rows] = "No file attached";
	$res[titles] = "";
	return $res;
}


/**
 * Returns the list of folders and files in a directory.
 *
 * @param $dir: {string} (optional, default is ".") Specifies the directory to read.
 *
 * @return $list: {numerical array} (mandatory) Returns the list of files and folders, or an empty array.
 */
function ls_almost_all ($dir = ".")
{
	$list = array ();

	$lastCharacter = substr($dir, -1);
	if ($lastCharacter !== "/") $dir = $dir . "/";

	if ($handle = opendir($dir)) {
		while (false !== ($entry = readdir($handle)))
		{
			if ($entry != "." && $entry != "..")
			{
				$type = NULL;
				if (is_dir($dir.$entry)) $type = "d";
				if (is_file($dir.$entry)) $type = "f";
				if (is_link($dir.$entry)) $type = "l";

				$list[] = array (
					'type' => $type
					, 'name' => "$entry"
				);
			}
		}
		closedir($handle);
	}
	//echo trace2web ($list, "BEFORE list", __LINE__);

	//array_multisort ($list['name']);
	usort($list, 'ls_almost_all_cmp_func');

	//echo trace2web ($list, "AFTER list", __LINE__);

	return $list;
}

function ls_almost_all_cmp_func ($a, $b)
{
	//echo trace2web ($a, "a", __LINE__);
	//echo trace2web ($b, "b", __LINE__);
	$fn_return = 0;
	$arr_sort = array ($a['name'], $b['name']);
	//echo trace2web ($arr_sort, "arr_sort", __LINE__);
	sort($arr_sort);
	//echo trace2web ($arr_sort, "arr_sort", __LINE__);

	if ($arr_sort[0] === $a['name'])
	{
		$fn_return = -1;
	}
	else
	{
		$fn_return = 1;
	}

	return $fn_return;
}


function remove_by_force ($to_be_deleted)
{
	$pattern = '/\/$/i';
	$replacement = '';
	$to_be_deleted = preg_replace($pattern, $replacement, $to_be_deleted);

	if (!is_writeable($to_be_deleted))
	{
		chmod($to_be_deleted,0777);
	}

	if (is_file($to_be_deleted))
	{
		unlink ($to_be_deleted);

		if(!is_file($to_be_deleted)){return true;}
		else{return false;}
	}

	else if(is_dir($to_be_deleted))
	{
		$handle = opendir($to_be_deleted);
		while($tmp=readdir($handle))
		{
			if($tmp!='..' && $tmp!='.' && $tmp!='')
			{
				//trace($to_be_deleted . "/" . $tmp);
				$remove_by_force = remove_by_force ($to_be_deleted . "/" . $tmp);

				if ($remove_by_force === false) return false;
			}
		}

		closedir($handle);
		rmdir($to_be_deleted);

		if(!is_dir($to_be_deleted)){return true;}
		else{return false;}
	}

	return false;
}

/**
 * Gives the size in bytes of a directory and the number of files in it.
 * Thanks to http://fatherofcents.com/php-get-folder-directory-size-file-count/
 *
 * @param $dir: {string} (mandatory)
 *
 * @return $fn_return: {numerical and associative array} (mandatory)
 * 	- 0: Folder size in bytes
 * 	- 1: Number of files in the directory.
 * 	- size: Folder size in bytes
 * 	- count: Number of files in the directory.
 *
 * Example of use
 * @code
 * $registered_directory = $_SERVER['DOCUMENT_ROOT'] . "/1001_addon/registereds_files/24/offer/10/";
 * $sample = foldersize($registered_directory);
 * echo "Folder Size : " . $sample[0] . " Bytes </br>" ;
 * echo "File Count : " . $sample[1] . " Files </ br>" ;
 * @endcode
 */
function foldersize($dir)
{
	$count_size = 0;
	$count = 0;
	$dir_array = scandir($dir);
	foreach($dir_array as $key=>$filename){
		if($filename!=".." && $filename!="."){
			if(is_dir($dir."/".$filename)){
				$new_foldersize = foldersize($dir."/".$filename);
				$count_size = $count_size + $new_foldersize[0];
				$count = $count + $new_foldersize[1];
			}else if(is_file($dir."/".$filename)){
				$count_size = $count_size + filesize($dir."/".$filename);
				$count++;
			}
		}

	}

	return array(
		(int)$count_size
		, (int)$count
		, 'size' => (int)$count_size
		, 'count' => (int)$count
	);
}


function file2string ($filename)
{
	if ($filename != "")
	{
		$handle = fopen($filename, "r");
		$string = fread($handle, filesize($filename));
		// fread(), see also file_get_contents() and file()
		fclose($handle);
		return $string;
	}
	else
	{
		new mil_Exception ("No file $filename at this place", "1201111240", "ERROR", __FILE__ .":". __LINE__ );
		return "No file at this place " . __FUNCTION__ . "()";
	}
}

function include_by_file2string ($file)
{
	include_once $file;
	//$lang_php = file2string ($file);
	//eval ($lang_php);

	$output = debugDisplayTable ($mil_lang, "mil_lang");

	return $output;
}



// }}}


// {{{ Utils:

/**
 * The intelligent_ceilrounding, rounds any number higher than itself, but intelligently.
 * - A number between 0 and 10 will be rounded to the unit digit over. Eg: 8,4 becomes 9.
 * - A number between 10 and 100 will be rounded to the unit digit over. Eg: 84 becomes 90.
 * - A number between 100 and 1000 will be rounded to the tens digit over. Eg: 842 becomes 900.
 * - A number between 1000 and 10000 will be rounded to the hundreds digit over. Eg: 8424 becomes 9000.
 *
 * Here is the technical principle:
 * - if number is between 100 and 999, you divide it by 10, then you add 1, then you multiply by 10.
 * - if number is between 1000 and 9999, you divide it by 100, then you add 1, then you multiply by 100.
 * - if number is between 10000 and 99999, you divide it by 1000, then you add 1, then you multiply by 1000.
 *
 * @param (mandatory) {integer|decimal} This number is the one to be rounded. You can use negative number too.
 *
 * @return Return the rounded number given in argument.
 *
 * Examples:
 * @code
 * 
 * 
 * 
 * 
 *
 * $num = -501; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = -4.3; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = -1.2; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = -1; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = -0.6; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = -0.3; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 0; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 0.3; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 0.6; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 1; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 1.2; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 4; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 4.3; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 11; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 19; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 501; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 501.4; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 525; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 558; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = (float) 6100; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 8453; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 8671; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 15122; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 110430; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 * $num = 1458941; trace ( "$num --> " . intelligent_ceilrounding ($num) );
 *
 * // will do:
 * -501 --> -510
 * -4,3 --> -5
 * -1,2 --> -2
 * -1 --> -1
 * -0,6 --> -1
 * -0,3 --> -1
 * 0 --> 0
 * 1 --> 1
 * 0.6 --> 0.6
 * 0.3 --> 0.3
 * 1 --> 1
 * 1.2 --> 2
 * 4 --> 4
 * 4 --> 4
 * 4.3 --> 5
 * 11 --> 11
 * 19 --> 19
 * 501 --> 510
 * 501.4 --> 510
 * 525 --> 530
 * 558 --> 560
 * 6100 --> 6100
 * 8'453 --> 8'500
 * 8'671 --> 8'700
 * 15'122 --> 16'000
 * 110'430 --> 120'000
 * 1'458'941 --> 1'500'000
 * @endcode
 */
function intelligent_ceilrounding ($number)
{
	//trace2file("", "intelligent_ceilrounding ($number)", __FILE__);
	$plus_minus = $number < 0 ? -1 : 1;
	$number  = abs($number);
	//if ((int)$number == 0) return 0;

	$divider_multiplyer = 1;

	$divider_multiplyer = $number < 10000000000000 ? 100000000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 1000000000000 ? 10000000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 100000000000 ? 1000000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 10000000000 ? 100000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 1000000000 ? 10000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 100000000 ? 1000000 : $divider_multiplyer;
	$divider_multiplyer = $number < 10000000 ? 100000 : $divider_multiplyer;
	$divider_multiplyer = $number < 1000000 ? 10000 : $divider_multiplyer;
	$divider_multiplyer = $number < 100000 ? 1000 : $divider_multiplyer;
	$divider_multiplyer = $number < 10000 ? 100 : $divider_multiplyer;
	$divider_multiplyer = $number < 1000 ? 10 : $divider_multiplyer;
	$divider_multiplyer = $number < 100 ? 1 : $divider_multiplyer;

	$number_decimal = $number / $divider_multiplyer;
	$number_integer = (int) $number_decimal;
	$difference = $number_decimal - $number_integer;

	//trace2file($number_decimal, "number_decimal-".__LINE__, __FILE__);
	//trace2file($number_integer, "number_integer-".__LINE__, __FILE__);
	//trace2file($difference, "difference-".__LINE__, __FILE__);
	// this avoid that when you send 140, this will be rounded at 150 (which is far fetched)
	if ( $difference != 0) // don't use the operator !==
	{
		$number = (int)($number_integer + 1);
	}
	else
	{
		$number = (int)($number_integer);
	}
	//trace2file($number, "number-".__LINE__, __FILE__);
	$number = $number * $divider_multiplyer;
	//trace2file($number, "number-".__LINE__, __FILE__);

	return (int)$number * $plus_minus;
}

function param_exists_and_not_empty ($param)
{
	return (isset($param) && !empty($param));
}

/**
 * See also the php standard function isset() and my isset_notempty_notnull()
 */
function exists_and_not_empty ($param)
{
	return param_exists_and_not_empty ($param);
}
function isset_notempty_notnull ($param)
{
	//return param_exists_and_not_empty ($param);
	$is_it_set = isset ($param);
	$notempty = $param !== "";
	$notnull = $param !== null;
	if (gettype ($param) === "string")
	{
		$notnull = $notnull && strtolower($param) !== "null";
	}
	return ($is_it_set && $notempty && $notnull);
}


/**
 * This function avoid the output of the var_dump() function but return the var_dump() result.
 *
 * @code
 * $myvar_properties = var_dump_nooutput ($myvar);
 * echo $myvar_properties;
 * @endcode
 */
function var_dump_nooutput ($var)
{
	$previous_buffer = array (
		'length' => ob_get_length ()
		, 'restart_ob' => null
		//, 'content' => ob_get_contents();
	);

	// check if the ob is already open or not:
	if ($previous_buffer['length'] !== 0) // Normally if no buffering is active, the result should be FALSE (according to the PHP doc, but this is not !)
	{
		// if yes, get the content and keep it in memory:
		$previous_buffer['restart_ob'] = true;
		$previous_buffer['content'] = ob_get_contents ();
	}



	// Get the result of var_dump itself:
	ob_get_clean();
	ob_start();
	var_dump($var);
	$result = ob_get_clean();

	// retore the previous buffer if necessary:
	if ($previous_buffer['length'] !== 0)
	{
		ob_start();
		echo $previous_buffer['content']; // put the memory into the ob
	}

	return $result;
}


function scramble($data) {
	$data = ereg_replace('"','&quot;', $data);
	$data = ereg_replace("'","&#039;", $data);
	return $data;
}



/** 
 * \fn function isTrue ($arg)
 * \brief check if an integer is true or false, but taking care of the access format (where -1 is true)
 * \param $arg is the value to test.
 * \return the result 1 or 0
 * 
 * This function is so usefull to conturn the probleme of testing if a variable is true when it comes 
 * 	from a Access data base.
 */
function isTrue ($arg)
{
	return ($arg != 0);
}




/** 
 * \fn function is_even ($num)
 * \brief check if a number is even (multiple of 2)
 * \param $num is the integer
 * \return 1 or 0
 */
function is_even ($num)
{
	if ($num%2 == 0)
		return true;	// is even (multiple of 2) est pair
	else
		return false;	// is not even (not multiple of 2)
}


/** 
 * \fn function eval_in_string ($str, $obj)
 * \brief Has to execute all the eval instruction that are in a string.
 * \param $str is the string ie: "blabla blabla blabla, eval (\"return \$this->method (\"param\")\");, blabla "
 * \param $obj is the object that has to replace the string $this.
 * \return $res an html string ie: "blabla blabla blabla, result of the method with the param, blabla "
 */
function eval_in_string ($str, $obj)
{
	//ssts
	//$str = "< blabla_code ELEMENT_4 eval (\"return \$this->func01 (\"test01\");\"); /blabla  d $soupe e>";  // next cell

	//$regs = split ("(eval ?\(\")|(;\"\);)", $str);
	$regs = split ("(eval ?\(\"return \\\$this->)|(;\"\);)", $str);

	//debugDisplayTable ($regs, "\$regs");

	$res = "";
	$i = 0;
	foreach ($regs as $ind => $val)
	{		
		if (is_even ($i))
		{
			//echo "$val<br>";
			$res .= "$val";
		}
		else
		{
			//echo "eval &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;       [$val]<br>";
			//$res .= eval("return \$obj->$val;");
			//$res .= "$val";
			//echo "[$res]";
			//$res .= eval("$val;");
		}

		$i++;
	}

	return "$res";
}




// This function generate an unique number because it's relying on the time and microtime unix.
// 	Even if you call this function 2 following times, the 2nd number will be different than with the 
//  first one (unless your computer is very very very powerfull).
function generateUniqueNumber ()
{
	list($microsec, $sec) = explode(" ",microtime()); 
	list($garbage, $microsec) = explode(".", $microsec); 
	return "$sec$microsec";
}


/**
 * replace_leaves_keep_all_branches () merge two trees (arrays or objects) with two main behaviors:
 * - 'replace_leaves' means: For the two structures given in arguments, leaves of the tree (array or object) $priority_1 will override leaves of $priority_2.
 * - 'keep_all_branches' means: that if there is a branch in only one of both trees, this branch is kept and added to the returned value.
 *
 * @param $priority_1 (optional, default is NULL) {mixed} The tree to be considered as the overriding one.
 * @param $priority_2 (optional, default is NULL) {mixed} The tree to be considered as the overrided tree.
 *
 * @remark The overriding is done if the value isset()==true
 *
 * @return {mixed} The fusion of both trees, with all branches of both trees, and the $priority_1 tree overriding the $priority_2 tree.
 */
function replace_leaves_keep_all_branches ($priority_1 = NULL, $priority_2 = NULL)
{
	if (!isset ($priority_1)) return $priority_2;


	if (
		gettype($priority_1) === "array"
		|| gettype($priority_1) === "object"
	)
	{
		$this_depth;
		foreach ($priority_1 as $key => $val)
		{
			if (!isset($priority_2[$key]))
			{
				$this_depth[$key] = $priority_1[$key];
			}
			else
			{
				$this_depth[$key] = replace_leaves_keep_all_branches ($priority_1[$key], $priority_2[$key]);
			}
		}

		foreach ((array) $priority_2 as $key => $val)
		{
			//echo trace2web ($key, "priority_2[$key]");
			if (!isset($this_depth[$key]))
			{
				$this_depth[$key] = $priority_2[$key];
			}	
		}

		return $this_depth;
	}
	else
	{
		return $priority_1;
	}
}

// }}}


// {{{ Stings and chars:

/**
 * unaccent was previously called accents_to_ascii().
 * In any string, this function replaces accents char to an ascii char.
 * 	- Eg:
 * 		- bÃ©bÃ© ==> bebe
 * 		- Ã±oqui ==> noqui
 *
 * @param string: {string} (mandatory) The string with accents.
 *
 * @return string: {string} (mandaotry) The new string with accent char transformed to ascci char (without any accent).
 *
 * Example of use:
 * @code
 * echo unaccent ("Bonjour Ã  bÃ©bÃ©"); // displays "Bonjour a bebe"
 * @endcode
 *
 * @todo This function seems to bug... I guess this is because the encoding of this file is 'unknown-8bit' and not utf-8 or us-ascii 
 * 	(because of this server that does not take in charge the utf-8... what a world!)
 */
function unaccent ($string)
{
	$string = strtr($string, "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ", "AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn");
	return $string;
}



/**
 * This function restricts chars to only: $SAFE_OUT_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890._-";
 * Note that at first, the function calls unaccent() in order to transform accents chars to unaccent chars, because the rest of the function skip all bad chars.
 * 
 * IN:  chaine a encoder / String to encode
 * OUT: Chaine encodée / Encoded string
 *
 * Description: Encode special characters under HTML format
 *                           ********************
 *              Encodage des caractères spéciaux au format HTML
 */
function dummyfy_chars ($data)
{
	$data = unaccent ($data);

	$SAFE_OUT_CHARS = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890._-";
	$encoded_data = "";
	$result = "";
	for ($i=0; $i<strlen($data); $i++)
	{
		// All chars in the ASCII table ar simplyfied to $SAFE_OUT_CHARS
		if (strchr($SAFE_OUT_CHARS, $data{$i})) {
			$result .= $data{$i};
		}
		else if (($var = bin2hex(substr($data,$i,1))) <= "7F"){
			//$result .= "&#x" . $var . ";"; // Leur ligne la ne marche pas. Voir aussi htmlentities();
			$result .= "_";
		}

		// All chars above the basic ASCII table, above 127 or 7F
		else
		{
			$result .= "_";
			//$result .= $data{$i};
		}

	}

	return $result;
}

/**
 * This function creates a string of n chars.
 *
 * @param char: {string} (mandatory) Is the char to use to create the string.
 * @param n: {int} (mandatory) The nubmer of time this char must be used to create the string.
 *
 * @return string {string} (mandatory) Is the result.
 *
 * @code
 * $mystr = create_string_of_n_char ("+", 5);
 * echo $mystr; // will echo : "+++++"
 * @endcode
 */
function create_string_of_n_char ($char, $n)
{
	$string = "";
	for ($i=0; $i < $n; $i++)
	{
		$string .= $char;
	}
	return $string;
}



/**
 * This function converts an array to a string, adding delimiters only when necessary, in order to print a nice string.
 *
 * @param delimiter: {string} (mandatory) The string or char that must delimit array fields.
 * @param arr: {numerical array} (mandatory) The array with elements. Some elements can be empty. And this is even the reason why this function exists
 * 	(See the below example for a better understanding).
 *
 * @return str: {string} (mandatory) The string returned.
 *
 * Example of use:
 * @code
 * $arr = array ("", "", "Abraham", "", "", "", "LINKOLN", "", "USA", "", "", "");
 * $delimiter = "ANY_PERSONAL_AND_UNIQ_DELIMITER";
 * $str = array_to_string_nice_concat ($delimiter, $arr);
 * echo display($str, "str");
 * @endcode
 */
function array_to_string_nice_concat ($delimiter, $arr) //function array_to_string_nice_delimiters_display ($delimiter, $arr)
{
	$str = implode ("$delimiter", $arr);
	// Clean inappropriate delimiters:
	$str = preg_replace("/($delimiter)+/", "$delimiter", $str);
	$str = preg_replace("/^($delimiter)+/i", "", $str);
	$str = preg_replace("/($delimiter)+$/i", "", $str);

	return $str;
}

// }}}


// {{{ Bools, booleans:

/**
 * This function is very usefull to cross several boolean data and determine (depending on each bool) in what case we are.
 *
 * @param boolsBinary: {numerical array of bools values} (mandatory) This param must have at least 1 element, and an infinity of elements number.
 *
 * @return decimal_number: {int} (mandatory) Is the decimal number corresponding to the binary number constituated by boolsBinary.
 * 
 * Explanations: Imagine you have 3 bools:
 * 	- is_demand_owner
 * 	- is_published
 * 	- is_offer_owner
 * Then, if you cross their results, here are all the combinations you can get:
 * @code
 *  -------------------------------------------------------------------------------------
 * |			BINARY CODES				|	DECIMAL NUM	|
 *  -----------------------------------------------------------------------------------------------------------------------------------------------------
 * | is_demand_owner	| is_publiched	| 	is_offer_owner	|	Case number	|		Case name	|	Functional result	|
 *  -----------------------------------------------------------------------------------------------------------------------------------------------------
 * |		0	|	0	|		0	|		0	| is_anyone			| 	Can NOT see the page	|
 * |		0	|	0	|		1	|		1	| before_publication		| 	Can see the page	|
 * |		0	|	1	|		0	|		2	| is_anyone			| 	Can NOT see the page	|
 * |		0	|	1	|		1	|		3	| owner_after_publication	| 	Can see the page	|
 * |		1	|	0	|		0	|		4	| is_anyone			| 	Can NOT see the page	|
 * |		1	|	0	|		1	|		5	| before_publication		| 	Can see the page	|
 * |		1	|	1	|		0	|		6	| demand_owner_after_publication| 	Can see the page	|
 * |		1	|	1	|		1	|		7	| owner_after_publication	| 	Can see the page	|
 *  -----------------------------------------------------------------------------------------------------------------------------------------------------
 * @endcode
 *
 * You see that you for each combination, there is a DECIMAL value (actually the number of your functional case).
 * The, you can for each case, predefined what will be the functional result:
 *
 *
 * Example of use:
 * @code
 * // configure your: $is_demand_owner, $is_publiched, $is_offer_owner
 *
 * $case_num = boolsBinary_to_decimalCase ( array (
 * 	$is_demand_owner
 * 	, $is_publiched
 * 	, $is_offer_owner
 * ));
 *
 * $functional_behavior;
 * if ($case_num === 0)
 * {
 *	$functional_behavior['case_name'] = "is_anyone";	// is any_one connected
 * 	$functional_behavior['can_see_the_page'] = false;
 * }
 * ...
 * @endcode
 *
 * This function can work with 1, 2, 3, 4, 5... N booleans in base 2.
 */
function boolsBinary_to_decimalCase ($boolsBinary)
{
	$s_binary_num = "";
	foreach ($boolsBinary as $index => $value)
	{
		$s_binary_num .= (string) (int) (bool) $boolsBinary[$index];
	}

	$decimal_number = bindec($s_binary_num);	// converts from bin to dec

	return $decimal_number;
}

/**
 * Alias of boolsBinary_to_decimalCase()
 */
function cool_bools ($boolsBinary)
{
	return boolsBinary_to_decimalCase ($boolsBinary);
}


/**
 * This function is just a help function, which helps you to make all the cases you need when using cool_bools().
 * See the document "AND-OR - Boolean.ods" and the tab "cool_bools_case_number" to learn more about this.
 */
function generate_binaryCases_for_N_bools ($n_bools)
{
	// calculate the number of possible combinations of crossing a number of N booleans.
	$nb_possible_cases = pow (2, $n_bools);

	for ($case_num=0; $case_num<$nb_possible_cases; $case_num++)
	{
		$bin = decbin($case_num);
		$bin_n_chars = str_pad($bin, $n_bools, "0", STR_PAD_LEFT);
		$table_row = preg_replace('/(.)/i', "$1\t", $bin_n_chars);
		echo trace2web ("$table_row=\t$case_num");
	}	
}


// }}}


// {{{ Web functions:
function browserAlarm ()
{
	$res = "<script type='text/javascript' src='javascript/browserAlarm.js'></script>";
	return $res;
}


function browserDetection ()
{
	$res = "<script type='text/javascript' src='javascript/browserDetection.js'></script>";
	return $res;
}


function delete_html_tags ($html)
{
	$plaintext = ereg_replace ("<[^>]*>", " ", $html);
	return $plaintext;
}


// }}}


// {{{ DATABASE: Postgres SQL:
function str_to_postgres_latin1 ($str)
{
	$res = html_entity_decode($str); // change all except '
	$res = ereg_replace ("'", "&#39;", $res);		// protection during the insert. We avoid all the problems of simple quote.

	return $res;
}

// }}}


// {{{ old OD code:

// This function isused by the class submit to include an article, and also by the class search. it's more handy to keep it here.
// delete all the bad things that polluate the research, such as the break lines, and strange letters such as ?!...
//	--> clean every character that is carying information.
function cleaning_string_for_research ($str)
{	
	// accent characters in C language : char_sans_accent = (unsigned char) tolower (char_avec_accent);

	$plaintext = delete_html_tags ($str);

	$plaintext = ereg_replace ("-", " ", $plaintext);
	//laintext = ereg_replace ("\.", " ", $plaintext);
	$plaintext = ereg_replace ("\?", " ", $plaintext);
	//laintext = ereg_replace (",", " ", $plaintext);
	//laintext = ereg_replace (":", " ", $plaintext);
	$plaintext = ereg_replace ("!", " ", $plaintext);

	// This line transforme all the spaces or \t \r \n \f in one space. So there is only on lein now. And the + makes that if there are several spaces together, then they are replaced by only one. 
	$plaintext = ereg_replace (chr(160), " ", $plaintext); 
	$plaintext = ereg_replace ("[\n\r\t ]+", " ", $plaintext); 
	//$plaintext = ereg_replace ("[[:space:]]+", " ", $plaintext); 

	// delete the spaces at the beginning and at the end.
	$plaintext = ereg_replace ("^[[:space:]]+", "", $plaintext);
	$plaintext = ereg_replace ("[[:space:]]+$", "", $plaintext);

	/*
	for ($i=0; $i<10; $i++)		
	{
		echo ord($plaintext[$i])."-->".$plaintext[$i]."<br>";
	}
	 */
	return $plaintext;
}

function q_username_can_access_forums ($username)
{
	$query = "
		SELECT DISTINCT
		forums.forum_sectors.forum_id
		FROM
		forums.forum_sectors,
		intranet.users_sectors
		WHERE
		-- condition
		intranet.users_sectors.username = '$username' AND
		-- JOINS
		intranet.users_sectors.sector_id = forums.forum_sectors.sector_id";

	return $query;
}


function q_username_can_access_topics ($username)
{
	$q_username_can_access_forums = q_username_can_access_forums ($username);

	$query = "
		SELECT DISTINCT
		forums.forum_topics.topic_id
		FROM
		forums.forum_topics
		WHERE
		-- condition
		forums.forum_topics.forum_id IN ($q_username_can_access_forums)
		";

	return $query;
}



// }}}


// {{{ Misc:

// show javascript alert
// is used with a fill up of via placeholders.
function mil_webLoginAlert($msg)
{
	return "<script>window.setTimeout(\"alert('".addslashes($msg)."')\",10);</script>";
}

// }}}


// {{{ Regional and localisation and domain settings:

/**
 * This function retrieve region vars relative to the current user.i
 *
 * Region vars priority rely on:
 * 	- First, the user can force the region settings by choosing in the menu what regional pref he wants (its goes into the SESSION vars),
 * 		or it is already loaded into the DB (user preferences) (not implemented yet).
 * 	- But by default, regional settings rely on the geolocation. See get_geoloc_vars().
 * 	- If ever it fails in geolocating, then regional settings rely on the Top Level Domain, the client visits. (See mil_c topleveldomain)
 *
 * @return region: {associative array} (mandatory) All region vars:
 * 	- geoloc: {associative array} (optional) Vars relative to the geolocation of the user. See get_geoloc_vars ()
 * 	- tld: {associative array} (mandatory) In case the geoloccation has failed, then, regional settings rely on the Top Level Domain. 
 * 		This is actually the content of one row in mil_c_topleveldomain.
 * 		- tld_id: {int} (mandatory)
 * 		- top_level_domain: {string} (mandatory) This can fr, co.uk, net, org...
 * 	- lang: {string} (mandatory) Is the lang to be used in the web page. See in the DB mil_c_country.lang
 *	- sLangue: {string} (mandatory) Is a variable used for the CIC e-payment. For CIC payment, see Doc of cic payment : FR EN DE IT ES NL PT SV
 *	- country_info: {associative array} (mandatory) is the record relative to this country (locale, lang, currency...). See the mil_c_country table.
 *	- currency: {associative array} (mandatory) Is the result of the currency selected as regional setting. This result is taken from the DB
 *		mil_c_currency.
 *		- currency_id: {int} (mandatory) Is the Primary Key of the selected currency.
 *		- currency_code: {string} (mandatory) Is the currency to be used in the web page, for this user. This can be something like:
 *			EUR, GBP, USD, AUD, CHF... (See what is in mil_c_currency.currency_code)
 *		- currency_display: {string} (mandatory) Is the beautiful dipslay form of the currency.
 *		- currency_rate: {float} (mandatory) Is the EUR/Other_curr rate.
 *
 * @todo Remove the temporary: die("mil_lib.php:" . __LINE__);
 */
function get_region_settings ()
{
	$region = array (
		'geoloc' => get_geoloc_vars ()
		, 'tld' => NULL
		, 'lang' => NULL
		, 'sLangue' => NULL
		, 'currency' => NULL
	);

	$currency_id;

	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	// If the user has set his regional settings into the SESSION vars or into the DB (user preferences):
	//if ()


	// ##########################################
	// if geolocation is successfull, 
	// 	then I know the mil_c_country.alpha2 so I'll get the mil_c_country.locale, currency_id, and sLangue for this country alpha2:
	if ($region['geoloc']['ip_located'] === TRUE)
	{
		// ...

		// Get currency settings:
		$currency_id = $region['geoloc']['currency_id'];

		// align:
		$region['lang'] = $region['geoloc']['lang'];
		$region['sLangue'] = $region['geoloc']['sLangue'];
	}


	// ##########################################
	// if geolocation has failled, then execute a TLD location.
	// 	then I know the mil_c_topleveldomain.top_level_domain so I'll get the top_level_domain.country_id and thus the mil_c_country.locale, currency_id, and sLangue for this country_id:
	else if ($region['geoloc']['ip_located'] === FALSE)
	{
		//echo trace2web ($_SERVER['HTTP_HOST'], "_SERVER['HTTP_HOST']");

		$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";		
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		//$pageURL = "https://secure.dev.operationdecoration.ch/01_purchase_go";

		$tld = getTLD ($pageURL);
		//$tld = "co.uk";

		//echo trace2web ($tld, "tld");

		// Get TLD settings:
		$sql = "
			SELECT 
			mil_c_topleveldomain.tld_id
			, mil_c_topleveldomain.top_level_domain
			, mil_c_country.*
			FROM mil_c_topleveldomain
			INNER JOIN mil_c_country ON mil_c_country.country_id = mil_c_topleveldomain.country_id
			WHERE top_level_domain = '$tld'
			";

		$myq = $GLOBALS['dbcon']->qexec( array (
			'sql' => $sql
			, 'expected_affected_rows' => "1:1"
			, 'get_field_structure' => false
			, 'script_place' => __FILE__.":".__LINE__
		));
		//echo trace2web ($myq, "myq");


		if ($myq['metadata']['affected_rows'] === 1)
		{
			// Get currency settings:
			$currency_id = $myq['results']['records'][1]['currency_id'];

			// align:
			$region['tld'] = array (
				'tld_id' => $myq['results']['records'][1]['tld_id']
				, 'top_level_domain' => $row['top_level_domain']
			);
			unset ($myq['results']['records'][1]['tld_id']);
			unset ($myq['results']['records'][1]['top_level_domain']);
			$region['country_info'] = $myq['results']['records'][1];
			$region['lang'] = $myq['results']['records'][1]['lang'];
			$region['sLangue'] = $myq['results']['records'][1]['sLangue'];	
		}
	}

	//echo trace2web ($region, "region");


	// ##########################################
	// Set locale
	$locale_array = explode(",", $region['country_info']['locale']);
	setlocale(LC_ALL, $locale_array); // ... LC_MONETARY, LC_NUMERIC, LC_TIME ...
	$localeconv = localeconv();
	//echo trace2web (getlocale(), "ATTENTION This is the VERY first getlocale()");
	//echo trace2web ($localeconv, "The locale is");


	// ##########################################
	// Get currency settings:
	$localeconv_currency_code = $localeconv['int_curr_symbol']; // EUR, GBP, USD...
	// Keep only letters and figures:
	$localeconv_currency_code = preg_replace('/[^a-zA-Z0-9]/i', '', $localeconv_currency_code);
	// ############################################################################################################
	// ############################################################################################################
	// WARNING I use here the field mil_c_country, and not the $localeconv['int_curr_symbol'], because, it is
	// 	important for me to have the currency available into the DB, for SQL external reporting.
	// 	Thus, it means that $localeconv['int_curr_symbol'] and mil_c_country.currency_id must be online.
	// ############################################################################################################
	// ############################################################################################################
	$sql = "SELECT * FROM mil_c_currency WHERE currency_id = $currency_id";
	if ($mysqli_result = $mysqli_con->query($sql))
	{
		$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
		//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

		if ($nbRes == 0) {
			new mil_Exception (__FUNCTION__ . " : Should not happen: $sql", "1201111240", "ERROR", __FILE__ .":". __LINE__ );
		} else if ($nbRes === 1) { 
			$region['currency'] = $mysqli_result->fetch_assoc();
		} else if ($nbRes > 1) {
			new mil_Exception (__FUNCTION__ . " : Should not happen: $sql", "1201111240", "ERROR", __FILE__ .":". __LINE__ );	
		}

		$mysqli_result->free();
	} else {
		new mil_Exception (__FUNCTION__ . "() : This is not possible to execute the request: $sql, "
			. trace2web($mysqli_con->error, "mysqli_con->error")
			, "1201111240", "WARN", __FILE__ .":". __LINE__ );
	}


	// ##########################################
	// Check if there could be a currency inconsistency between the locale and milc_ccountry.currency_id:

	//echo trace2web($region, "region");
	//var_dump($region['currency']['currency_code']);
	//echo trace2web ($localeconv, "The locale is");
	//var_dump($localeconv_currency_code);
	if ($region['currency']['currency_code'] !== $localeconv_currency_code)
	{
		new mil_Exception (__FUNCTION__ . "() : Currency problem!!! The localeconv()['int_curr_symbol'] is not the same as the currency choosen for the country_id: "
			. $region['country_info']['country_id'] . "\n"
			. trace2web($region, "region")
			, "1201111240", "WARN", __FILE__ .":". __LINE__ );
		die("mil_lib.php:" . __LINE__);
	}

	//$mysqli_con->close();

	return $region;
}

/**
 * This function helps to geolocate the user according to its ip address.
 * I use the services of Maxmind (their monthly free geolite update) and I insert it into the DB. Then as I know the client ip address, I search in DB
 * 	where it is located and I'll know what regions params to use.
 *
 * @return geoloc: {associative array} (mandatory)
 * 	- ip_located: {bool} (mandatory) Says if the client ip address has been well located into the DB.
 * 	- ip: {string} (mandatory)
 * 	- alpha2: {string} (mandatory) Is the country code with 2 figures relying on the standard 'Iso 3166-1'.
 * 	- city: {string} (optional)
 * 	- latitude: {string} (optional)
 * 	- longitude: {sring} (optional)
 * 	- ...
 */
function get_geoloc_vars ()
{
	$geoloc = array (
		'ip_located' => FALSE
		, 'ip' => NULL
		, 'alpha2' => NULL
	);

	return $geoloc;
}

/**
 * This function returns the domain of any url.
 *
 * @code
 * echo getDomain('http://www.google.com/test.html') . '<br/>';
 * echo getDomain('https://news.google.co.uk/?id=12345') . '<br/>';
 * echo getDomain('http://my.subdomain.google.com/directory1/page.php?id=abc') . '<br/>';
 * echo getDomain('https://testing.multiple.subdomain.google.co.uk/') . '<br/>';
 * echo getDomain('http://nothingelsethan.com') . '<br/>';
 *
 * // the ouput will be:
 * google.com
 * google.co.uk
 * google.com
 * google.co.uk
 * nothingelsethan.com
 * @endcode
 * 
 * Taken at http://pastebin.com/index/JQ9ikbiZÃ³
 */
function getDomain($url) 
{
	$pieces = parse_url($url);
	$domain = isset($pieces['host']) ? $pieces['host'] : '';
	if (preg_match('/(?P<domain>[a-z0-9][a-z0-9-]{1,63}.[a-z.]{2,6})$/i', $domain, $regs)) {
		return $regs['domain'];
	}
	return false;
}

/**
 * This function returns the Top Level Domain of an url.
 *
 * @code
 * echo getTLD('http://www.google.com/test.html') . '<br/>';
 * echo getTLD('https://news.google.co.uk/?id=12345') . '<br/>';
 * echo getTLD('http://my.subdomain.google.com/directory1/page.php?id=abc') . '<br/>';
 * echo getTLD('https://testing.multiple.subdomain.google.co.uk/') . '<br/>';
 * echo getTLD('http://nothingelsethan.com') . '<br/>';
 *
 * // the ouput will be:
 * com
 * co.uk
 * com
 * co.uk
 * com
 * @endcode
 */
function getTLD ($url)
{
	$domain = getDomain ($url); // domain only, without subdomains.
	$pattern = '/^[^\.]*\./';
	$replacement = '';
	$tld = preg_replace($pattern, $replacement, $domain);
	return $tld;
}





/**
 * Returns the current locale.
 *
 * @return current_locale: {string} (mandatory) Eg: "fr_FR.utf8"
 */
function getlocale ()
{
	return setlocale(LC_ALL, 0);
}

// }}}


// {{{ operationdecoration project:

/**
 * This function is used to mix up an id, and confuse the user, in order to harden the access to an information,
 * the mixing-up is done from base $from_base to base $to_base (letters can be added to the result if the base > 10).
 *
 * @param $number (mandatory) {mixed, can be decimal, hexa...} The value to mix up in the from_base base.
 * @param $from_base (mandatory) {int} The reference base from which to convert.
 * @param $to_base (mandatory) {int} The base into which figures must be converted.
 * @param $coef (optional, default is 3) {int} A coeficient to apply to the jam process.
 *
 * @return (string) A complex string matching the given $number (but in base 16, thus letters are integered), but mixed up.
 *
 * This function and its reverse function unjam_base_to_base() have been tested with:
 * 	- jam_base_to_base (29999999999999, 10, 36, 3); 	// 29 999 999 999 999 and it works well.
 * 	- jam_base_to_base (29999999999999, 10, 16, 3); 	// 29 999 999 999 999 and it works well.
 *	- jam_base_to_base (2147483647 * 2, 10, 36, 20);	// int unsigned max on 4 bites.
 */
function jam_base_to_base ($number, $from_base, $to_base, $coef = 3)
{
	//echo trace2web($int, "jam_base_to_base($number)");

	// Jam by applying a transform coefficient, in base 10 anyway:
	$number = base_convert($number, $from_base, 10);	//echo trace2web($number, "to decimal"); // put it in the decimal base, anyway, before applying the coef
	$number = $number * $coef;				//echo trace2web($number, "after multiplication");
	$number = $number + $coef;				//echo trace2web($number, "after addition");

	// Jam by transforming to another base:
	$number = base_convert($number, 10, $to_base);		//echo trace2web($number, "after base_convert");

	// Jam by reversing display order of figures:
	$number = strrev( (string) $number);			//echo trace2web($number, "after strrev");

	return (string) $number;
}

/**
 * This function is used to unmix up an id, and retrieve the good id after confusing the user, in order to harden the access to an information,
 * the unmixing-up is done from base 16 to base 10 (letters are transformed to figures).
 *
 * @param $number (mandatory) {string} The value to unmix up in the from_base base.
 * @param $int (mandatory) {int} The reference base from which to convert.
 * @param $int (mandatory) {int} The base into which figures must be converted.
 * @param $int (optional, default is 3) {int} The value to mix up in the from_base base.
 *
 * @return (int, but like for any int overflow, this will be a float instead. See http://fr2.php.net/manual/en/language.types.integer.php)
 * 	The original id matching the given mixed up $int.
 *
 * This function and its reverse function jam_base_to_base() have been tested with:
 * 	- unjam_base_to_base (29999999999999, 10, 36, 3); 	// 29 999 999 999 999 and it works well.
 * 	- unjam_base_to_base (29999999999999, 10, 16, 3); 	// 29 999 999 999 999 and it works well.
 *	- unjam_base_to_base (2147483647 * 2, 10, 36, 20);	// int unsigned max on 4 bites.
 */
function unjam_base_to_base ($number, $from_base, $to_base, $coef = 3)
{
	//echo trace2web($number, "unjam_base_to_base($number)");

	// UnJam by reversing display order of figures:
	$number = strrev((string) $number);			//echo trace2web($number, "after strrev");

	// UnJam by transforming from another base:
	$number = base_convert($number, $to_base, 10);		//echo trace2web($number, "to decimal");

	// UnJam by applying a transform coefficient, in base 10 anyway:
	$number = $number - $coef;				//echo trace2web($number, "after subsctraction");
	$number = $number / $coef;				//echo trace2web($number, "after division");
	$number = base_convert($number, 10, $from_base);	//echo trace2web($number, "after base_convert");
	//echo trace2web (var_dump ($str));

	return $number;
}




function check_captcha ($captcha)
{
	$one_error = array (
		'html_container' => "captcha"
		, 'metadata' => array (
			'valid' => true
		)
	);

	if (exists_and_not_empty($captcha))
	{
		if ($captcha !== $_SESSION["veriword"])
		{
			$one_error['metadata']['valid'] = false;
			$one_error['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['captcha_bad'];
			return $one_error;
		}
		else
		{
			$one_error['metadata']['valid'] = true; //GOOD_CAPTCHA
		}
	}
	else
	{
		$one_error['metadata']['valid'] = false;
		$one_error['metadata']['returnMessage'] = $GLOBALS['mil_lang_common']['captcha_absent'];
		return $one_error;
	}

	return $one_error;
}

/**
 * This function is very usefull to get config informations, and handle these informations by their name instead of their id,
 * 	eg: handling the status 'contact_offer' instead of its id '1'.
 *
 * @param table: {string} (mandatory) Is the name of the table you want to get, eg: 'mil_c_basket_item_type'
 * @param id_field: {string} (mandatory) Is the name of the field containing the numerical id, eg: 'type_id'
 * @param name_field: {string} (mandatory) Is the name of the field containing the internal name (not the translated into french, german...), eg: 'type_name'
 *
 * @return fn_return {associative and numerical array} (mandatory) Keys of this array are of 2 types: 'type_id' and 'type_name'. See the example bellow.
 * 	If no value is stored in the table, then this array is empty.
 *
 *
 * Example of use:
 * @code
 * $GLOBALS['config_ini']['DB']['mil_c_basket_item_type'] = get_config_table ("mil_c_basket_item_type", "type_id", "type_name");
 *
 * echo trace2web($GLOBALS['config_ini']['DB']['mil_c_basket_item_type'][1]);
 * echo trace2web($GLOBALS['config_ini']['DB']['mil_c_basket_item_type']['1']);
 * echo trace2web($GLOBALS['config_ini']['DB']['mil_c_basket_item_type']['contact_offer']);
 * echo trace2web($GLOBALS['config_ini']['DB']['mil_c_basket_item_type'], "mil_c_basket_item_type");
 *
 * // Display will be:
 * contact_offer
 * contact_offer
 * 1
 * mil_c_basket_item_type: Array
 * (
 *     [1] => contact_offer
 *     [contact_offer] => 1
 *     [2] => sales_com
 *     [sales_com] => 2
 *     [3] => special_offer
 *     [special_offer] => 3
 * )
 * @endcode
 */
function get_config_table ($table, $id_field, $name_field)
{
	global $mysqli_con; //$mysqli_con = mil_mysqli_connection ();

	$sql = "
		SELECT $id_field, $name_field
		FROM $table
		";

	$records;
	$fn_return;

	if ($mysqli_result = $mysqli_con->query($sql))
	{
		$nbRes = $mysqli_result->num_rows;	// SELECT // if ($mysqli_result->num_rows === 1) { echo "There is one result"; }
		//$nbRes = $mysqli_con->affected_rows;	// INSERT, UPDATE, REPLACE ou DELETE, SELECT

		if ($nbRes == 0) {
			$fn_return = array();
		} else if ($nbRes > 0) {
			for ($l = 1; $row = $mysqli_result->fetch_assoc(); $l++) {
				$records[$l] = $row;

				$current_id_field = $records[$l][$id_field];
				$current_name_field = $records[$l][$name_field];

				$fn_return[$current_id_field] = $current_name_field;
				$fn_return[$current_name_field] = $current_id_field;
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

	//echo trace2web ($fn_return);

	return $fn_return;
}



// }}}


// {{{ Encoding:

function stripslashes_an_array ($arr)
{
	foreach ($arr as $key => $value)
	{
		if (gettype($value) == "array")
		{
			$arr["$key"] = stripslashes_an_array ($value);
		}
		else
		{
			$arr["$key"] = stripslashes($value);
		}
	}

	return $arr;
}


// tested with : ! "#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~Â `ÂâÆââ¦â â¡Ëâ°Å â¹ÅÂÅ½ÂÂâââââ¢ââËâ¢Å¡âºÅÂÅ¾Å¸Â Â¡Â¢Â£Â¤Â¥Â¦Â§Â¨Â©ÂªÂ«Â¬Â®Â¯Â°Â±Â²Â³Â´ÂµÂ¶Â·Â¸Â¹ÂºÂ»Â¼Â½Â¾Â¿ÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃÃ Ã¡Ã¢Ã£Ã¤Ã¥Ã¦Ã§Ã¨Ã©ÃªÃ«Ã¬Ã­Ã®Ã¯Ã°Ã±Ã²Ã³Ã´ÃµÃ¶Ã·Ã¸Ã¹ÃºÃ»Ã¼Ã½Ã¾Ã¿â¬
// Usefull for url decoding and getting params from GET or POST sent by jquery ( $('#form_id').serialize () + '&' + $.param ({hello:'Bonjour', bye:'Aurevoir', alphabet:['a', 'b', 'c']})
function mixed_stripslashes ($mixed)
{
	if (gettype($mixed) === "boolean") return stripslashes($mixed);
	if (gettype($mixed) === "integer") return stripslashes($mixed);
	if (gettype($mixed) === "double") return stripslashes($mixed);
	if (gettype($mixed) === "string") return stripslashes($mixed);
	if (gettype($mixed) === "array")
	{
		$function_return;
		foreach ($mixed as $key => $val)
		{
			$function_return[$key] = mixed_stripslashes($val);
		}
		return $function_return;
	}
	if (gettype($mixed) === "object") return stripslashes($mixed);
	if (gettype($mixed) === "resource") return stripslashes($mixed);
	if (gettype($mixed) === "NULL") return stripslashes($mixed);
	if (gettype($mixed) === "unknown type") return stripslashes($mixed);
}

function mixed_rawurlencode ($mixed)
{
	if (gettype($mixed) === "boolean") return rawurlencode($mixed);
	if (gettype($mixed) === "integer") return rawurlencode($mixed);
	if (gettype($mixed) === "double") return rawurlencode($mixed);
	if (gettype($mixed) === "string") return rawurlencode($mixed);
	if (gettype($mixed) === "array")
	{
		$function_return;
		foreach ($mixed as $key => $val)
		{
			$function_return[$key] = mixed_rawurlencode($val);
		}
		return $function_return;
	}
	if (gettype($mixed) === "object") return rawurlencode($mixed);
	if (gettype($mixed) === "resource") return rawurlencode($mixed);
	if (gettype($mixed) === "NULL") return rawurlencode($mixed);
	if (gettype($mixed) === "unknown type") return rawurlencode($mixed);
}

function mixed_rawurldecode ($mixed)
{
	if (gettype($mixed) === "boolean") return rawurldecode($mixed);
	if (gettype($mixed) === "integer") return rawurldecode($mixed);
	if (gettype($mixed) === "double") return rawurldecode($mixed);
	if (gettype($mixed) === "string") return rawurldecode($mixed);
	if (gettype($mixed) === "array")
	{
		$function_return;
		foreach ($mixed as $key => $val)
		{
			$function_return[$key] = mixed_rawurldecode($val);
		}
		return $function_return;
	}
	if (gettype($mixed) === "object") return rawurldecode($mixed);
	if (gettype($mixed) === "resource") return rawurldecode($mixed);
	if (gettype($mixed) === "NULL") return rawurldecode($mixed);
	if (gettype($mixed) === "unknown type") return rawurldecode($mixed);
}


function mixed_convert_uuencode ($mixed)
{
	if (gettype($mixed) === "boolean") return convert_uuencode($mixed);
	if (gettype($mixed) === "integer") return convert_uuencode($mixed);
	if (gettype($mixed) === "double") return convert_uuencode($mixed);
	if (gettype($mixed) === "string") return convert_uuencode($mixed);
	if (gettype($mixed) === "array")
	{
		$function_return;
		foreach ($mixed as $key => $val)
		{
			$function_return[$key] = mixed_convert_uuencode($val);
		}
		return $function_return;
	}
	if (gettype($mixed) === "object") return convert_uuencode($mixed);
	if (gettype($mixed) === "resource") return convert_uuencode($mixed);
	if (gettype($mixed) === "NULL") return convert_uuencode($mixed);
	if (gettype($mixed) === "unknown type") return convert_uuencode($mixed);
}

function mixed_htmlentities ($mixed)
{
	if (gettype($mixed) === "boolean") return htmlentities($mixed);
	if (gettype($mixed) === "integer") return htmlentities($mixed);
	if (gettype($mixed) === "double") return htmlentities($mixed);
	if (gettype($mixed) === "string") return htmlentities($mixed);
	if (gettype($mixed) === "array")
	{
		$function_return;
		foreach ($mixed as $key => $val)
		{
			$function_return[$key] = mixed_htmlentities($val);
		}
		return $function_return;
	}
	if (gettype($mixed) === "object") return htmlentities($mixed);
	if (gettype($mixed) === "resource") return htmlentities($mixed);
	if (gettype($mixed) === "NULL") return htmlentities($mixed);
	if (gettype($mixed) === "unknown type") return htmlentities($mixed);
}


// }}}


?>
