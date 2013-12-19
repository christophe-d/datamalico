//
function DVIC_generic_only_digits_plus (event) 
{
	event.stopPropagation();

	var field = $jq1001(this).val();
	field = only_digits_plus(field);
	$jq1001(this).val(field);

	return $jq1001(this);
}
