<?php

// #############################################################
// Open Page:
//$doc_root = realpath($_SERVER["DOCUMENT_ROOT"]);
//include_once $doc_root."/1001_addon/library/mil_/mil_.conf.php";	// needed espacially for ajax file
open_json_page ();
$term = strtolower($_GET['term']);
//sleep(1);


// #############################################################
// Get data from DB or text file orwhatever:
$myq = array (
	array ('category' => "Actors", 'label' => "Redford", 'db_store' => "1"),
	array ('category' => "Actors", 'label' => "Pitt", 'db_store' => "2"),
	array ('category' => "Actors", 'label' => "Wayne", 'db_store' => "3"),
	array ('category' => "Actors", 'label' => "Dujardin", 'db_store' => "4"),
	array ('category' => "Actors", 'label' => "Selec", 'db_store' => "5"),
	array ('category' => "Actors", 'label' => "Costner", 'db_store' => "6"),
	array ('category' => "Actress", 'label' => "Johanson", 'db_store' => "6"),
	array ('category' => "Actress", 'label' => "Monroe", 'db_store' => "8"),
	array ('category' => "Actress", 'label' => "Dunst", 'db_store' => "9"),
	array ('category' => "Singer", 'label' => "Jaeger", 'db_store' => "10"),
	array ('category' => "Singer", 'label' => "Mae", 'db_store' => "11"),
	array ('category' => "Singer", 'label' => "Willem", 'db_store' => "12")
);
//$myq = array ();


// #############################################################
// Get data from DB or text file orwhatever:
// filter the data from DB (using a SELECT WHERE query) or from text file:
$results = array ();
foreach ($myq as $row_num => $row)
{
	// is there the term into the label?
	if (strpos(strtolower($row['label']), $term) !== false)
	{
		$results[] = $row;
	}

	if (count($results) > 10) break;
}


// #############################################################
// Close Page:
print_r ($results);
close_json_page ($results);


// #############################################################
// #############################################################
// #############################################################
// Functions

function open_json_page ()
{
	// always modified
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

	// HTTP/1.1
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);

	header('Content-type: application/json; charset=utf-8');
	ob_start();	// lock the output buffering
}

function close_json_page ($output)
{
	ob_end_clean();		// unlock the output buffering 
	//$ajaxReturn = mixed_htmlentities($ajaxReturn);

	//echo array_to_json($results); // jquery example, but use the php json_encode() instead. 
	echo json_encode($output); // is actually the ajaxReturn
}




// taken from the jquery-ui example (search.php)
function array_to_json_2Bdeleted ( $array ){

	if( !is_array( $array ) ){
		return false;
	}

	$associative = count( array_diff( array_keys($array), array_keys( array_keys( $array )) ));
	if( $associative ){

		$construct = array();
		foreach( $array as $key => $value ){

			// We first copy each key/value pair into a staging array,
			// formatting each key and value properly as we go.

			// Format the key:
			if( is_numeric($key) ){
				$key = "key_$key";
			}
			$key = "\"".addslashes($key)."\"";

			// Format the value:
			if( is_array( $value )){
				$value = array_to_json( $value );
			} else if( !is_numeric( $value ) || is_string( $value ) ){
				$value = "\"".addslashes($value)."\"";
			}

			// Add to staging array:
			$construct[] = "$key: $value";
		}

		// Then we collapse the staging array into the JSON form:
		$result = "{ " . implode( ", ", $construct ) . " }";

	} else { // If the array is a vector (not associative):

		$construct = array();
		foreach( $array as $value ){

			// Format the value:
			if( is_array( $value )){
				$value = array_to_json( $value );
			} else if( !is_numeric( $value ) || is_string( $value ) ){
				$value = "'".addslashes($value)."'";
			}

			// Add to staging array:
			$construct[] = $value;
		}

		// Then we collapse the staging array into the JSON form:
		$result = "[ " . implode( ", ", $construct ) . " ]";
	}

	return $result;
}

?>
